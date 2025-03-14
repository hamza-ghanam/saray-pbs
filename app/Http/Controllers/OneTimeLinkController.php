<?php

namespace App\Http\Controllers;


use App\Models\OneTimeLink;
use App\Models\User;

// or a separate Broker/Contractor model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class OneTimeLinkController extends Controller
{
    /**
     * Generate a one-time link for a Broker or Contractor.
     *
     * @OA\Post(
     *     path="/otls/generate",
     *     summary="Generate a one-time link for Broker or Contractor",
     *     tags={"OneTimeLink"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_type"},
     *             @OA\Property(property="user_type", type="string", example="Broker", description="Either 'Broker' or 'Contractor'"),
     *             @OA\Property(property="expires_in_hours", type="integer", example=48, description="Optional number of hours until link expires")
     *         )
     *     ),
     *     @OA\Response(response=201, description="One-time link created"),
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
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $validator->validated();

        // Generate a random token
        $token = Str::random(32);

        $otl = OneTimeLink::create([
            'token' => $token,
            'user_type' => $data['user_type'], // "Broker" or "Contractor"
            'expired_at' => null, // link is valid until used
        ]);

        // Return the OTL or a URL containing the token

        $url = url("/api/otls/register?token={$token}");

        return response()->json([
            'message' => 'One-time link generated successfully',
            'otl' => $otl
        ], Response::HTTP_CREATED);
    }

    /**
     * Register a new Broker/Contractor using a one-time link token.
     *
     * The user must provide matching password and password_confirmation fields.
     * If the token is already used or invalid, an error is returned.
     *
     * @OA\Post(
     *     path="/otls/register",
     *     summary="Register using a one-time link token",
     *     tags={"OneTimeLink"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"token", "name", "email", "docs", "password", "password_confirmation"},
     *                 @OA\Property(
     *                     property="token",
     *                     type="string",
     *                     description="The OTL token from the link",
     *                     example="ab12cd34ef56gh78ij90klmn1234op56"
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     maxLength=255,
     *                     description="User's full name",
     *                     example="John Doe"
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     format="email",
     *                     description="User's email",
     *                     example="john.doe@example.com"
     *                 ),
     *                 @OA\Property(
     *                     property="docs",
     *                     type="string",
     *                     format="binary",
     *                     description="Uploaded docs (pdf, zip, jpg, jpeg, png)"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string",
     *                     format="password",
     *                     description="User's password (must be at least 6 chars)",
     *                     example="secret123"
     *                 ),
     *                 @OA\Property(
     *                     property="password_confirmation",
     *                     type="string",
     *                     format="password",
     *                     description="Must match 'password' field",
     *                     example="secret123"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully, status = Pending",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Broker registered successfully, awaiting approval"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 description="Newly created user record",
     *                 @OA\Property(property="id", type="integer", example=15),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *                 @OA\Property(property="docs", type="string", example="docs/abc123.pdf"),
     *                 @OA\Property(property="status", type="string", example="Pending"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-10T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-10T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid or already used token"),
     *     @OA\Response(response=422, description="Validation error (e.g., password mismatch, file not valid, etc.)")
     * )
     */
    public function registerUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|string|min:6',
            // The 'confirmed' rule requires a matching 'password_confirmation' field
            'docs' => 'required|file|mimes:pdf,zip,jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $validator->validated();

        // 1. Find the OneTimeLink by token
        $otl = OneTimeLink::where('token', $data['token'])->first();
        if (!$otl) {
            return response()->json(['error' => 'Invalid token'], Response::HTTP_BAD_REQUEST);
        }

        Log::info("One-time link registration attempt. OTL ID: {$otl->id}");

        // 2. Check expiration or if already used (soft deleted or expired_at in past)
        if (!is_null($otl->expired_at)) {
            return response()->json(['error' => 'This link has already been used'], 400);
        }

        // 3. Check user_type => "Broker" or "Contractor"
        $userType = $otl->user_type;

        // 4. Upload docs
        $docsFile = $request->file('docs');
        $docsPath = $docsFile->store('docs', 'public');

        // 5. Create user with role "Broker" or "Contractor"
        $password = $data['password']; // If password is optional
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($password),
            'docs' => $docsPath, // store docs path in users table or separate table
            'status' => 'Pending', // This user remains "Pending" until an admin approves
        ]);

        // 6. Assign role based on user_type
        $user->assignRole($userType);

        // 7. Mark the OneTimeLink as used (soft delete or set expired_at = now())
        $otl->expired_at = now();
        $otl->save();

        // 8. Return success response
        return response()->json([
            'message' => "{$userType} registered successfully, awaiting approval",
            'user' => $user
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

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

        return response()->json([
            'message' => 'User approved successfully',
            'user' => $user
        ], Response::HTTP_OK);
    }
}
