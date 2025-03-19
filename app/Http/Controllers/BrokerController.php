<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class BrokerController extends Controller
{

    /**
     * Upload a signed agreement for a pending Broker.
     *
     * This endpoint allows a user with role="Broker" and status="Pending"
     * to authenticate via email/password (minimal check) and upload their
     * signed agreement (doc_type="signed_agreement").
     *
     * @OA\Post(
     *     path="/brokers/upload-signed-agreement",
     *     summary="Upload signed agreement for a pending Broker",
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
     *                     description="The signed agreement file (pdf, jpg, jpeg, png, zip)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Signed agreement uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Signed agreement uploaded successfully"),
     *             @OA\Property(property="doc_path", type="string", example="agreements/signed/agreement_15.pdf")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=403, description="User is not a pending Broker or account is inactive"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function uploadSignedAgreement(Request $request)
    {
        // 1. Validate input
        $validator = Validator::make($request->all(), [
            'email'            => 'required|email',
            'password'         => 'required|string',
            'signed_agreement' => 'required|file|mimes:pdf,jpg,jpeg,png,zip',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            // 2. Attempt minimal authentication
            $user = User::where('email', $request->email)->first();
            if (!$user || !Hash::check($request->password, $user->password)) {
                DB::rollBack();
                return response()->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
            }

            // 3. Check role = Broker and status = Pending
            $role = $user->getRoleNames()->first();
            if ($role !== 'Broker') {
                DB::rollBack();
                return response()->json(['error' => 'Forbidden - user is not a Broker'], Response::HTTP_FORBIDDEN);
            }
            if ($user->status !== 'Pending') {
                DB::rollBack();
                return response()->json(['error' => 'Forbidden - user is not in Pending status'], Response::HTTP_FORBIDDEN);
            }

            // 4. Store the file
            $file = $request->file('signed_agreement');
            $fileName = "signed_agreement_{$user->id}." . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('agreements/signed', $fileName, 'local');

            // 5. Create a record in user_docs
            $user->docs()->create([
                'doc_type'  => 'signed_agreement',
                'file_path' => $filePath,
            ]);

            DB::commit();

            // 6. Build a public URL to the doc
            $docUrl = asset("storage/{$filePath}");

            // 7. Return success
            return response()->json([
                'message'  => 'Signed agreement uploaded successfully',
                'doc_path' => $docUrl
            ], Response::HTTP_OK);

        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json([
                'error'   => 'Server error',
                'message' => $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
