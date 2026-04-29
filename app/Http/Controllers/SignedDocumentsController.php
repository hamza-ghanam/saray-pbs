<?php

namespace App\Http\Controllers;

use App\Contracts\Documents\SignableDocument;
use App\Enums\DocumentType;
use App\Models\Booking;
use App\Models\Building;
use App\Models\ReservationForm;
use App\Models\SPA;
use App\Models\User;
use App\Models\UserDoc;
use App\Services\DocumentSignatureService;
use App\Services\ImageService;
use Illuminate\Http\Request;
use App\Models\SigningLink;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SignedDocumentsController extends Controller
{
    /**
     * List latest signature attempts per recipient for a given signable + documentable (using aliases).
     *
     * This endpoint is generic and can be used for RF, SPA, etc. (no morphMap required).
     * It returns the latest SigningLink per recipient email (latest attempt).
     *
     * Aliases:
     * - signable_type: Booking
     * - documentable_type: RF | SPA
     *
     * @OA\Get(
     *     path="/signatures",
     *     summary="List signatures (latest attempt per recipient)",
     *     tags={"Signatures"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="signable_type",
     *         in="query",
     *         required=true,
     *         description="Signable type alias. Use 'Booking' for RF/SPA, 'User' for broker agreements.",
     *         @OA\Schema(type="string", enum={"Booking","User"}, example="Booking")
     *     ),
     *     @OA\Parameter(
     *         name="signable_id",
     *         in="query",
     *         required=true,
     *         description="Signable ID (e.g. booking id or user id)",
     *         @OA\Schema(type="integer", example=137)
     *     ),
     *     @OA\Parameter(
     *         name="documentable_type",
     *         in="query",
     *         required=true,
     *         description="Documentable type alias. RF = ReservationForm, SPA = SPA, UserDoc = broker agreement.",
     *         @OA\Schema(type="string", enum={"RF","SPA","UserDoc"}, example="RF")
     *     ),
     *     @OA\Parameter(
     *         name="documentable_id",
     *         in="query",
     *         required=true,
     *         description="Documentable ID (e.g. ReservationForm id, SPA id, or UserDoc id)",
     *         @OA\Schema(type="integer", example=55)
     *     ),
     *     @OA\Parameter(
     *         name="document_type",
     *         in="query",
     *         required=false,
     *         description="Document type filter. If omitted, defaults to the value of documentable_type.",
     *         @OA\Schema(type="string", enum={"RF","SPA","BROKER_AGREEMENT"}, example="RF")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List returned successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="count", type="integer", example=2),
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="signing_link_id", type="integer", example=999),
     *                     @OA\Property(property="email", type="string", format="email", example="customer1@example.com"),
     *                     @OA\Property(property="name", type="string", nullable=true, example="John Doe"),
     *                     @OA\Property(property="status", type="string", example="expired"),
     *                     @OA\Property(property="signed_at", type="string", format="date-time", nullable=true, example="2026-01-28 18:20:10"),
     *                     @OA\Property(property="has_signature", type="boolean", example=true),
     *                     @OA\Property(property="signature_path", type="string", nullable=true, description="URL to the signature image via GET /signatures/{id}/image, or null if no signature submitted yet.", example="https://api.example.com/signatures/999/image"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-01-28 18:19:45")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden – user lacks permission",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Forbidden")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error (missing/invalid query parameters)",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="message", type="string", example="The signable_type field is required."),
     *                     @OA\Property(property="errors", type="object", example={"signable_type":{"The signable_type field is required."}})
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="message", type="string", example="The documentable_type field must be one of RF, SPA, or UserDoc."),
     *                     @OA\Property(property="errors", type="object", example={"documentable_type":{"The documentable_type field must be one of RF, SPA, or UserDoc."}})
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->can('approve reservation form')) {
            return response()->json(['error' => 'Forbidden'], ResponseAlias::HTTP_FORBIDDEN);
        }

        $data = $request->validate([
            'signable_type'      => 'required|string|in:Booking,User',
            'signable_id'        => 'required|integer',
            'documentable_type'  => 'required|string|in:RF,SPA,UserDoc',
            'documentable_id'    => 'required|integer',
            'document_type'      => 'nullable|string|in:RF,SPA,BROKER_AGREEMENT',
        ]);

        $signableClass = $this->resolveSignableAlias($data['signable_type']);
        $documentableClass = $this->resolveDocumentableAlias($data['documentable_type']);

        $docType = $data['document_type'] ?? $data['documentable_type'];

        // All signing links (latest first)
        $links = SigningLink::query()
            ->where('signable_type', $signableClass)
            ->where('signable_id', $data['signable_id'])
            ->where('documentable_type', $documentableClass)
            ->where('documentable_id', $data['documentable_id'])
            ->where('document_type', $docType)
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn ($l) => strtolower(trim($l->recipient_email)))
            ->map(fn ($group) => $group->first())
            ->values();

        return response()->json([
            'count' => $links->count(),
            'items' => $links->map(function ($link) {
                $signImage = $link->signature_image_path ? route('signatures.image', ['signingLinkId' => $link->id]) : null;

                return [
                    'signing_link_id' => $link->id,
                    'email'           => $link->recipient_email,
                    'name'            => $link->recipient_name,
                    'status'          => $link->status,
                    'signed_at'       => $link->signed_at,
                    'has_signature'   => !empty($link->signature_image_path),
                    'signature_path'  => $signImage, // admin-only
                    'created_at'      => $link->created_at,
                ];
            }),
        ], ResponseAlias::HTTP_OK);
    }

    /**
     * Stream a signature image by SigningLink ID.
     *
     * - Requires authenticated user.
     * - Intended for admin/internal use to preview a submitted signature image.
     * - Returns the stored image file associated with the signing link.
     *
     * @OA\Get(
     *     path="/signatures/{signingLinkId}/image",
     *     summary="View signature image",
     *     tags={"Signatures"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="signingLinkId",
     *         in="path",
     *         required=true,
     *         description="SigningLink ID",
     *         @OA\Schema(type="integer", example=999)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Signature image streamed successfully",
     *         @OA\MediaType(
     *             mediaType="image/png"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden – user lacks 'view customer' or 'view users' permission",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Forbidden.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Signing link or signature image not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\SigningLink] 999")
     *         )
     *     )
     * )
     */
    public function showImage(Request $request, $id)
    {
        $signingLink = SigningLink::findOrFail($id);

        if (! auth()->user()->can('view customer') && ! auth()->user()->can('view users')) {
            abort(ResponseAlias::HTTP_FORBIDDEN);
        }

        $path = $signingLink->signature_image_path;
        return app(ImageService::class)
            ->streamImage($request, $path, true);
    }

    /**
     * Reject a submitted signature and resend a new signing link to the same recipient.
     *
     * - Cancels the selected signature attempt (SigningLink).
     * - Deletes signature image file (if exists).
     * - Resets the related document finalisation fields (signed_at, signed_file_path, status -> 'Pending') if supported.
     * - Generates and emails a new signing link to the same recipient (new token).
     *
     * @OA\Post(
     *     path="/signatures/{signingLinkId}/reject",
     *     summary="Reject a signature and resend new signing link",
     *     tags={"Signatures"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="signingLinkId",
     *         in="path",
     *         required=true,
     *         description="SigningLink ID to reject",
     *         @OA\Schema(type="integer", example=999)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="reason", type="string", nullable=true, example="Customer uploaded the wrong image")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Rejected and resent successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Signature rejected and new signing link sent.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden – user lacks permission",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Forbidden")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="SigningLink not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\SigningLink] 999")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable – no submitted signature on this link or validation error",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="This signing link has no submitted signature.")
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="message", type="string", example="The reason may not be greater than 255 characters."),
     *                     @OA\Property(property="errors", type="object", example={"reason":{"The reason may not be greater than 255 characters."}})
     *                 )
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Failed to reject signature.")
     *         )
     *     )
     * )
     */
    public function reject(Request $request, int $signingLinkId)
    {
        $user = $request->user();

        if (!$user->can('approve reservation form')) {
            return response()->json(['error' => 'Forbidden'], ResponseAlias::HTTP_FORBIDDEN);
        }

        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            /** @var SigningLink $link */
            $link = SigningLink::query()
                ->lockForUpdate()
                ->findOrFail($signingLinkId);

            if (empty($link->signed_at) || empty($link->signature_image_path)) {
                DB::rollBack();
                return response()->json([
                    'error' => 'This signing link has no submitted signature.'
                ], ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
            }

            if (Storage::disk('local')->exists($link->signature_image_path)) {
                Storage::disk('local')->delete($link->signature_image_path);
            }

            $link->forceFill([
                'signature_image_path' => null,
                'signed_at'            => null,
                'status'               => SigningLink::STATUS_WITHDRAWN,
            ])->save();

            // إذا الوثيقة تم finalise مسبقاً → نرجعها Not Signed
            $document = $link->documentable;
            if (method_exists($document, 'forceFill')) {
                $document->forceFill([
                    'signed_at'        => null,
                    'signed_file_path' => null,
                    'status'           => 'Pending',
                ])->save();
            }

            // إعادة إرسال رابط جديد لنفس المستلم
            $signatureService = app(DocumentSignatureService::class);
            $type = $link->document_type instanceof DocumentType
                ? $link->document_type
                : DocumentType::from((string) $link->document_type);

            $signatureService->send(
                signable: $link->signable,
                documentable: $link->documentable,
                type: $type,
                recipients: [[
                    'email' => $link->recipient_email,
                    'name'  => $link->recipient_name,
                ]]
            );

            DB::commit();
            return response()->json([
                'message' => 'Signature rejected and new signing link sent.',
            ], ResponseAlias::HTTP_OK);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Reject signature failed', [
                'signing_link_id' => $signingLinkId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => $e->getMessage(), // مؤقتاً باللوكال فقط
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function downloadDocumentVariant(Request $request, string $token)
    {
        $variant = (string) $request->query('variant', 'latest'); // latest|original|signed
        if (!in_array($variant, ['latest', 'original', 'signed'], true)) {
            return response()->json(['error' => 'Invalid variant.'], ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }

        $link = SigningLink::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (!$link) {
            return response()->json(['error' => 'Invalid link.'], ResponseAlias::HTTP_NOT_FOUND);
        }

        // Recommended: allow download even after submit (expired),
        // but block if cancelled.
        if ($link->status === SigningLink::STATUS_WITHDRAWN) {
            return response()->json(['error' => 'Link is not valid.'], 410);
        }

        $doc = $link->documentable;

        if (!$doc) {
            return response()->json(['error' => 'Document not found.'], ResponseAlias::HTTP_NOT_FOUND);
        }

        // ---- Resolve paths (generic via method-exists) ----
        if (! $doc instanceof SignableDocument) {
            return response()->json([
                'error' => 'Document does not support PDF download.'
            ],
            ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }

        $originalPath = $doc->getOriginalPdfPath();
        $signedPath   = $doc->getSignedPdfPath();

        $path = match ($variant) {
            'original' => $originalPath,
            'signed'   => $signedPath,
            default    => $signedPath ?: $originalPath, // latest
        };

        if (empty($path) || !Storage::disk('local')->exists($path)) {
            return response()->json(['error' => 'File not found.'], ResponseAlias::HTTP_NOT_FOUND);
        }

        $fileName = method_exists($doc, 'getDownloadFileName')
            ? $doc->getDownloadFileName($variant)
            : $this->fallbackFileName($link, $variant);

        return Storage::disk('local')->download($path, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Download the signable document using a valid signing token.
     *
     * - Public endpoint (no auth).
     * - Resolves the SigningLink by SHA-256 hash of the plain token.
     * - Allows download only while the signing link is still valid.
     * - Returns the original unsigned PDF associated with the document (RF, SPA or Broker Agreement).
     *
     * @OA\Get(
     *     path="/sign/doc/{token}/download-document",
     *     summary="Download signable document by signing token",
     *     tags={"Signing Links"},
     *
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         required=true,
     *         description="Plain signing token received by the signer",
     *         @OA\Schema(type="string", example="9f3a2b7c8d...plain_token_here...")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="PDF downloaded successfully",
     *         @OA\MediaType(mediaType="application/pdf")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Signing link not found, document invalid, or PDF file missing",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="Invalid signing link.")
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="Invalid document for this signing link.")
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="Document file not found.")
     *                 )
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=410,
     *         description="Signing link expired, already used, or withdrawn",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="This signing link has expired.")
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="error", type="string", example="This signing link is no longer valid.")
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    public function downloadDocumentByToken(string $token)
    {
        $link = SigningLink::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (!$link) {
            return response()->json([
                'error' => 'Invalid signing link.'
            ], ResponseAlias::HTTP_NOT_FOUND);
        }

        if (!empty($link->expires_at) && now()->greaterThan($link->expires_at)) {
            return response()->json([
                'error' => 'This signing link has expired.'
            ], ResponseAlias::HTTP_GONE);
        }

        if (!empty($link->signed_at) || in_array((string) $link->status, [
            SigningLink::STATUS_SIGNED, SigningLink::STATUS_EXPIRED, SigningLink::STATUS_WITHDRAWN
            ], true)) {
            return response()->json([
                'error' => 'This signing link is no longer valid.'
            ], ResponseAlias::HTTP_GONE);
        }

        $document = $link->documentable;

        if (!$document instanceof SignableDocument) {
            return response()->json([
                'error' => 'Invalid document for this signing link.'
            ], ResponseAlias::HTTP_NOT_FOUND);
        }

        $filePath = $document->getOriginalPdfPath();

        if (empty($filePath) || !Storage::disk('local')->exists($filePath)) {
            return response()->json([
                'error' => 'Document file not found.'
            ], ResponseAlias::HTTP_NOT_FOUND);
        }

        return response()->download(
            Storage::disk('local')->path($filePath),
            basename($filePath),
            ['Content-Type' => 'application/pdf']
        );
    }

    private function fallbackFileName(SigningLink $link, string $variant): string
    {
        $prefix = strtoupper((string) $link->document_type);
        $suffix = $variant === 'signed' ? 'SIGNED' : ($variant === 'original' ? 'ORIGINAL' : 'LATEST');
        return "{$prefix}_{$suffix}.pdf";
    }

    /**
     * @return class-string
     */
    private function resolveSignableAlias(string $alias): string
    {
        return match ($alias) {
            'Booking' => Booking::class,
            'User'    => User::class,
            default => throw new HttpException(
                ResponseAlias::HTTP_UNPROCESSABLE_ENTITY,
                'Unsupported signable_type alias.'
            ),
        };
    }

    /**
     * @return class-string
     */
    private function resolveDocumentableAlias(string $alias): string
    {
        return match ($alias) {
            'RF'      => ReservationForm::class,
            'SPA'     => SPA::class,
            'UserDoc' => UserDoc::class,
            default => throw new HttpException(
                ResponseAlias::HTTP_UNPROCESSABLE_ENTITY,
                'Unsupported documentable_type alias.'
            ),
        };
    }
}
