<?php

namespace App\Actions;

use App\Models\SigningLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class SubmitSignatureAction
{
    /**
     * @throws \Throwable
     */
    public function handle(Request $request, string $token, SignatureSubmitConfig $cfg, ?callable $finalize = null)
    {
        $request->validate([
            'signature' => ['required', 'string'],
        ]);

        $tokenHash = hash('sha256', $token);

        DB::beginTransaction();
        try {
            $link = SigningLink::query()
                ->where('token_hash', $tokenHash)
                ->lockForUpdate()
                ->first();

            if (!$link) {
                DB::rollBack();
                return response()->json(['error' => 'Invalid link.'], Response::HTTP_NOT_FOUND);
            }

            // allow after submit? هنا submit نفسه لازم pending فقط
            if ($link->status !== SigningLink::STATUS_PENDING || $link->signed_at !== null) {
                DB::rollBack();
                return response()->json(['error' => 'This link is no longer valid.'], 410);
            }

            if ($link->expires_at !== null && $link->expires_at->isPast()) {
                $link->forceFill(['status' => SigningLink::STATUS_EXPIRED])->save();
                DB::commit();
                return response()->json(['error' => 'This link has expired.'], 410);
            }

            $doc = $link->documentable;

            if (!$doc || !is_a($doc, $cfg->expectedDocumentClass)) {
                DB::rollBack();
                return response()->json(['error' => $cfg->invalidDocMessage], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($link->document_type->value !== $cfg->type->value) {
                DB::rollBack();
                return response()->json(['error' => 'Invalid document type for this endpoint.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Decode base64
            $sig = trim((string) $request->input('signature'));
            $sig = preg_replace('/^data:image\/png;base64,/', '', $sig);
            $sig = str_replace(' ', '+', $sig);

            $binary = base64_decode($sig, true);
            if ($binary === false) {
                DB::rollBack();
                return response()->json(['error' => 'Invalid signature encoding.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if (strlen($binary) > 1_500_000) {
                DB::rollBack();
                return response()->json(['error' => 'Signature image is too large.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Save signature image
            $signaturePath = $cfg->signatureDir . '/' . $link->id . '_' . now()->format('Ymd_His') . '.png';
            Storage::disk('local')->put($signaturePath, $binary);

            // Consume link
            $link->forceFill([
                'signature_image_path' => $signaturePath,
                'signed_at'            => now(),
                'status'               => SigningLink::STATUS_SIGNED,
                'client_ip'            => $request->ip(),
                'user_agent'           => (string) $request->userAgent(),
            ])->save();

            // Finalize if complete (حسب الوثيقة)
            $finalized = false;
            if ($finalize) {
                $finalized = (bool) $finalize($doc);
            }

            DB::commit();

            return response()->json([
                'message'   => 'Signature submitted successfully.',
                'finalized' => $finalized,
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('submit signature error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
