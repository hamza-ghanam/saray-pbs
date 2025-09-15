<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ForgotPasswordController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/forgot-password",
     *     summary="Send password reset link to user's email",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset link sent.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password reset link sent.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unable to send reset link"
     *     )
     * )
     */
    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::firstWhere('email', $request->email);

        if (!$user) {
            return response()->json(['message' => 'User Not Found!'], Response::HTTP_NOT_FOUND);
        }

        Mail::to($user->email)->queue(new ResetPasswordEmail($user));

        return response()->json(['message' => 'Password reset email sent!'], Response::HTTP_OK);
    }
}
