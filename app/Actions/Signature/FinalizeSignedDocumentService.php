<?php

namespace App\Actions\Signature;

use App\Models\Booking;
use App\Models\SigningLink;
use App\Models\User;
use App\Models\UserDoc;
use App\Services\PdfService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

readonly class FinalizeSignedDocumentService
{
    public function __construct(private PdfService $pdf)
    {
    }

    /**
     * @return string|null Signed file path if finalized (or already finalized), null if not ready / failed
     */
    public function finalizeBookingIfComplete(Model $documentable, int $bookingId, FinalizeSignedDocumentConfig $cfg): ?string
    {
        // already finalized
        if (!empty($documentable->signed_at) && !empty($documentable->signed_file_path)) {
            return $documentable->signed_file_path;
        }

        $booking = Booking::with(['customerInfos', 'installments.paymentPlan', 'unit', 'paymentPlan'])
            ->find($bookingId);

        if (!$booking) {
            return null;
        }

        $requiredEmails = $this->normaliseEmails(
            $booking->customerInfos
                ->where('requires_signature', true)
                ->pluck('email')
        );

        $signaturesByEmail = $this->getSignaturesByEmailIfComplete(
            documentable: $documentable,
            documentTypeValue: $cfg->type->value,
            requiredEmails: $requiredEmails,
        );

        if ($signaturesByEmail === null) {
            return null; // not complete (or no required emails)
        }

        $hasStaffUploadedSignature = collect($signaturesByEmail)->contains(fn ($signature) => ($signature['signature_source'] ?? null) === SigningLink::SIGNATURE_SOURCE_STAFF_UPLOADED);

        // calc (if needed)
        if ($booking->paymentPlan) {
            $booking->paymentPlan->dld_fee = round($booking->price * ($booking->paymentPlan->dld_fee_percentage / 100), 2);
        }

        // IMPORTANT: take one "now" only (avoid drifting timestamps)
        $finalSignedAt = now();

        $data = [
            'booking' => $booking,
            'customerInfos' => $booking->customerInfos,
            'paymentPlan' => $booking->paymentPlan,
            'installments' => $booking->installments,
            'unit' => $booking->unit,
            'signaturesByEmail' => $signaturesByEmail,
            'hasStaffUploadedSignature' => $hasStaffUploadedSignature,
            'finalSignedAt' => $finalSignedAt,
            'companySignedAt' => $documentable->company_signed_at ?? null,
        ];

        return $this->finaliseAndPersist(
            documentable: $documentable,
            cfg: $cfg,
            data: $data,
            fileKey: (string)$booking->getKey(),
            finalSignedAt: $finalSignedAt
        );
    }

    public function finalizeBrokerAgreementIfComplete(
        UserDoc                      $agreementDoc,
        FinalizeSignedDocumentConfig $cfg,
        User                         $approver,
        string                       $signaturePath,
        SigningLink                  $link
    ): ?string
    {
        $existingSigned = UserDoc::query()
            ->where('user_id', $agreementDoc->user_id)
            ->where('doc_type', 'signed_agreement')
            ->latest('id')
            ->first();

        if ($existingSigned && !empty($existingSigned->file_path)) {
            return $existingSigned->file_path;
        }

        $requiredEmails = $this->normaliseEmails([
            $agreementDoc->user?->email,
        ]);

        $signaturesByEmail = $this->getSignaturesByEmailIfComplete(
            documentable: $agreementDoc,
            documentTypeValue: $cfg->type->value,
            requiredEmails: $requiredEmails,
        );

        if ($signaturesByEmail === null) {
            return null; // not complete (or no required emails)
        }

        $hasStaffUploadedSignature = collect($signaturesByEmail)->contains(fn ($signature) => ($signature['signature_source'] ?? null) === SigningLink::SIGNATURE_SOURCE_STAFF_UPLOADED);

        $finalSignedAt = now();

        $data = [
            'agreement'         => $agreementDoc,
            'signaturesByEmail' => $signaturesByEmail,
            'hasStaffUploadedSignature' => $hasStaffUploadedSignature,
            'finalSignedAt'     => $finalSignedAt,
            'companySignedAt'   => $agreementDoc->company_signed_at ?? null,
            'user'              => $agreementDoc->user,
            'approver'          => $approver,
            'signaturePath'     => $signaturePath,
            'link'              => $link,
        ];

        return $this->finaliseAndPersist(
            documentable: $agreementDoc,
            cfg: $cfg,
            data: $data,
            fileKey: (string)$agreementDoc->user_id,
            finalSignedAt: $finalSignedAt
        );
    }

    private function normaliseEmails(iterable $emails): Collection
    {
        return collect($emails)
            ->filter()
            ->map(fn($e) => strtolower(trim((string)$e)))
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * @return array<string, array{path:string, signed_at:mixed, signature_source:?string}>|null
     * null => not complete / no required emails
     */
    private function getSignaturesByEmailIfComplete(
        Model      $documentable,
        string     $documentTypeValue,
        Collection $requiredEmails
    ): ?array
    {
        if ($requiredEmails->isEmpty()) {
            return null;
        }

        // Fetch all signed links for required emails (one query)
        $links = SigningLink::query()
            ->whereMorphedTo('documentable', $documentable)
            ->where('document_type', $documentTypeValue)
            ->whereIn('recipient_email', $requiredEmails->all())
            ->whereNotNull('signed_at')
            ->whereNotNull('signature_image_path')
            ->orderByDesc('signed_at')
            ->get();

        // distinct signed emails count
        $signedDistinct = $links
            ->map(fn($l) => strtolower(trim((string)$l->recipient_email)))
            ->unique()
            ->count();

        if ($signedDistinct !== $requiredEmails->count()) {
            return null;
        }

        // last signature per email (already sorted desc)
        return $links
            ->unique(fn($l) => strtolower(trim((string)$l->recipient_email)))
            ->mapWithKeys(fn($l) => [
                strtolower(trim((string)$l->recipient_email)) => [
                    'path' => Storage::disk('local')->path($l->signature_image_path),
                    'signed_at' => $l->signed_at,
                    'signature_source' => $l->signature_source,
                ],
            ])
            ->toArray();
    }

    private function finaliseAndPersist(
        Model                        $documentable,
        FinalizeSignedDocumentConfig $cfg,
        array                        $data,
        string                       $fileKey,
        CarbonInterface              $finalSignedAt
    ): ?string
    {
        $fileName = $cfg->filePrefix . $fileKey . '_' . $finalSignedAt->format('Ymd_His') . '.pdf';
        $path = trim($cfg->signedDir, '/') . '/' . $fileName;

        return DB::transaction(function () use ($documentable, $cfg, $data, $path, $finalSignedAt) {
            $fresh = $documentable->newQuery()->lockForUpdate()->find($documentable->getKey());

            if (!$fresh) {
                return null;
            }

            // already finalized
            if (!empty($fresh->signed_at) && !empty($fresh->signed_file_path)) {
                return $fresh->signed_file_path;
            }

            $this->pdf->store($cfg->view, $data, $path);

            if (!empty($cfg->persist) && is_callable($cfg->persist)) {
                ($cfg->persist)($fresh, $path, $finalSignedAt, $cfg);
                return $path;
            }

            $fresh->forceFill([
                'signed_at' => $finalSignedAt,
                'signed_file_path' => $path,
                'status' => $cfg->statusSigned,
            ])->save();

            return $path;
        });
    }
}
