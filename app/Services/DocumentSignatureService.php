<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Mail\SignatureLinkMail;
use App\Models\SigningLink;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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
     *   expired_previous:int,
     *   recipients:array<int,array{email:string,name:?string,url:string,signing_link_id:int}>
     * }
     */
    public function send(
        Model $signable,
        Model $documentable,
        DocumentType $type,
        iterable $recipients,
        ?Carbon $expiresAt = null
    ): array {
        // Normalize recipients -> unique by email
        $normalized = collect($recipients)
            ->filter(fn ($r) => !empty($r['email']))
            ->map(fn ($r) => [
                'email' => strtolower(trim($r['email'])),
                'name'  => isset($r['name']) ? trim((string) $r['name']) : null,
            ])
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
                // 1) Invalidate any existing active pending link for same doc + same recipient
                SigningLink::query()
                    ->whereMorphedTo('documentable', $documentable)
                    ->where('recipient_email', $recipient['email'])
                    ->where('document_type', $type->value)
                    ->where('status', SigningLink::STATUS_PENDING)
                    ->update([
                        'status' => SigningLink::STATUS_EXPIRED,
                    ]);

                // 2) Create a new signing link (token_hash generated in model booted())
                $link = SigningLink::create([
                    // context
                    'signable_type' => $signable->getMorphClass(),
                    'signable_id'   => $signable->getKey(),

                    // document
                    'documentable_type' => $documentable->getMorphClass(),
                    'documentable_id'   => $documentable->getKey(),

                    // recipient
                    'recipient_email' => $recipient['email'],
                    'recipient_name'  => $recipient['name'],

                    // type & lifecycle
                    'document_type' => $type,
                    'expires_at'    => $expiresAt, // keep null if not provided
                    'status'        => SigningLink::STATUS_PENDING,
                ]);

                $token = $link->plain_token;
                $signUrl = rtrim(config('app.frontend_url'), '/') . '/sign/' . $token;

                $downloadUrl = rtrim(config('app.url'), '/') . '/api/sign/doc/' . $token . '/download?variant=latest';

                $humanTitle = $type->value === 'RF' ? 'Reservation Form' : ($type->value . ' Document');

                DB::commit();

                // 3) Email (outside transaction)
                Mail::to($recipient['email'])->queue(
                    new SignatureLinkMail(
                        signable: $signable,
                        documentType: $type,
                        signingUrl: $signUrl,
                        downloadUrl: $downloadUrl,
                        recipientName: $recipient['name'],
                        documentTitle: $humanTitle,
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