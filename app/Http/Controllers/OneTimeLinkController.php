<?php

namespace App\Http\Controllers;


use App\Mail\BrokerAgreementMail;
use App\Mail\OneTimeLinkMail;
use App\Mail\SalesPurchaseAgreementMail;
use App\Models\OneTimeLink;
use App\Models\User;
use App\Services\BrevoMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Mail;

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
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_type"},
     *             @OA\Property(property="user_type", type="string", example="Broker", description="Either 'Broker' or 'Contractor'"),
     *             @OA\Property(property="email", type="string", example="myemail@hotmail.com", description="User email to share the OTL via it.")
     *         )
     *     ),
     *     @OA\Response(response=201, description="One-time link successfully generated and shared by email."),
     *     @OA\Response(response=403, description="Forbidden (user lacks permission)"),
     *     @OA\Response(response=422, description="Validation error")
     * )
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
            $token = Str::random(32);
            $exists = OneTimeLink::where('token', $token)->exists();
        } while ($exists);

        $otl = OneTimeLink::create([
            'token' => $token,
            'user_type' => $data['user_type'], // "Broker" or "Contractor"
            'expired_at' => null, // link is valid until used
        ]);

        //Email
        Mail::to($request->email)->queue(new OneTimeLinkMail($otl));

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
            'booking' => $otl
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
    public function selfRegisterUser(Request $request): \Illuminate\Http\JsonResponse
    {
        // 1. Basic validation (we'll add doc rules after we get user_type from OTL)
        $baseData = $request->validate([
            'token' => 'required|string',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // 2. Retrieve OneTimeLink by token
        $otl = OneTimeLink::where('token', $baseData['token'])->first();

        if (!$otl) {
            return response()->json(['error' => 'Invalid token'], Response::HTTP_BAD_REQUEST);
        }
        if (!is_null($otl->expired_at)) {
            return response()->json(['error' => 'This link has already been used'], Response::HTTP_BAD_REQUEST);
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

        DB::beginTransaction();
        try {
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
            $otl->update(['expired_at' => now(), 'user_id' => $user->id]);

            $respData = [
                'user' => $user,
            ];

            if ($userType === 'Broker') {
                // 8. Generate the agreement PDF
                //    (assuming you have a Blade view "pdf.agreement" that needs user data)
                $pdf = PDF::loadView('pdf.broker_agreement', [
                    'user' => $user,
                    'userType' => $userType
                ]);
                $pdfContent = $pdf->output();
                $pdfName = "agreement_{$user->id}.pdf";
                $user->docs()->create([
                    'doc_type' => 'agreement',
                    'file_path' => "agreements/{$pdfName}", // storing the relative path
                ]);

                // 2. Store the PDF content in "local" disk
                Storage::disk('local')->put("agreements/{$pdfName}", $pdfContent);

                DB::commit();

                $docsCollection = $user->docs()->get(); // now this is a Collection
                $docs = $docsCollection->map(function ($doc) {
                    return [
                        'doc_id' => $doc->id,
                        'doc_type' => $doc->doc_type,
                        'created_at' => $doc->created_at,
                        'updated_at' => $doc->updated_at,
                    ];
                });

                $respData += [
                    'message' => 'Broker agreement emailed to the broker successfully.',
                    'docs' => $docs
                ];

                // Email
                Mail::to($validated['email'])->queue(new BrokerAgreementMail($user, $pdfName));
            } else {
                DB::commit();

                $respData += [
                    'message' => "Contractor registered successfully, awaiting approval",
                    'docs' => $user->docs,
                ];
            }

            // 3. Return success + doc ID
            return response()->json($respData, Response::HTTP_CREATED);

        } catch (\Exception $ex) {
            DB::rollback();
            return response()->json(['error' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Approve a user's registration (from Pending to Active).
     *
     * @OA\Post(
     *     path="/users/{id}/approve",
     *     summary="Approve user registration",
     *     tags={"User Management"},
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
    public function approve(Request $request, $userId)
    {
        $admin_user = $request->user();
        if (!$admin_user->can('approve registration')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if ($user->status !== 'Pending') {
            return response()->json(['error' => 'User is not in pending status'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->status = 'Active';
        $user->save();

        Mail::to($user->email)->queue(new OneTimeLinkMail(null, $user));

        return response()->json([
            'message' => 'User approved successfully',
            'user' => $user
        ], Response::HTTP_OK);
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
