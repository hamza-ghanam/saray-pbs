<?php

namespace App\Actions;

use App\Enums\DocumentType;
use App\Models\Booking;
use App\Models\SigningLink;
use App\Services\PdfService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

readonly class FinalizeSignedDocumentService
{
    public function __construct(private PdfService $pdf) {}

    /**
     * @return bool true if finalized OR already finalized, false if not ready / failed
     */
    public function finalizeIfComplete(Model $documentable, int $bookingId, FinalizeConfig $cfg): bool
    {
        // already finalized
        if (!empty($documentable->signed_at) && !empty($documentable->signed_file_path)) {
            return true;
        }

        $booking = Booking::with(['customerInfos', 'installments.paymentPlan', 'unit', 'paymentPlan'])
            ->find($bookingId);

        if (!$booking) {
            return false;
        }

        $requiredEmails = $booking->customerInfos
            ->where('requires_signature', true)
            ->pluck('email')
            ->filter()
            ->map(fn ($e) => strtolower(trim($e)))
            ->unique()
            ->values();

        if ($requiredEmails->isEmpty()) {
            return false;
        }

        // ✅ ensure all required emails signed (distinct)
        $signedDistinct = SigningLink::query()
            ->whereMorphedTo('documentable', $documentable)
            ->where('document_type', $cfg->type->value)
            ->whereIn('recipient_email', $requiredEmails->all())
            ->whereNotNull('signed_at')
            ->whereNotNull('signature_image_path')
            ->distinct('recipient_email')
            ->count('recipient_email');

        if ($signedDistinct !== $requiredEmails->count()) {
            return false;
        }

        // last signature per email
        $signaturesByEmail = SigningLink::query()
            ->whereMorphedTo('documentable', $documentable)
            ->where('document_type', $cfg->type->value)
            ->whereIn('recipient_email', $requiredEmails->all())
            ->whereNotNull('signed_at')
            ->whereNotNull('signature_image_path')
            ->orderByDesc('signed_at')
            ->get()
            ->unique(fn ($l) => strtolower(trim($l->recipient_email)))
            ->mapWithKeys(fn ($l) => [
                strtolower(trim($l->recipient_email)) => [
                    'path'      => Storage::disk('local')->path($l->signature_image_path),
                    'signed_at' => $l->signed_at, // Carbon إذا casts موجودة
                ],
            ])
            ->toArray();

        // calc (if needed)
        if ($booking->paymentPlan) {
            $booking->paymentPlan->dld_fee = round($booking->price * ($booking->paymentPlan->dld_fee_percentage / 100), 2);
        }

        // IMPORTANT: take one "now" only (avoid drifting timestamps)
        $finalSignedAt = now();

        $data = [
            'booking'           => $booking,
            'customerInfos'     => $booking->customerInfos,
            'paymentPlan'       => $booking->paymentPlan,
            'installments'      => $booking->installments,
            'unit'              => $booking->unit,
            'signaturesByEmail' => $signaturesByEmail,
            'finalSignedAt'     => $finalSignedAt,
            'companySignedAt'   => $documentable->company_signed_at ?? null,
        ];

        $fileName = $cfg->filePrefix . $bookingId . '_' . $finalSignedAt->format('Ymd_His') . '.pdf';
        $path = trim($cfg->signedDir, '/') . '/' . $fileName;

        // (اختياري ولكن أنصح) transaction + lock لمنع double-finalise لو إجت توقيعات متزامنة
        return DB::transaction(function () use ($documentable, $cfg, $data, $path, $finalSignedAt) {
            $fresh = $documentable->newQuery()->lockForUpdate()->find($documentable->getKey());

            if (!$fresh) {
                return false;
            }

            if (!empty($fresh->signed_at) && !empty($fresh->signed_file_path)) {
                return true;
            }

            $this->pdf->store($cfg->view, $data, $path);

            $fresh->forceFill([
                'signed_at'        => $finalSignedAt,
                'signed_file_path' => $path,
                'status'           => $cfg->statusSigned,
            ])->save();

            return true;
        });
    }
}
