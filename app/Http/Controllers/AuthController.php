<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ], 201);
    }


    /**
     * Authenticate a user and return an API token.
     *
     * @OA\Post(
     *     path="/auth/login",
     *     summary="User Login",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="securepassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="token", type="string", example="1|A23sj9ds89d7a9sd87as"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="user@example.com"),
     *                 @OA\Property(property="role", type="string", example="Sales"),
     *                 @OA\Property(property="status", type="string", example="Active")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=403, description="Account is inactive"),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::where('email', $request->email)->first();

        // Check credentials
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->status !== 'Active') {
            if ($user->status === 'Pending') {
                // Check user role
                $role = $user->getRoleNames()->first();

                // If user is a Broker, check if they have doc_type = "signed_agreement"
                if ($role === 'Broker') {
                    $hasSignedAgreement = $user->docs()->where('doc_type', 'signed_agreement')->exists();

                    if (!$hasSignedAgreement) {
                        // They haven't uploaded the signed agreement yet
                        return response()->json(['error' => 'Please sign the agreement and upload it back'], Response::HTTP_FORBIDDEN);
                    } else {
                        // They have uploaded the signed agreement, but still pending admin approval
                        return response()->json(['error' => 'Your account is pending approval'], Response::HTTP_FORBIDDEN);
                    }
                }

                // For other roles with pending status, just return "pending approval" or a similar message
                return response()->json(['error' => 'Your account is pending approval'], Response::HTTP_FORBIDDEN);
            }

            // If status is "Inactive"
            return response()->json(['error' => 'Account is inactive'], Response::HTTP_FORBIDDEN);
        }

        // Generate token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
                'status' => $user->status,
            ]
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     summary="User Logout",
     *     description="Logs out the authenticated user by revoking their current access token.",
     *     operationId="logoutUser",
     *     tags={"Authentication"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logged out successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        // Retrieve the authenticated user
        $user = $request->user();

        // Revoke the current access token
        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully'], Response::HTTP_OK);
    }
}

