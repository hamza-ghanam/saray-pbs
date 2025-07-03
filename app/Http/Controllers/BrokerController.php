<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class BrokerController extends Controller
{

    /**
     * Upload or replace a signed agreement for a pending Broker.
     *
     * This endpoint allows a user with role="Broker" and status="Pending"
     * to authenticate via email/password (minimal check) and upload or replace
     * their signed agreement (doc_type="signed_agreement"). Only one agreement
     * record exists per user; uploading again replaces the existing one.
     *
     * @OA\Post(
     *     path="/brokers/upload-signed-agreement",
     *     summary="Upload or replace signed agreement for a pending Broker",
     *     tags={"Brokers"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"email", "password", "signed_agreement"},
     *                 @OA\Property(property="email", type="string", format="email", example="broker@example.com"),
     *                 @OA\Property(property="password", type="string", format="password", example="strongpassword123"),
     *                 @OA\Property(
     *                     property="signed_agreement",
     *                     type="string",
     *                     format="binary",
     *                     description="The signed agreement file (pdf only)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Signed agreement uploaded or replaced successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=15),
     *             @OA\Property(property="message", type="string", example="Signed agreement replaced successfully"),
     *             @OA\Property(property="doc_path", type="string", example="https://yourapp.com/storage/agreements/signed/agreement_15.pdf")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=403, description="User is not a pending Broker"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
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
