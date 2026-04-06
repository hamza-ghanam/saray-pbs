<?php

namespace App\Services;

use App\Contracts\Documents\SignableDocument;
use App\Enums\DocumentType;
use App\Mail\SignatureLinkMail;
use App\Models\SigningLink;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

class DocumentSignatureService
{
    /**
     * Send signing links (one unique token per recipient).
     *
     * @param  Model  $signable        Context object (Booking, BrokerAgreement, ...)
     * @param  Model  $documentable    The actual document model (ReservationForm, SPA, UserDoc, ...)
     * @param  DocumentType $type      RF, SPA, BROKER_AGREEMENT, ...
     * @param  iterable<array{email:string,name?:string|null}> $recipients
     * @param  Carbon|null $expiresAt
     * @return array{
     *   sent:int,
     *   created:int,
     *   recipients:array<int,array{email:string,name:?string,url:string,signing_link_id:int}>
     * }
     */
    public function send(
        Model $signable,
        Model $documentable,
        DocumentType $type,
        iterable $recipients,
        ?Carbon $expiresAt = null,
        ?string $customMessage = null
    ): array {
        if (!$documentable instanceof SignableDocument) {
            throw new InvalidArgumentException('Document does not support signing (missing HasPdfDocument).');
        }

        $originalPath = $documentable->getOriginalPdfPath();

        if (empty($originalPath) || !Storage::disk('local')->exists($originalPath)) {
            throw new RuntimeException('Original PDF file not found for signing.');
        }

        // Normalize recipients -> unique by email, with safe name handling
        $normalized = collect($recipients)
            ->filter(fn ($r) => !empty($r['email']))
            ->map(function ($r) {
                $email = strtolower(trim((string) $r['email']));

                $name = null;
                if (array_key_exists('name', $r)) {
                    if (is_string($r['name'])) {
                        $name = trim($r['name']);
                    } elseif (is_array($r['name'])) {
                        $name = trim(implode(' ', array_filter(
                            $r['name'],
                            fn ($v) => is_scalar($v) && trim((string) $v) !== ''
                        )));
                        $name = $name !== '' ? $name : null;
                    }
                }

                return [
                    'email' => $email,
                    'name'  => $name,
                ];
            })
            ->unique('email')
            ->values();

        $result = [
            'sent' => 0,
            'created' => 0,
            'recipients' => [],
        ];

        foreach ($normalized as $recipient) {
            DB::beginTransaction();

            try {
                // If this recipient has already signed this document, do not generate/send a new link.
                $alreadySigned = SigningLink::query()
                    ->where('documentable_type', $documentable->getMorphClass())
                    ->where('documentable_id', $documentable->getKey())
                    ->whereRaw('LOWER(TRIM(recipient_email)) = ?', [$recipient['email']])
                    ->where('document_type', $type->value)
                    ->whereNotNull('signed_at')
                    ->whereNotNull('signature_image_path')
                    ->first();

                if ($alreadySigned) {
                    DB::commit();
                    continue;
                }

                $plainToken = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $plainToken);

                // IMPORTANT:
                // The DB unique index is on:
                // (documentable_type, documentable_id, recipient_email, document_type)
                // So if a row already exists for this recipient/document/type,
                // we must UPDATE it instead of CREATE a new one.
                $existing = SigningLink::query()
                    ->where('documentable_type', $documentable->getMorphClass())
                    ->where('documentable_id', $documentable->getKey())
                    ->whereRaw('LOWER(TRIM(recipient_email)) = ?', [$recipient['email']])
                    ->where('document_type', $type->value)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    $existing->forceFill([
                        'signable_type'        => $signable->getMorphClass(),
                        'signable_id'          => $signable->getKey(),
                        'recipient_name'       => $recipient['name'],
                        'expires_at'           => $expiresAt,
                        'status'               => SigningLink::STATUS_PENDING,
                        'token_hash'           => $tokenHash,
                        'signed_at'            => null,
                        'signature_image_path' => null,
                        'client_ip'            => null,
                        'user_agent'           => null,
                    ])->save();

                    $link = $existing;
                } else {
                    $link = SigningLink::create([
                        'signable_type'     => $signable->getMorphClass(),
                        'signable_id'       => $signable->getKey(),
                        'documentable_type' => $documentable->getMorphClass(),
                        'documentable_id'   => $documentable->getKey(),
                        'recipient_email'   => $recipient['email'],
                        'recipient_name'    => $recipient['name'],
                        'document_type'     => $type,
                        'expires_at'        => $expiresAt,
                        'status'            => SigningLink::STATUS_PENDING,
                        'token_hash'        => $tokenHash,
                    ]);
                }

                $signUrl = rtrim(config('app.frontend_url'), '/') . '/sign/' . strtolower($type->value) . '/' . $plainToken;

                $humanTitle = match ($type->value) {
                    'RF'               => 'Reservation Form',
                    'SPA'              => 'Sales Purchase Agreement',
                    'BROKER_AGREEMENT' => 'Broker Agreement',
                    default            => $type->value . ' Document',
                };

                $safeRecipientName = $recipient['name'] ?: 'Recipient';
                $fileName = $humanTitle . '_' . $safeRecipientName . '_UNSIGNED.pdf';

                DB::commit();

                Mail::to($recipient['email'])->queue(
                    new SignatureLinkMail(
                        signable: $signable,
                        documentType: $type,
                        signingUrl: $signUrl,
                        filePath: $originalPath,
                        fileName: $fileName,
                        recipientName: $recipient['name'],
                        documentTitle: $humanTitle,
                        customMessage: $customMessage,
                        isWithdraw: !empty($customMessage)
                    )
                );

                $result['sent']++;
                $result['created']++;

                $result['recipients'][] = [
                    'email' => $recipient['email'],
                    'name'  => $recipient['name'],
                    'url'   => $signUrl,
                    'signing_link_id' => $link->id,
                ];
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        }

        return $result;
    }
}
