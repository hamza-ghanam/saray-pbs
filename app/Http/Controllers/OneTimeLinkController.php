<?php

namespace App\Http\Controllers;


use App\Actions\Signature\FinalizeConfig;
use App\Actions\Signature\FinalizeSignedDocumentService;
use App\Enums\DocumentType;
use App\Mail\OneTimeLinkMail;
use App\Models\OneTimeLink;
use App\Models\SigningLink;
use App\Models\User;
use App\Models\UserDoc;
use App\Services\DocumentSignatureService;
use App\Services\PdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Random\RandomException;
use Symfony\Component\HttpFoundation\Response;

class OneTimeLinkController extends Controller
{
    /**
     * @OA\Get(
     *     path="/otls",
     *     summary="List all One-Time Links",
     *     description="Returns a paginated list of all One-Time Links along with their associated user (if exists).",
     *     operationId="getOTLs",
     *     tags={"OneTime Links"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="token", type="string"),
     *                     @OA\Property(property="user_type", type="string"),
     *                     @OA\Property(property="expired_at", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="user", type="object", nullable=true,
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="email", type="string")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="per_page", type="integer", example=10),
     *             @OA\Property(property="total", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        Log::info("User {$user->id} requested OTL listing.");

        if (!$user->can('manage broker') || !$user->can('manage contractor')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        $limit = min((int)$request->get('limit', 10), 100);

        $otls = OneTimeLink::with('user')
            ->latest()      // defaults to ordering by created_at DESC
            ->paginate($limit);

        return response()->json($otls, Response::HTTP_OK);
    }


    /**
     * Generate a one-time link for a Broker or Contractor.
     *
     * @OA\Post(
     *     path="/otls/generate",
     *     summary="Generate a one-time link for Broker or Contractor",
     *     tags={"OneTime Links"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_type","email"},
     *             @OA\Property(
     *                 property="user_type",
     *                 type="string",
     *                 example="Broker",
     *                 description="Either 'Broker' or 'Contractor'",
     *                 enum={"Broker","Contractor"}
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 example="myemail@hotmail.com",
     *                 description="Email address that will receive the one-time registration link."
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="One-time link successfully generated and shared by email.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="One-time link successfully generated and shared by email."),
     *             @OA\Property(property="email", type="string", format="email", example="myemail@hotmail.com"),
     *             @OA\Property(property="user_type", type="string", example="Broker", enum={"Broker","Contractor"})
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden (user lacks permission)",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Forbidden")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={"email":{"The email field is required."},"user_type":{"The selected user_type is invalid."}}
     *             )
     *         )
     *     )
     * )
     * @throws RandomException
     */
    public function generateLink(Request $request)
    {
        $user = $request->user();
        Log::info("User {$user->id} is generating a one-time link.");

        // If you have permission logic:
        if (!$user->can('generate one-time link')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'user_type' => 'required|string|in:Broker,Contractor',
            'email'     => 'required|email|max:255|unique:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $validator->validated();

        // Generate a random token
        do {
            $plainToken = bin2hex(random_bytes(32));
            $hash = hash('sha256', $plainToken);
            $exists = OneTimeLink::where('token', $hash)->exists();
        } while ($exists);

        $otl = OneTimeLink::create([
            'user_type'  => $data['user_type'], // "Broker" or "Contractor"
            'expired_at' => null,                // valid until used
        ]);

        $otlUrl = rtrim(config('app.frontend_url'), '/')
            . '/one-time-links/register/'
            . $data['user_type']
            . '/'
            . $otl->plain_token;

        //Email
        Mail::to($request->email)->queue(new OneTimeLinkMail(otlUrl: $otlUrl));

        /*
        $mailer = new BrevoMailer();
        $mailer->sendView(
            to:    [['email' => $request->email]],
            subject: 'Your One-Time Access Link',
            view: 'emails.otl',   // Blade view under resources/views/emails
            data: ['otl' => $otl]
        );
        */

        return response()->json([
            'message' => 'One-time link successfully generated and shared by email.',
            'email'     => $request->email,
            'user_type' => $otl->user_type,
        ], Response::HTTP_CREATED);
    }

    /**
     * Register a new Broker or Contractor using a one-time link token.
     *
     * - **Broker** must upload:
     *   - rera_cert, trade_license, bank_account, tax_registration
     *   - broker_profile[license_number], broker_profile[rera_registration_number], broker_profile[address], broker_profile[po_box], broker_profile[telephone]
     * - **Contractor** must upload:
     *   - contract, trade_license, scope_of_work
     *
     * An agreement PDF will be generated and returned (for Brokers).
     *
     * @OA\Post(
     *     path="/otls/register",
     *     summary="Register a Broker or Contractor via one-time link token",
     *     tags={"OneTime Links"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"token","name","email","password","password_confirmation"},
     *                 @OA\Property(property="token", type="string", description="OneTimeLink token", example="ab12cd34ef56gh78ij90klmn1234op56"),
     *                 @OA\Property(property="name", type="string", maxLength=255, example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *                 @OA\Property(property="password", type="string", format="password", minLength=6, example="strongPass123"),
     *                 @OA\Property(property="password_confirmation", type="string", format="password", example="strongPass123"),
     *                 @OA\Property(property="rera_cert",        type="string", format="binary", description="Broker: RERA certificate"),
     *                 @OA\Property(property="trade_license",    type="string", format="binary", description="Trade license"),
     *                 @OA\Property(property="bank_account",     type="string", format="binary", description="Broker: bank account proof"),
     *                 @OA\Property(property="tax_registration", type="string", format="binary", description="Broker: tax registration proof"),
     *                 @OA\Property(property="contract",      type="string", format="binary", description="Contractor: contract document"),
     *                 @OA\Property(property="scope_of_work", type="string", format="binary", description="Contractor: scope of work"),
     *                 @OA\Property(property="broker_profile[license_number]",           type="string", description="Broker’s license number"),
     *                 @OA\Property(property="broker_profile[rera_registration_number]", type="string", description="Broker’s RERA registration number"),
     *                 @OA\Property(property="broker_profile[address]",                  type="string", description="Broker’s address"),
     *                 @OA\Property(property="broker_profile[po_box]",                   type="string", description="Broker’s PO Box"),
     *                 @OA\Property(property="broker_profile[telephone]",                type="string", description="Broker’s telephone number")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Contractor registered successfully, awaiting approval"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 description="Created user record",
     *                 @OA\Property(property="id",         type="integer", example=15),
     *                 @OA\Property(property="name",       type="string",  example="John Doe"),
     *                 @OA\Property(property="email",      type="string",  format="email", example="john.doe@example.com"),
     *                 @OA\Property(property="status",     type="string",  example="Pending"),
     *                 @OA\Property(property="created_at", type="string",  format="date-time", example="2025-03-10T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string",  format="date-time", example="2025-03-10T12:00:00Z")
     *             ),
     *             @OA\Property(
     *                 property="docs",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="doc_id",     type="integer", example=42),
     *                     @OA\Property(property="doc_type",   type="string",  example="rera_cert"),
     *                     @OA\Property(property="created_at", type="string",  format="date-time", example="2025-07-01T09:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string",  format="date-time", example="2025-07-01T09:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid or already used token / Invalid user_type"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function selfRegisterUser(
        Request $request,
        PdfService $pdfService,
        DocumentSignatureService $signatureService
    ):JsonResponse
    {
        // 1. Basic validation (we'll add doc rules after we get user_type from OTL)
        $baseData = $request->validate([
            'token' => 'required|string',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $plainToken = trim($baseData['token']);

        // 2. Retrieve OneTimeLink by token

        $otl = OneTimeLink::query()
            ->whereNull('expired_at')
            ->get()
            ->first(fn ($otl) => $otl->matchesPlainToken($plainToken));

        if (!$otl) {
            return response()->json(['error' => 'Invalid or expired token'], Response::HTTP_BAD_REQUEST);
        }

        // 3. Check user_type => "Broker" or "Contractor"
        $userType = $otl->user_type; // e.g. "Broker" or "Contractor"

        // Prepare additional doc rules
        if ($userType === 'Broker') {
            $rules = [
                'rera_cert' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'trade_license' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'bank_account' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'tax_registration' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',

                // broker_profile
                'broker_profile.license_number' => 'required|string',
                'broker_profile.rera_registration_number' => 'required|string',
                'broker_profile.address' => 'required|string',
                'broker_profile.po_box' => 'required|string',
                'broker_profile.telephone' => 'required|string',
            ];
        } elseif ($userType === 'Contractor') {
            $rules = [
                'contract' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048|max:2048',
                'trade_license' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048|max:2048',
                'scope_of_work' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048|max:2048',
            ];
        } else {
            return response()->json(['error' => 'Invalid user_type in OTL'], Response::HTTP_BAD_REQUEST);
        }

        $validated = array_merge($baseData, $request->validate($rules));

        try {
            $created = DB::transaction(function () use ($request, $validated, $otl, $userType, $pdfService) {
                // 4. Create the user
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'status' => 'Pending', // waiting for admin approval
                ]);

                // 5. Assign role
                $user->assignRole($userType);

                // 6. Upload docs and store them in user_docs (one doc per record)
                if ($userType === 'Broker') {
                    foreach (['rera_cert', 'trade_license', 'bank_account', 'tax_registration'] as $docType) {
                        $path = $request->file($docType)->store('docs', 'local');
                        $user->docs()->create(['doc_type' => $docType, 'file_path' => $path]);
                    }

                    $user->brokerProfile()->create($validated['broker_profile']);
                } else { // Contractor
                    foreach (['contract', 'trade_license', 'scope_of_work'] as $docType) {
                        $path = $request->file($docType)->store('docs', 'local');
                        $user->docs()->create(['doc_type' => $docType, 'file_path' => $path]);
                    }
                }

                // 7. Mark the OTL as used
                $otl->update([
                    'expired_at' => now(),
                    'user_id' => $user->id
                ]);

                return $user->fresh();
            });

            return response()->json([
                'message'   => $userType === 'Broker'
                    ? 'Broker registered successfully. Your details are under review. We will email the agreement for e-signature once verified.'
                    : 'Contractor registered successfully, awaiting approval.',
                'email'     => $created->email,
                'user_type' => $userType,
                'status'    => $created->status,
            ], Response::HTTP_CREATED);
        } catch (\Throwable $ex) {
            Log::error('selfRegisterUser failed: ' . $ex->getMessage());
            return response()->json(['error' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Approve a user's registration (from Pending to Active).
     *
     * @OA\Post(
     *     path="/users/{id}/approve",
     *     summary="Approve user registration",
     *     tags={"User Management","Brokers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the user to approve",
     *         required=true,
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User approved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="User approved successfully"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 description="Approved user record",
     *                 @OA\Property(property="id", type="integer", example=42),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *                 @OA\Property(property="status", type="string", example="Active")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden (no permission to approve registration)"),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=422, description="User is not in pending status")
     * )
     */
    public function approve(Request $request, $userId, FinalizeSignedDocumentService $finalizer)
    {
        $admin_user = $request->user()->loadMissing('signature');
        if (!$admin_user->can('approve registration')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $signaturePath = $admin_user->signature?->is_active
            ? $admin_user->signature->absolutePath()
            : null;

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if ($user->status !== 'Pending') {
            return response()->json(['error' => 'User is not in pending status'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $role = $user->getRoleNames()->first();

        $docPath = null;
        if ($role === 'Broker') {
            // Find latest signed link for broker agreement for this broker email
            $link = SigningLink::query()
                ->where('document_type', DocumentType::BROKER_AGREEMENT->value)
                ->whereRaw('LOWER(TRIM(recipient_email)) = ?', [strtolower(trim($user->email))])
                ->whereNotNull('signed_at')
                ->whereNotNull('signature_image_path')
                ->orderByDesc('signed_at')
                ->first();

            if (!$link) {
                return response()->json([
                    'error' => 'Broker must sign the agreement before approval.'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $agreement = $link->documentable; // morph relation
            if (!$agreement instanceof UserDoc) {
                return response()->json([
                    'error' => 'Invalid broker agreement document.'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Ensure this agreement belongs to this broker
            if ((int) $agreement->user_id !== (int) $user->id) {
                return response()->json([
                    'error' => 'Agreement does not belong to this broker.'
                ], Response::HTTP_FORBIDDEN);
            }

            $finalizeConfig = $this->brokerAgreementFinalizeConfig();

            $finalisedPath = $finalizer->finalizeBrokerAgreementIfComplete($agreement, $finalizeConfig, $admin_user, $signaturePath, $link);

            if (!$finalisedPath) {
                return response()->json([
                    'error' => 'Broker agreement is not fully signed or could not be finalised.'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

           $docPath = $finalisedPath;

            $user->brokerProfile()->update([
               'approved_at' => now(),
            ]);
        }

        // ✅ Activate user
        $user->status = 'Active';
        $user->save();

        Mail::to($user->email)->queue(new OneTimeLinkMail(user: $user, documentPath: $docPath));

        return response()->json([
            'message' => 'User approved successfully',
            'user'    => $user
        ], Response::HTTP_OK);
    }
    /**
     * Returns FinalizeConfig for broker agreement with proper persist strategy.
     */
    private function brokerAgreementFinalizeConfig(): FinalizeConfig
    {
        $cfg = new FinalizeConfig(
            type: DocumentType::BROKER_AGREEMENT,
            view: 'pdf.broker_agreement',
            signedDir: 'agreements/brokers/signed',
            filePrefix: 'BROKER_AGREEMENT_SIGNED_FINAL_',
        );

        // Persist strategy: create NEW UserDoc (doc_type = signed_agreement)
        $cfg->persist = function (UserDoc $agreementDoc, string $path, $finalSignedAt, FinalizeConfig $cfg) {
            $brokerId = $agreementDoc->user_id;

            // Replace previous signed agreement (matches old behaviour)
            $existing = UserDoc::query()
                ->where('user_id', $brokerId)
                ->where('doc_type', 'signed_agreement')
                ->first();

            if ($existing) {
                Storage::disk('local')->delete($existing->file_path);
                $existing->delete();
            }

            UserDoc::create([
                'user_id'   => $brokerId,
                'doc_type'  => 'signed_agreement',
                'file_path' => $path,
            ]);
        };

        return $cfg;
    }

    // To be deleted..
    public function downloadAgreement(Request $request, User $user)
    {
        $authUser = $request->user();
        $type = $request->input('type', 'agreement');

        // 2. Permission check
        abort_if(
            (
                ! $authUser->hasRole('Broker')
                && ! $authUser->can('manage broker')
            )
            || (
                $authUser->hasRole('Broker')
                && $user->id !== $authUser->id
            ),
            Response::HTTP_FORBIDDEN
        );

        $target = $authUser->hasRole('Broker')
            ? $authUser
            : $user;

        $localPath = $type === 'signed_agreement' ? 'local' : 'public';

        // Find the document
        $agreementDoc = $target
            ->docs()
            ->where('doc_type', $type)
            ->first();

        if (! $agreementDoc) {
            return response()->json(['message' => 'Agreement not found'], Response::HTTP_NOT_FOUND);
        }

        Log::info("User {$authUser->id} downloading {$type} of broker {$authUser->name}");

        $ext      = pathinfo($agreementDoc->file_path, PATHINFO_EXTENSION);
        $filename = "agreement_{$target->name}.{$ext}";

        return Storage::disk($localPath)->download($agreementDoc->file_path, $filename);
    }
}
