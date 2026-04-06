<?php

namespace App\Actions\Signature;

use App\Enums\DocumentType;
use App\Models\Booking;
use App\Services\DocumentSignatureService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

final readonly class SendForSignatureAction
{
    public function __construct(private DocumentSignatureService $signatureService)
    {
    }

    public function handle(Request $request, int $bookingId, SignatureSendConfig $cfg): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        if (!$user->can($cfg->permission)) {
            abort(ResponseAlias::HTTP_FORBIDDEN, 'Unauthorized');
        }

        $booking = Booking::with(['customerInfos'])->find($bookingId);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], ResponseAlias::HTTP_NOT_FOUND);
        }

        if ($user->hasRole('Sales') && (int)$booking->created_by !== (int)$user->id) {
            return response()->json(['error' => $cfg->forbiddenMessage], ResponseAlias::HTTP_FORBIDDEN);
        }

        if ($booking->status !== $cfg->requiredBookingStatus) {
            return response()->json([
                'error' => "Cannot send for signature unless booking status is \"{$cfg->requiredBookingStatus}\"."
            ], ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }

        $docClass = $cfg->documentModelClass;
        $doc = $docClass::where('booking_id', $booking->id)->first();
        if (!$doc) {
            return response()->json(['error' => $cfg->missingDocMessage], ResponseAlias::HTTP_NOT_FOUND);
        }

        if (!empty($cfg->finalizedStatuses) && in_array((string) ($doc->status ?? ''), $cfg->finalizedStatuses, true)) {
            return response()->json([
                'error' => $cfg->alreadyFinalizedMessage,
            ], ResponseAlias::HTTP_CONFLICT);
        }

        if (empty($doc->file_path)) {
            return response()->json(['error' => $cfg->missingPdfMessage], ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }

        $recipients = $booking->customerInfos
            ->where('requires_signature', true)
            ->map(fn($c) => ['email' => $c->email, 'name' => $c->name_en ?? null])
            ->filter(fn($r) => !empty($r['email']))
            ->unique('email')
            ->values()
            ->toArray();

        if (empty($recipients)) {
            return response()->json([
                'error' => 'No customer emails found for this booking.'
            ], ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result = $this->signatureService->send(
            signable: $booking,
            documentable: $doc,
            type: DocumentType::from($cfg->documentTypeValue),
            recipients: $recipients,
        );

        return response()->json([
            'message' => $cfg->successMessage,
            'sent' => $result['sent'],
            'created' => $result['created'],
            'recipients' => $result['recipients'],
        ], ResponseAlias::HTTP_OK);
    }
}
