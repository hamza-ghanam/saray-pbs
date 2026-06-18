<?php

namespace App\Actions\Signature;

use App\Models\Booking;
use App\Models\SigningLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class UploadCustomerSignatureAction
{
    /**
     * @throws \Throwable
     */
    public function handle(
        Request $request,
        int $bookingId,
        CustomerSignatureUploadConfig $cfg,
        ?callable $finalize = null
    ) {
        $user = $request->user();

        if (!$user || !$user->can($cfg->permission)) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customer_infos,id'],
            'signature'   => ['required', 'string'],
        ]);

        if (is_array($request->input('customer_id')) || is_array($request->input('signature'))) {
            return response()->json([
                'error' => 'Invalid request payload.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $booking = Booking::with([$cfg->bookingRelation, 'customerInfos'])->find($bookingId);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], Response::HTTP_NOT_FOUND);
        }

        if ($user->hasRole('Sales') && (int) $booking->created_by !== (int) $user->id) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $document = $booking->{$cfg->bookingRelation}()->first();
        if (!$document || !is_a($document, $cfg->expectedDocumentClass)) {
            return response()->json([
                'error' => $cfg->documentNotFoundMessage
            ], Response::HTTP_NOT_FOUND);
        }

        if ($document->status !== 'Pending') {
            return response()->json([
                'error' => $cfg->invalidStatusMessage
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $customerId = (int) $request->integer('customer_id');

        $customer = $booking->customerInfos->first(function ($customerInfo) use ($customerId) {
            return (bool) ($customerInfo->pivot->requires_signature ?? true)
                && (int) $customerInfo->id === $customerId;
        });

        if (!$customer) {
            return response()->json([
                'error' => 'No signer with this customer_id was found for the booking.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $email = strtolower(trim((string) $customer->email));

        $signatureInput = $request->input('signature');
        if (!is_string($signatureInput)) {
            return response()->json([
                'error' => 'Invalid signature encoding.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $sig = trim($signatureInput);
        $sig = preg_replace('/^data:image\/png;base64,/', '', $sig) ?? $sig;
        $sig = str_replace(' ', '+', $sig);

        $binary = base64_decode($sig, true);
        if ($binary === false) {
            return response()->json(['error' => 'Invalid signature encoding.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (strlen($binary) > 1_500_000) {
            return response()->json(['error' => 'Signature image is too large.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();

        try {
            $link = SigningLink::query()
                ->lockForUpdate()
                ->whereMorphedTo('documentable', $document)
                ->where('document_type', $cfg->type->value)
                ->whereRaw('LOWER(TRIM(recipient_email)) = ?', [$email])
                ->whereNull('signed_at')
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>=', now());
                })
                ->latest('id')
                ->first();

            if (!$link) {
                $alreadySigned = SigningLink::query()
                    ->whereMorphedTo('documentable', $document)
                    ->where('document_type', $cfg->type->value)
                    ->whereRaw('LOWER(TRIM(recipient_email)) = ?', [$email])
                    ->whereNotNull('signed_at')
                    ->exists();

                if ($alreadySigned) {
                    DB::rollBack();
                    return response()->json([
                        'error' => 'This signer has already submitted a signature.'
                    ], Response::HTTP_CONFLICT);
                }

                $plainToken = bin2hex(random_bytes(32));

                $link = SigningLink::create([
                    'signable_type'     => Booking::class,
                    'signable_id'       => $booking->id,
                    'documentable_type' => $document::class,
                    'documentable_id'   => $document->getKey(),
                    'document_type'     => $cfg->type->value,
                    'recipient_email'   => $email,
                    'recipient_name'    => is_string($customer->name ?? null)
                        ? trim($customer->name)
                        : (is_array($customer->name ?? null)
                            ? trim(implode(' ', array_filter($customer->name)))
                            : null),
                    'token_hash'        => hash('sha256', $plainToken),
                    'status'            => SigningLink::STATUS_PENDING,
                    'expires_at'        => now()->addDays(7),
                ]);
            }

            $signaturePath = $cfg->signatureDir . '/'
                . $cfg->filePrefix . '_'
                . $booking->id . '_'
                . $link->id . '_'
                . now()->format('Ymd_His')
                . '.png';

            if (!Storage::disk('local')->put($signaturePath, $binary)) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Failed to save signature image.'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            if (!empty($link->signature_image_path) && Storage::disk('local')->exists($link->signature_image_path)) {
                Storage::disk('local')->delete($link->signature_image_path);
            }

            $link->forceFill([
                'signature_image_path' => $signaturePath,
                'signed_at'            => now(),
                'status'               => SigningLink::STATUS_SIGNED,
                'signature_source'     => SigningLink::SIGNATURE_SOURCE_STAFF_UPLOADED,
                'client_ip'            => $request->ip(),
                'user_agent'           => (string) $request->userAgent(),
            ])->save();

            $finalized = false;
            if ($finalize) {
                $finalized = (bool) $finalize($document);
            }

            DB::commit();

            return response()->json([
                'message'          => 'Customer signature uploaded successfully.',
                'signature_source' => SigningLink::SIGNATURE_SOURCE_STAFF_UPLOADED,
                'finalized'        => $finalized,
                // 'signing_link_id'  => $link->id,
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('upload customer signature error: ' . $e->getMessage());

            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
