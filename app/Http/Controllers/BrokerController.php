<?php

namespace App\Http\Controllers;

use App\Actions\Signature\SignatureSubmitConfig;
use App\Actions\Signature\SubmitSignatureAction;
use App\Enums\DocumentType;
use App\Models\Booking;
use App\Models\BrokerCommission;
use App\Models\Building;
use App\Models\GeneralSetting;
use App\Models\SigningLink;
use App\Models\User;
use App\Models\UserDoc;
use App\Services\DocumentSignatureService;
use App\Services\ImageService;
use App\Services\PdfService;
use finfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * @OA\Schema(
 *     schema="ValidationErrors",
 *     type="object",
 *     description="Laravel validation errors map: field => array of messages",
 *     additionalProperties=@OA\Schema(
 *         type="array",
 *         @OA\Items(type="string")
 *     ),
 *     example={"email":{"The email field is required."},"stamp":{"Stamp must be a PNG or JPEG image."}}
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     description="Simple error payload",
 *     @OA\Property(property="error", type="string", example="Invalid credentials"),
 *     @OA\Property(property="message", type="string", nullable=true, example="Invalid or expired token")
 * )
 * @OA\Schema(
 *     schema="SubmitSignatureOk",
 *     type="object",
 *     description="Generic success payload for signature submission",
 *     @OA\Property(property="message", type="string", example="Signature submitted successfully."),
 *     @OA\Property(property="data", type="object", additionalProperties=true)
 * )
 */

class BrokerController extends Controller
{

    /**
     * Generate the Broker Agreement PDF for a pending Broker and store it as a UserDoc (doc_type=broker_agreement).
     *
     * This endpoint ONLY generates/stores the agreement. It does NOT send a signing link.
     * After generating, call `sendAgreementForSignature()` to email the broker a one-time signing link.
     *
     * Business rules:
     * - Staff must have permission: `generate one-time link`
     * - Target user must exist, have role `Broker`, status `Pending`, and have a brokerProfile
     * - Broker must have required documents: rera_cert, trade_license, bank_account, tax_registration
     * - Replaces any previous broker_agreement doc by deleting the old file and record
     * - Updates brokerProfile.agreed_at
     *
     * @OA\Post(
     *     path="/brokers/{brokerUserId}/agreements/generate",
     *     operationId="generateBrokerAgreement",
     *     tags={"Brokers"},
     *     summary="Generate Broker Agreement PDF (store as UserDoc)",
     *     description="Staff-only endpoint. Generates the Broker Agreement PDF and stores it privately as a UserDoc (doc_type=broker_agreement). Does not send a signing link. Use the send-for-signature endpoint after generation.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="brokerUserId",
     *         in="path",
     *         required=true,
     *         description="Broker user ID",
     *         @OA\Schema(type="integer", example=300)
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Broker agreement PDF generated and returned as a download.",
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden (missing permission generate one-time link)",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Broker not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Business rule error (wrong role/status/profile/documents)",
     *         @OA\JsonContent(
     *             type="object",
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="error", type="string", example="User is not a Broker")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="error", type="string", example="Broker must be Pending to generate agreement")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="error", type="string", example="Broker profile missing")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="error", type="string", example="Missing required documents"),
     *                     @OA\Property(
     *                         property="missing",
     *                         type="array",
     *                         @OA\Items(type="string"),
     *                         example={"trade_license","bank_account"}
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     *
     * @throws Throwable
     */
    public function generateAgreement(
        Request $request,
        int $brokerUserId,
        PdfService $pdfService
    ) {
        $ctx = $this->getPendingBrokerAgreementContext($request, $brokerUserId);
        if ($ctx instanceof \Illuminate\Http\JsonResponse) {
            if ($ctx->getStatusCode() === Response::HTTP_UNPROCESSABLE_ENTITY) {
                $payload = $ctx->getData(true);
                if (($payload['error'] ?? null) === 'Broker must be Pending') {
                    return response()->json(['error' => 'Broker must be Pending to generate agreement'], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }
            return $ctx;
        }

        /** @var User $adminUser */
        $adminUser = $ctx['adminUser'];

        /** @var User $broker */
        $broker = $ctx['broker'];

        // ✅ Ensure required docs exist (you can tighten this)
        $requiredDocTypes = ['rera_cert', 'trade_license', 'bank_account', 'tax_registration'];
        $existingTypes = $broker->docs->pluck('doc_type')->unique()->values()->all();

        $missing = array_values(array_diff($requiredDocTypes, $existingTypes));
        if (!empty($missing)) {
            return response()->json([
                'error'   => 'Missing required documents',
                'missing' => $missing,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $agreedAt = now()->copy();
        $signaturePath = $adminUser->signature?->is_active
            ? $adminUser->signature->absolutePath()
            : null;

        try {
            $payload = DB::transaction(function () use ($adminUser, $signaturePath, $agreedAt, $broker, $pdfService) {

                // 1) Generate PDF path
                $pdfName = "BROKER_AGREEMENT_{$broker->id}_" . $agreedAt->format('Ymd_His') . ".pdf";
                $pdfPath = "agreements/brokers/{$pdfName}";

                // 2) Render & store PDF (mPDF via your PdfService)
                $company = GeneralSetting::getGroup('company');

                $pdfService->store('pdf.broker_agreement', [
                    'user'          => $broker,
                    'brokerProfile' => $broker->brokerProfile,
                    'userType'      => 'Broker',
                    'signaturePath' => $signaturePath,
                    'admin'         => $adminUser,
                    'buildingName'  => Building::orderBy('id')->value('name'),
                    'company'       => $company,
                ], $pdfPath);

                // 3) Create/replace agreement UserDoc
                $existingAgreement = $broker->docs()
                    ->where('doc_type', 'broker_agreement')
                    ->latest('id')
                    ->first();

                if ($existingAgreement) {
                    if (!empty($existingAgreement->file_path)) {
                        Storage::disk('local')->delete($existingAgreement->file_path);
                    }
                    $existingAgreement->delete();
                }

                /** @var UserDoc $agreementDoc */
                $agreementDoc = $broker->docs()->create([
                    'doc_type'  => 'broker_agreement',
                    'file_path' => $pdfPath,
                ]);

                $broker->brokerProfile->update([
                    'agreed_at' => $agreedAt,
                ]);

                return [
                    'agreement_doc_id' => $agreementDoc->id,
                    'pdf_path'         => $pdfPath,
                    'agreed_at'        => $agreedAt->toIso8601String(),
                ];
            });

            $fileName = basename((string) $payload['pdf_path']);
            $absolutePath = Storage::disk('local')->path((string) $payload['pdf_path']);

            if (!file_exists($absolutePath)) {
                return response()->json([
                    'error' => 'Generated broker agreement file not found on disk.'
                ], Response::HTTP_NOT_FOUND);
            }


            return response()->file($absolutePath, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            ]);

        } catch (\Throwable $e) {
            Log::error("generateBrokerAgreement failed for broker {$brokerUserId}: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Send a signing link for an already generated Broker Agreement.
     *
     * Flow:
     * - Validates staff permission (generate one-time link)
     * - Ensures broker exists, role=Broker, status=Pending, and brokerProfile exists
     * - Ensures a broker_agreement UserDoc already exists
     * - Sends a one-time signing link to the broker email
     *
     * @OA\Post(
     *     path="/brokers/{brokerUserId}/agreements/send-for-signature",
     *     operationId="sendBrokerAgreementForSignature",
     *     tags={"Brokers"},
     *     summary="Send one-time signing link for existing Broker Agreement",
     *     description="Staff-only endpoint. Sends a one-time signing link for an already generated Broker Agreement (doc_type=broker_agreement). The agreement must be generated beforehand using the generateBrokerAgreement endpoint.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="brokerUserId",
     *         in="path",
     *         required=true,
     *         description="Broker user ID",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Signing link sent successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Signing link sent successfully."),
     *             @OA\Property(
     *                 property="broker",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(property="email", type="string", format="email", example="broker@example.com")
     *             ),
     *             @OA\Property(property="agreement_doc_id", type="integer", example=456),
     *             @OA\Property(property="signing", type="object", description="Signing link payload (implementation-dependent)", additionalProperties=true)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden (missing permission generate one-time link)",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Broker not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Business rule error (agreement not generated, missing docs, wrong status)",
     *         @OA\JsonContent(
     *             type="object",
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="error", type="string", example="Broker agreement not generated yet. Generate it first.")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="error", type="string", example="Missing required documents")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="error", type="string", example="Broker must be Pending to send agreement for signature")
     *                 )
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function sendAgreementForSignature(
        Request $request,
        int $brokerUserId,
        PdfService $pdfService,
        DocumentSignatureService $signatureService
    )
    {
        $ctx = $this->getPendingBrokerAgreementContext($request, $brokerUserId);
        if ($ctx instanceof JsonResponse) {
            // Override message to keep exact old behaviour if status not pending
            if ($ctx->getStatusCode() === Response::HTTP_UNPROCESSABLE_ENTITY) {
                $payload = $ctx->getData(true);
                if (($payload['error'] ?? null) === 'Broker must be Pending') {
                    return response()->json(['error' => 'Broker must be Pending to send agreement for signature'], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }
            return $ctx;
        }

        /** @var User $adminUser */
        $adminUser = $ctx['adminUser'];

        /** @var User $broker */
        $broker = $ctx['broker'];

        // ✅ Must already have an agreement doc generated
        /** @var UserDoc|null $agreementDoc */
        $agreementDoc = $broker->docs()
            ->where('doc_type', 'broker_agreement')
            ->latest('id')
            ->first();

        if (!$agreementDoc || empty($agreementDoc->file_path)) {
            return response()->json([
                'error' => 'Broker agreement not generated yet. Generate it first.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // ✅ Optional: ensure required docs exist
        $requiredDocTypes = ['rera_cert', 'trade_license', 'bank_account', 'tax_registration'];
        $existingTypes = $broker->docs->pluck('doc_type')->unique()->values()->all();
        $missing = array_values(array_diff($requiredDocTypes, $existingTypes));
        if (!empty($missing)) {
            return response()->json([
                'error'   => 'Missing required documents',
                'missing' => $missing,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            // Send signing link (no PDF generation here)
            $sendResult = $signatureService->send(
                signable: $broker,
                documentable: $agreementDoc,
                type: DocumentType::BROKER_AGREEMENT,
                recipients: [
                    ['email' => $broker->email, 'name' => $broker->name],
                ],
            );

            return response()->json([
                'message' => 'Signing link sent successfully.',
                'broker'  => [
                    'id'    => $broker->id,
                    'email' => $broker->email,
                ],
                'agreement_doc_id' => $agreementDoc->id,
                'signing'          => $sendResult,
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error("sendBrokerAgreementForSignature failed for broker {$brokerUserId}: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Broker submits signature for the Broker Agreement using token + minimal auth.
     * Also collects representative/designation and stamp (base64 image), stores stamp locally
     * and saves stamp_path in broker profile.
     *
     * Notes:
     * - This endpoint DOES NOT finalize the agreement PDF; finalization happens on Admin approval.
     * - Token is hashed server-side (token_hash) and must be unused (signed_at NULL) and not expired.
     *
     * @OA\Post(
     *     path="/brokers/agreements/{token}/submit-signature",
     *     operationId="submitBrokerAgreementSignature",
     *     tags={"Brokers"},
     *     summary="Submit broker agreement signature (token-based) + update broker profile stamp/representative/designation",
     *     description="Broker submits a base64 signature image using a one-time token. Requires minimal authentication (email/password). Also saves representative and designation as strings and persists the stamp image by decoding base64, storing it privately, and writing the stored path to broker profile (stamp_path). Final PDF is generated later on admin approval.",
     *
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         required=true,
     *         description="One-time signing token (plain token, server matches by token_hash). Must be unused and not expired.",
     *         @OA\Schema(type="string", example="c4b9c8b7b2e34c7fb2a1f4a3d3d2f1a0")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Minimal broker authentication + signature payload + broker profile signing metadata",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"email","password","signature","representative","designation","stamp"},
     *
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 example="broker@example.com",
     *                 description="Broker email. Must match token recipient_email."
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 format="password",
     *                 example="StrongPassword123!",
     *                 description="Broker password for minimal authentication."
     *             ),
     *
     *             @OA\Property(
     *                 property="signature",
     *                 type="string",
     *                 example="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...",
     *                 description="Signature image as base64 string. Accepts data-URI (data:image/png;base64,...) or raw base64 (implementation-dependent)."
     *             ),
     *
     *             @OA\Property(
     *                 property="representative",
     *                 type="string",
     *                 maxLength=255,
     *                 example="John Doe",
     *                 description="Broker representative full name to be stored in broker profile."
     *             ),
     *             @OA\Property(
     *                 property="designation",
     *                 type="string",
     *                 maxLength=255,
     *                 example="Sales Director",
     *                 description="Representative designation/title to be stored in broker profile."
     *             ),
     *             @OA\Property(
     *                 property="stamp",
     *                 type="string",
     *                 example="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...",
     *                 description="Company stamp image as base64. Server decodes and stores privately, and saves resulting path into broker_profile.stamp_path. Only PNG/JPEG allowed. Recommended ≤ 2MB after decoding."
     *             ),
     *
     *             @OA\Property(
     *                 property="signed_at",
     *                 type="string",
     *                 format="date-time",
     *                 nullable=true,
     *                 example="2026-02-22T11:03:00+04:00",
     *                 description="Optional. If your SubmitSignatureAction supports client-sent timestamps; otherwise ignored."
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Signature submitted successfully (not finalized).",
     *         @OA\JsonContent(ref="#/components/schemas/SubmitSignatureOk")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials.",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden (not pending broker / token not owned by email / token invalid/used/expired)",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Token not found / invalid",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error (field errors).",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrors")
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error.",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     *
     * @throws Throwable
     */
    public function submitAgreementSignature(
        Request $request,
        string $token,
        SubmitSignatureAction $action,
    ) {
        $validator = Validator::make($request->all(), [
            'email'          => 'required|email',
            'password'       => 'required|string',
            'representative' => 'required|string|max:255',
            'designation'    => 'required|string|max:255',
            'stamp'          => 'required|string', // base64 image string
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // minimal authentication
        /** @var User|null $user */
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        // Role & status check
        $role = $user->getRoleNames()->first();
        if ($role !== 'Broker' || $user->status !== 'Pending') {
            return response()->json(['error' => 'Forbidden - user is not a pending Broker'], Response::HTTP_FORBIDDEN);
        }

        // Store broker agreement signing meta in broker profile
        $brokerProfile = $user->brokerProfile;

        if (!$brokerProfile) {
            return response()->json(['error' => 'Broker profile missing'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Decode + store stamp image (base64) and save path to broker profile
        $stampBase64 = trim((string) $request->input('stamp'));

        // Accept both "data:image/png;base64,..." and raw base64
        $mime = null;
        if (preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,/', $stampBase64, $m)) {
            $mime = strtolower($m[1]);
            $stampBase64 = substr($stampBase64, strlen($m[0]));
        }

        $stampBase64 = str_replace(["\r", "\n", " "], '', $stampBase64);
        $binary = base64_decode($stampBase64, true);

        if ($binary === false) {
            return response()->json(['error' => 'Invalid stamp image (base64)'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Allow only png/jpg/jpeg
        $ext = match ($mime) {
            'image/png'  => 'png',
            'image/jpeg', 'image/jpg' => 'jpg',
            default      => null,
        };

        // If no data-URI mime provided, try to detect from binary
        if ($ext === null) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $detected = strtolower((string) $finfo->buffer($binary));
            $ext = match ($detected) {
                'image/png'  => 'png',
                'image/jpeg' => 'jpg',
                default      => null,
            };
        }

        if ($ext === null) {
            return response()->json(['error' => 'Stamp must be a PNG or JPEG image'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Optional: basic size guard (2MB)
        if (strlen($binary) > (2 * 1024 * 1024)) {
            return response()->json(['error' => 'Stamp image is too large (max 2MB)'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Delete previous stamp if exists
        if (!empty($brokerProfile->stamp_path)) {
            Storage::disk('local')->delete($brokerProfile->stamp_path);
        }

        $stampName = 'stamp_' . now()->format('Ymd_His') . '.' . $ext;
        $stampPath = "stamps/brokers/{$user->id}/{$stampName}";

        Storage::disk('local')->put($stampPath, $binary);

        $brokerProfile->update([
            'representative' => $request->input('representative'),
            'designation'    => $request->input('designation'),
            'stamp_path'     => $stampPath,
        ]);

        $tokenHash = hash('sha256', $token);

        $link = SigningLink::query()
            ->where('token_hash', $tokenHash)
            ->where('document_type', DocumentType::BROKER_AGREEMENT->value)
            ->whereNull('signed_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->first();

        if (!$link) {
            return response()->json(['error' => 'Invalid or expired token'], Response::HTTP_NOT_FOUND);
        }

        if (strtolower(trim($link->recipient_email)) !== strtolower(trim($request->email))) {
            return response()->json(['error' => 'Forbidden - token does not belong to this email'], Response::HTTP_FORBIDDEN);
        }

        $cfg = new SignatureSubmitConfig(
            type: DocumentType::BROKER_AGREEMENT,
            expectedDocumentClass: UserDoc::class,
            signatureDir: 'signatures/broker_agreements',
            invalidDocMessage: 'Invalid document type for this endpoint.',
        );

        // Handle submit (no finalize here; finalize happens on approve)
        return $action->handle(
            $request,
            $token,
            $cfg
        );
    }

    /**
     * Withdraw a previously submitted Broker Agreement signature and re-issue a NEW signing link.
     *
     * This endpoint is used when an admin reviews a Broker’s submitted signature and decides it must be
     * re-submitted (e.g., unclear signature/stamp, incorrect details). The broker must not be approved yet.
     *
     * **What it does (transactional):**
     * 1) Validates admin permission `approve registration`.
     * 2) Loads the broker by `{userId}` and ensures role = Broker and status != Active.
     * 3) Finds the latest **SIGNED** `SigningLink` for `DocumentType::BROKER_AGREEMENT` for this broker email.
     * 4) Deletes the stored signature image.
     * 5) Marks that link as **WITHDRAWN**, clears signature fields, stores `withdraw_reason`, `withdrawn_by`, `withdrawn_at`,
     *    and expires it.
     * 6) Expires any other still-active (pending) broker agreement links for the same broker email.
     * 7) Sends a fresh signing link to the broker (new token/token_hash) with the provided message.
     *
     * @OA\Post(
     *     path="/brokers/{brokerUserId}/agreements/withdraw",
     *     operationId="withdrawBrokerAgreement",
     *     tags={"Brokers"},
     *     summary="Withdraw broker agreement signature and resend a new signing link",
     *     description="Withdraws the latest submitted broker agreement signature (if broker is not approved yet) and sends a new signing link for re-submission.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="Broker user ID",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Withdrawal reason/message that will be recorded and sent to the broker.",
     *         @OA\JsonContent(
     *             required={"reason"},
     *             @OA\Property(
     *                 property="reason",
     *                 type="string",
     *                 maxLength=1000,
     *                 example="Your signature/stamp is not clear. Please re-submit the agreement with a clear stamp and signature."
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Withdrawn successfully and a new signing link has been sent.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Broker agreement withdrawn and a new signing link has been sent.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - user lacks permission approve registration.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Forbidden")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Broker not found (or user is not a Broker).",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Broker not found.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity - broker already approved, no submitted signature exists, or invalid agreement document.",
     *         @OA\JsonContent(
     *             type="object",
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="error", type="string", example="Cannot withdraw: broker is already approved.")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="error", type="string", example="Broker has no submitted signature to withdraw.")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="error", type="string", example="Invalid broker agreement document.")
     *                 )
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Unexpected error occurred.")
     *         )
     *     )
     * )
     *
     * @throws \Throwable
     */
    public function withdrawBrokerAgreement(Request $request, int $userId)
    {
        $admin = $request->user();

        if (!$admin->can('approve registration')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            // 0) Load broker by userId
            $broker = User::query()
                ->lockForUpdate()
                ->find($userId);

            if (!$broker || !$broker->hasRole('Broker')) {
                DB::rollBack();
                return response()->json(['error' => 'Broker not found.'], Response::HTTP_NOT_FOUND);
            }

            // ✅ Only if not Approved
            if ($broker->status === 'Active') {
                DB::rollBack();
                return response()->json([
                    'error' => 'Cannot withdraw: broker is already approved.'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $email = strtolower(trim($broker->email));

            // 1) Find latest SIGNED broker agreement link for this broker
            /** @var SigningLink|null $link */
            $link = SigningLink::query()
                ->lockForUpdate()
                ->where('document_type', DocumentType::BROKER_AGREEMENT->value)
                ->whereRaw('LOWER(TRIM(recipient_email)) = ?', [$email])
                ->whereNotNull('signed_at')
                ->whereNotNull('signature_image_path')
                ->where('status', SigningLink::STATUS_SIGNED) // إذا عندك
                ->orderByDesc('signed_at')
                ->first();

            if (!$link) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Broker has no submitted signature to withdraw.'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // 2) documentable should be UserDoc and belongs to this broker
            $agreementDoc = $link->documentable;

            if (!$agreementDoc instanceof UserDoc) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Invalid broker agreement document.'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ((int) $agreementDoc->user_id !== (int) $broker->id) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Agreement does not belong to this broker.'
                ], Response::HTTP_FORBIDDEN);
            }

            // 3) Delete old signature image file
            if ($link->signature_image_path && Storage::disk('local')->exists($link->signature_image_path)) {
                Storage::disk('local')->delete($link->signature_image_path);
            }

            // 4) Reset signature fields on the old link + mark status withdrawn
            $link->forceFill([
                'signature_image_path' => null,
                'signed_at'            => null,
                'status'               => SigningLink::STATUS_WITHDRAWN,
                'withdraw_reason'      => $request->reason,
                'withdrawn_by'         => $admin->id,
                'withdrawn_at'         => now(),
                'expires_at'           => now(), // invalidate
            ])->save();

            // 5) Expire any other pending links to avoid multiple active links
            SigningLink::query()
                ->where('document_type', DocumentType::BROKER_AGREEMENT->value)
                ->whereRaw('LOWER(TRIM(recipient_email)) = ?', [$email])
                ->whereNull('signed_at')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                })
                ->update([
                    'status'     => SigningLink::STATUS_EXPIRED,
                    'expires_at' => now(),
                ]);

            // 6) Send new signing link (fresh token_hash) to the broker
            $signatureService = app(DocumentSignatureService::class);

            $signatureService->send(
                signable: $link->signable,
                documentable: $agreementDoc,
                type: DocumentType::BROKER_AGREEMENT,
                recipients: [[
                    'email' => $broker->email,
                    'name'  => $broker->name,
                ]],
                customMessage: $request->reason,
            );

            DB::commit();

            return response()->json([
                'message' => 'Broker agreement withdrawn and a new signing link has been sent.',
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Withdraw broker agreement failed', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show broker stamp image.
     *
     * - Requires authenticated user.
     * - Requires `view users` permission.
     * - Only works for users with role `Broker`.
     * - Streams the stored broker stamp image from broker profile.
     *
     * @OA\Get(
     *     path="/brokers/{userId}/stamp",
     *     operationId="showBrokerStamp",
     *     tags={"Brokers"},
     *     summary="Show broker stamp image",
     *     description="Streams the broker stamp image stored in broker profile (stamp_path).",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="Broker user ID",
     *         @OA\Schema(type="integer", example=298)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Broker stamp image streamed successfully",
     *         @OA\MediaType(
     *             mediaType="image/png"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - missing permission view users",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Forbidden")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Broker not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="User is not a broker",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="User doesn't exist or not a broker!")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Stamp image missing or could not be streamed",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function showStamp(Request $request,int $userId)
    {
        $authUser = $request->user();
        Log::info("User {$authUser->id} requested stamp of broker ID: {$userId}.");

        if (!$authUser->can('view users')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $broker = User::findOrFail($userId);

        if (!$broker || !$broker->hasRole('Broker')) {
            return response()->json(['error' => "User doesn't exist or not a broker!"], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $path = $broker->brokerProfile->stamp_path;
        return app(ImageService::class)
            ->streamImage($request, $path, true);
    }

    /**
     * Shared guard + loader for broker agreement endpoints.
     *
     * @return array{adminUser: User, broker: User}|\Illuminate\Http\JsonResponse
     */
    private function getPendingBrokerAgreementContext(Request $request, int $brokerUserId): array|\Illuminate\Http\JsonResponse
    {
        $adminUser = $request->user();

        // ✅ Permission for staff
        if (!$adminUser || !$adminUser->can('generate one-time link')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        /** @var User|null $broker */
        $broker = User::with(['brokerProfile', 'docs'])->find($brokerUserId);

        if (!$broker) {
            return response()->json(['error' => 'Broker not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$broker->hasRole('Broker')) {
            return response()->json(['error' => 'User is not a Broker'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($broker->status !== 'Pending') {
            // generic message; endpoints may override with specific ones
            return response()->json(['error' => 'Broker must be Pending'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$broker->brokerProfile) {
            return response()->json(['error' => 'Broker profile missing'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return [
            'adminUser' => $adminUser,
            'broker'    => $broker,
        ];
    }

    /**
     * @OA\Get(
     *     path="/brokers/{user}/bookings",
     *     summary="Get all broker bookings with lightweight commission summary",
     *     operationId="getBrokerBookings",
     *     tags={"Brokers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="Broker user ID",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64", example=42)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(response=200, description="Broker bookings fetched successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function brokerBookings(Request $request, User $user)
    {
        $authUser = $request->user();

        if (!$authUser || !$authUser->can('view broker bookings')) {
            return response()->json([
                'error' => 'Forbidden',
            ], Response::HTTP_FORBIDDEN);
        }

        if (!$user->hasRole('Broker')) {
            return response()->json([
                'error' => 'User is not a Broker.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validator = Validator::make($request->all(), [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $validator->validated();
        $perPage = (int) ($validated['per_page'] ?? 15);

        $bookings = Booking::query()
            ->where('sale_source_id', $user->id)
            ->with([
                'unit:id,unit_no,building_id',
                'unit.building:id,name',
                'brokerCommission:id,booking_id,broker_user_id,commission_rate_id,commission_percentage,base_amount,commission_amount,paid_amount,remaining_amount,status,eligible_at,calculated_at',
            ])
            ->orderByDesc('booked_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        $bookings->getCollection()->transform(function (Booking $booking) {
            return [
                'id' => $booking->id,
                'unit_id' => $booking->unit_id,
                'sale_source_id' => $booking->sale_source_id,
                'price' => $booking->price,
                'status' => $booking->status,
                'booked_at' => $booking->booked_at,
                'unit' => $booking->unit ? [
                    'id' => $booking->unit->id,
                    'unit_no' => $booking->unit->unit_no,
                    'building' => $booking->unit->building ? [
                        'id' => $booking->unit->building->id,
                        'name' => $booking->unit->building->name,
                    ] : null,
                ] : null,
                'commission_summary' => $booking->brokerCommission ? [
                    'id' => $booking->brokerCommission->id,
                    'commission_percentage' => (float) $booking->brokerCommission->commission_percentage,
                    'base_amount' => (float) $booking->brokerCommission->base_amount,
                    'commission_amount' => (float) $booking->brokerCommission->commission_amount,
                    'paid_amount' => (float) $booking->brokerCommission->paid_amount,
                    'remaining_amount' => (float) $booking->brokerCommission->remaining_amount,
                    'status' => $booking->brokerCommission->status,
                    'eligible_at' => $booking->brokerCommission->eligible_at,
                    'calculated_at' => $booking->brokerCommission->calculated_at,
                ] : null,
            ];
        });

        $summary = BrokerCommission::query()
            ->where('broker_user_id', $user->id)
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('COALESCE(SUM(commission_amount), 0) as total_commission_amount')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as total_paid_amount')
            ->selectRaw('COALESCE(SUM(remaining_amount), 0) as total_remaining_amount')
            ->selectRaw("SUM(CASE WHEN status = 'due' THEN 1 ELSE 0 END) as due_count")
            ->selectRaw("SUM(CASE WHEN status = 'partially_paid' THEN 1 ELSE 0 END) as partially_paid_count")
            ->selectRaw("SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count")
            ->selectRaw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count")
            ->first();

        return response()->json([
            'broker' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'summary' => [
                'total_count' => (int) ($summary->total_count ?? 0),
                'total_commission_amount' => (float) ($summary->total_commission_amount ?? 0),
                'total_paid_amount' => (float) ($summary->total_paid_amount ?? 0),
                'total_remaining_amount' => (float) ($summary->total_remaining_amount ?? 0),
                'due_count' => (int) ($summary->due_count ?? 0),
                'partially_paid_count' => (int) ($summary->partially_paid_count ?? 0),
                'paid_count' => (int) ($summary->paid_count ?? 0),
                'cancelled_count' => (int) ($summary->cancelled_count ?? 0),
            ],
            'bookings' => $bookings,
        ], Response::HTTP_OK);
    }

    public function uploadSignedAgreement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'            => 'required|email',
            'password'         => 'required|string',
            'signed_agreement' => 'required|file|mimes:pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();

        try {
            // Minimal authentication
            $user = User::where('email', $request->email)->first();
            if (!$user || !Hash::check($request->password, $user->password)) {
                DB::rollBack();
                return response()->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
            }

            // Role & status check
            $role = $user->getRoleNames()->first();
            if ($role !== 'Broker' || $user->status !== 'Pending') {
                DB::rollBack();
                return response()->json(['error' => 'Forbidden - user is not a pending Broker'], Response::HTTP_FORBIDDEN);
            }

            // 1. If there's an existing signed agreement, delete its file first
            $existing = $user->docs()->where('doc_type', 'signed_agreement')->first();
            if ($existing) {
                Storage::disk('local')->delete($existing->file_path);
            }

            // 2. Store the new file
            $file       = $request->file('signed_agreement');
            $fileName   = "signed_agreement_{$user->id}." . $file->getClientOriginalExtension();
            $filePath   = $file->storeAs('agreements/signed', $fileName, 'local');

            // 3. Update or create the record
            if ($existing) {
                $existing->update(['file_path' => $filePath]);
                $doc     = $existing;
                $message = 'Signed agreement replaced successfully';
            } else {
                $doc = $user->docs()->create([
                    'doc_type'  => 'signed_agreement',
                    'file_path' => $filePath,
                ]);
                $message = 'Signed agreement uploaded successfully';
            }

            DB::commit();

            // Build public URL
            $docUrl = asset("storage/{$filePath}");

            return response()->json([
                'id'       => $doc->id,
                'message'  => $message,
            ], Response::HTTP_OK);

        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json([
                'error'   => 'Server error',
                'message' => $ex->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
