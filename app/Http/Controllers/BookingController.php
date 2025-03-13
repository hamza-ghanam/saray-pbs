<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Booking;
use App\Models\CustomerInfo;
use App\Models\PaymentPlan;
use App\Models\Unit;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Rakibdevs\MrzParser\MrzParser;
use Mindee\Client;
use Mindee\Product\Passport\PassportV1;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    use AuthorizesRequests;

    /**
     * Get a booking by its ID, including the related customer info.
     *
     * @OA\Get(
     *     path="/bookings/{id}",
     *     summary="Retrieve a booking and its customer info by ID",
     *     tags={"Booking"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the booking to retrieve",
     *         required=true,
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=42),
     *             @OA\Property(property="unit_id", type="integer", example=12),
     *             @OA\Property(property="customer_info_id", type="integer", example=7),
     *             @OA\Property(property="status", type="string", example="Pre-Booked"),
     *             @OA\Property(property="receipt_path", type="string", nullable=true, example="public/receipts/abc123.pdf"),
     *             @OA\Property(
     *                 property="customer_info",
     *                 type="object",
     *                 description="Nested customer info",
     *                 @OA\Property(property="id", type="integer", example=7),
     *                 @OA\Property(property="name", type="string", example="HAMZA GHANAM"),
     *                 @OA\Property(property="passport_number", type="string", example="N007047689"),
     *                 @OA\Property(property="birth_date", type="string", format="date", example="1992-02-05"),
     *                 @OA\Property(property="gender", type="string", example="Male"),
     *                 @OA\Property(property="nationality", type="string", example="Syrian Arab Republic (the)"),
     *                 @OA\Property(property="document_path", type="string", example="public/documents/abcd1234.jpg")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Booking not found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        Log::info("User {$user->id} is requesting booking info for ID: {$id}.");

        if (!$user->can('view booking')) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        // Retrieve the booking with its related customerInfo
        $booking = Booking::with('customerInfo')->find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('view', $booking);

        // (Optional) Hide sensitive fields
        $booking->makeHidden(['receipt_path']);
        if ($booking->customerInfo) {
            $booking->customerInfo->makeHidden(['document_path']);
        }

        return response()->json($booking, Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/booking/scan-passport",
     *     summary="Scan a customer passport to get their information.",
     *     tags={"Booking"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"document"},
     *                 @OA\Property(
     *                     property="document",
     *                     type="string",
     *                     format="binary",
     *                     description="Passport file to extract data from. Accepted file extensions: pdf, jpg, jpeg, png"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer passport data extracted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             description="Parsed passport data",
     *             @OA\Property(property="type", type="string", example="Passport"),
     *             @OA\Property(property="card_no", type="string", example="015194164"),
     *             @OA\Property(property="issuer", type="string", example="Syrian Arab Republic"),
     *             @OA\Property(property="date_of_expiry", type="string", example="2024-07-05"),
     *             @OA\Property(property="first_name", type="string", example="JOHN"),
     *             @OA\Property(property="last_name", type="string", example="SMITH"),
     *             @OA\Property(property="date_of_birth", type="string", example="1988-10-09"),
     *             @OA\Property(property="gender", type="string", example="Male"),
     *             @OA\Property(property="personal_number", type="string", example="01092683756"),
     *             @OA\Property(property="nationality", type="string", example="Syrian Arab Republic")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="document", type="array", @OA\Items(type="string", example="The document field is required."))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="OCR extraction failed")
     *         )
     *     )
     * )
     */
    public function scanPassport(Request $request)
    {
        $user = $request->user();
        Log::info("User {$user->id} is scanning a customer passport.");

        if (!$user->can('book unit')) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        // Validate file upload (accepting images and PDFs)
        $validator = Validator::make($request->all(), [
            'document' => 'required|file|mimes:jpg,jpeg,png,pdf',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $file = $request->file('document');
        $path = $file->getRealPath();

        try {
            // 1. Parse the passport MRZ using Mindee
            $mindeeClient = new Client(env('MINDEE_API_KEY'));
            $inputSource = $mindeeClient->sourceFromPath($path);
            $apiResponse = $mindeeClient->parse(PassportV1::class, $inputSource);

            // 2. Extract the MRZ lines (mrz1 + mrz2)
            $mrz1 = $apiResponse->document->inference->prediction->mrz1->value ?? '';
            $mrz2 = $apiResponse->document->inference->prediction->mrz2->value ?? '';
            $mrz = $mrz1 . "\n" . $mrz2;

            // 3. Parse the combined MRZ lines via MrzParser
            $data = MrzParser::parse($mrz);
        } catch (\Exception $ex) {
            Log::error("OCR extraction failed: " . $ex->getMessage());
            return response()->json(['error' => 'OCR extraction failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $filePath = $file->store('public/passports');

        // File path token
        $token = (string)Str::uuid();
        DB::table('uploads')->insert([
            'token' => $token,
            'path' => $filePath,
            'user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Return the parsed passport data along with the file token
        return response()->json([
            'passport' => $data,
            'upload_token' => $token,
        ], Response::HTTP_OK);
    }

    /**
     * Book a unit with provided customer information and an optional payment receipt.
     *
     * @OA\Post(
     *     path="/book-unit",
     *     summary="Book a unit by creating CustomerInfo and Booking with status Pre-Booked",
     *     tags={"Booking"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={
     *                     "upload_token",
     *                     "name",
     *                     "passport_number",
     *                     "birth_date",
     *                     "gender",
     *                     "nationality",
     *                     "unit_id"
     *                 },
     *                 @OA\Property(
     *                     property="upload_token",
     *                     type="string",
     *                     description="Token referencing the previously uploaded passport file",
     *                     example="3d1ad42a-49bb-4171-83af-f67dd83e97c3"
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     maxLength=255,
     *                     example="John Smith"
     *                 ),
     *                 @OA\Property(
     *                     property="passport_number",
     *                     type="string",
     *                     maxLength=50,
     *                     example="N007832713"
     *                 ),
     *                 @OA\Property(
     *                     property="birth_date",
     *                     type="string",
     *                     format="date",
     *                     example="1992-02-05"
     *                 ),
     *                 @OA\Property(
     *                     property="gender",
     *                     type="string",
     *                     maxLength=10,
     *                     example="Male"
     *                 ),
     *                 @OA\Property(
     *                     property="nationality",
     *                     type="string",
     *                     maxLength=255,
     *                     example="Syrian Arab Republic"
     *                 ),
     *                 @OA\Property(
     *                     property="unit_id",
     *                     type="integer",
     *                     description="ID of the unit to be booked",
     *                     example=12
     *                 ),
     *                 @OA\Property(
     *                     property="payment_plan_id",
     *                     type="integer",
     *                     description="ID of the selected payment plan for this unit",
     *                     example=5
     *                 ),
     *                 @OA\Property(
     *                     property="receipt",
     *                     type="string",
     *                     format="binary",
     *                     description="Optional payment receipt (pdf, jpg, jpeg, png)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Booking created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=42),
     *             @OA\Property(property="unit_id", type="integer", example=12),
     *             @OA\Property(property="customer_info_id", type="integer", example=7),
     *             @OA\Property(property="status", type="string", example="Pre-Booked"),
     *             @OA\Property(property="confirmed_by", type="integer", nullable=true, example=null),
     *             @OA\Property(property="confirmed_at", type="string", format="date-time", nullable=true, example=null),
     *             @OA\Property(
     *                 property="customer_info",
     *                 type="object",
     *                 description="Nested CustomerInfo record",
     *                 @OA\Property(property="id", type="integer", example=7),
     *                 @OA\Property(property="name", type="string", example="John Smith"),
     *                 @OA\Property(property="passport_number", type="string", example="N007832713"),
     *                 @OA\Property(property="birth_date", type="string", format="date", example="1992-02-05"),
     *                 @OA\Property(property="gender", type="string", example="Male"),
     *                 @OA\Property(property="nationality", type="string", example="Syrian Arab Republic"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-10T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-10T12:00:00Z")
     *             ),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-10T12:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-10T12:00:00Z")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=500, description="Internal Server Error")
     * )
     */
    public function bookUnit(Request $request)
    {
        $user = $request->user();

        if (!$user->can('book unit')) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        // Validate the user-submitted data
        $validator = Validator::make($request->all(), [
            'upload_token' => 'required|string',
            'name' => 'required|string|max:255',
            'passport_number' => 'required|string|max:50',
            'birth_date' => 'required|date',
            'gender' => 'required|string|max:10',
            'nationality' => 'required|string|max:255',
            'unit_id' => 'required|integer|exists:units,id',
            'payment_plan_id' => 'required|integer|exists:payment_plans,id',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        Log::info("User {$user->id} is booking the unit {$request->unit_id}.");

        DB::beginTransaction();

        try {
            // Look up the file path by token
            $upload = DB::table('uploads')
                ->where('token', $request->upload_token)
                ->where('user_id', $user->id)
                ->first();

            if (!$upload) {
                return response()->json(['message' => 'Invalid or expired token'], Response::HTTP_FORBIDDEN);
            }

            // Check if the unit is Available or Cancelled
            $unit = Unit::findOrFail($request->unit_id);

            if (!in_array($unit->status, ['Available', 'Cancelled'])) {
                return response()->json([
                    'error' => "Unit status must be 'Available' or 'Cancelled' to book."
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $paymentPlan = PaymentPlan::where('id', $request->payment_plan_id)
                ->where('unit_id', $request->unit_id)
                ->first();

            if (!$paymentPlan) {
                throw new \Exception('Selected payment plan does not belong to this unit.');
            }

            // Now we have the real path
            $passportPath = $upload->path;

            // Optionally store the receipt
            $receiptPath = null;
            if ($request->hasFile('receipt')) {
                $receiptFile = $request->file('receipt');
                $receiptPath = $receiptFile->store('public/receipts');
            }

            // Create the CustomerInfo record
            $customerInfo = CustomerInfo::create([
                'name' => $request->name,
                'passport_number' => $request->passport_number,
                'birth_date' => $request->birth_date,
                'gender' => $request->gender,
                'nationality' => $request->nationality,
                'document_path' => $passportPath,
            ]);

            // Create the Booking record with status "Pre-Booked"
            $booking = Booking::create([
                'unit_id' => $request->unit_id,
                'payment_plan_id'  => $paymentPlan->id,
                'customer_info_id' => $customerInfo->id,
                'status' => 'Pre-Booked',
                'receipt_path' => $receiptPath,
                'created_by' => $request->user()->id,
            ]);


            $unit->status = 'Pre-Booked';
            $unit->save();

        } catch (\Exception $ex) {
            DB::rollback();
            return response()->json(['error' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        DB::commit();

        // Eager load the related customerInfo
        $booking->load('customerInfo');
        $booking->makeHidden(['receipt_path']);
        $booking->customerInfo->makeHidden(['document_path']);

        // Return the newly created booking (with nested customerInfo)
        return response()->json($booking, Response::HTTP_CREATED);
    }

    /**
     * Upload (or replace) a payment receipt for an existing booking.
     *
     * @OA\Post(
     *     path="/bookings/{id}/upload-receipt",
     *     summary="Upload or replace a payment receipt for an existing booking",
     *     tags={"Booking"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the booking to upload a receipt for",
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"receipt"},
     *                 @OA\Property(
     *                     property="receipt",
     *                     type="string",
     *                     format="binary",
     *                     description="Payment receipt file (pdf, jpg, jpeg, png)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Receipt uploaded successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=42),
     *             @OA\Property(property="unit_id", type="integer", example=12),
     *             @OA\Property(property="customer_info_id", type="integer", example=7),
     *             @OA\Property(property="status", type="string", example="Pre-Booked"),
     *             @OA\Property(property="confirmed_by", type="integer", nullable=true, example=null),
     *             @OA\Property(property="confirmed_at", type="string", format="date-time", nullable=true, example=null),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-10T12:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-10T12:00:00Z")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Booking not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function uploadReceipt(Request $request, $id)
    {
        $user = $request->user();
        Log::info("User {$user->id} is uploading a receipt for booking {$id}.");

        if (!$user->can('edit booking') || !$user->can('approve booking')) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        // Validate the uploaded file
        $validator = Validator::make($request->all(), [
            'receipt' => 'required|file|mimes:pdf,jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Retrieve the booking
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], Response::HTTP_NOT_FOUND);
        }

        // Store the receipt
        $receiptFile = $request->file('receipt');
        $receiptPath = $receiptFile->store('public/receipts');

        // Update the booking's receipt_path
        $booking->receipt_path = $receiptPath;
        $booking->save();

        $booking->makeHidden(['receipt_path']);

        return response()->json($booking, Response::HTTP_OK);
    }

    /**
     * Update an existing booking and its related customer info.
     *
     * @OA\Put(
     *     path="/bookings/{id}",
     *     summary="Update a booking (and its customer info) by ID",
     *     tags={"Booking"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the booking to update",
     *         required=true,
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     maxLength=255,
     *                     description="Updated customer name",
     *                     example="HAMZA GHANAM"
     *                 ),
     *                 @OA\Property(
     *                     property="passport_number",
     *                     type="string",
     *                     maxLength=50,
     *                     description="Updated passport number",
     *                     example="N007047689"
     *                 ),
     *                 @OA\Property(
     *                     property="birth_date",
     *                     type="string",
     *                     format="date",
     *                     description="Updated birth date",
     *                     example="1992-02-05"
     *                 ),
     *                 @OA\Property(
     *                     property="gender",
     *                     type="string",
     *                     maxLength=10,
     *                     description="Updated gender",
     *                     example="Male"
     *                 ),
     *                 @OA\Property(
     *                     property="nationality",
     *                     type="string",
     *                     maxLength=255,
     *                     description="Updated nationality",
     *                     example="Syrian Arab Republic (the)"
     *                 ),
     *                 @OA\Property(
     *                     property="payment_plan_id",
     *                     type="integer",
     *                     description="ID of the selected payment plan for this unit",
     *                     example=5
     *                 ),
     *                 @OA\Property(
     *                     property="receipt",
     *                     type="string",
     *                     format="binary",
     *                     description="Optional new receipt file (pdf, jpg, jpeg, png)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=42),
     *             @OA\Property(property="unit_id", type="integer", example=12),
     *             @OA\Property(property="customer_info_id", type="integer", example=7),
     *             @OA\Property(property="status", type="string", example="Pre-Booked"),
     *             @OA\Property(property="confirmed_by", type="integer", nullable=true, example=null),
     *             @OA\Property(property="confirmed_at", type="string", format="date-time", nullable=true, example=null),
     *             @OA\Property(
     *                 property="customer_info",
     *                 type="object",
     *                 description="Nested customer info",
     *                 @OA\Property(property="id", type="integer", example=7),
     *                 @OA\Property(property="name", type="string", example="HAMZA GHANAM"),
     *                 @OA\Property(property="passport_number", type="string", example="N007047689"),
     *                 @OA\Property(property="birth_date", type="string", format="date", example="1992-02-05"),
     *                 @OA\Property(property="gender", type="string", example="Male"),
     *                 @OA\Property(property="nationality", type="string", example="Syrian Arab Republic (the)"),
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Booking not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=500, description="Internal Server Error")
     * )
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        Log::info("User {$user->id} is updating the booking ID: {$id}.");

        // Validation for optional (partial) updates: 'sometimes'
        $validator = Validator::make($request->all(), [
            'name'            => 'sometimes|required|string|max:255',
            'passport_number' => 'sometimes|required|string|max:50',
            'birth_date'      => 'sometimes|required|date',
            'gender'          => 'sometimes|required|string|max:10',
            'nationality'     => 'sometimes|required|string|max:255',
            'payment_plan_id' => 'sometimes|integer|exists:payment_plans,id',
            'receipt'         => 'nullable|file|mimes:pdf,jpg,jpeg,png'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Retrieve the booking with its related customerInfo
        $booking = Booking::with('customerInfo')->find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], Response::HTTP_NOT_FOUND);
        }

         if (!$user->can('edit booking', $booking)) {
             return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
         }

        DB::beginTransaction();

        try {
            // Update Booking fields if provided
            if ($request->has('payment_plan_id')) {
                $paymentPlan = PaymentPlan::where('id', $request->payment_plan_id)
                    ->where('unit_id', $booking->unit_id)
                    ->first();

                if (!$paymentPlan) {
                    throw new \Exception('Selected payment plan does not belong to this unit.');
                }

                $booking->payment_plan_id = $paymentPlan->id;
            }

            // Handle a new receipt upload if present
            if ($request->hasFile('receipt')) {
                $receiptFile = $request->file('receipt');
                $receiptPath = $receiptFile->store('public/receipts');
                $booking->receipt_path = $receiptPath;
            }

            // Update the related CustomerInfo fields if provided
            $customerInfo = $booking->customerInfo; // Already loaded
            if ($request->has('name')) {
                $customerInfo->name = $request->input('name');
            }
            if ($request->has('passport_number')) {
                $customerInfo->passport_number = $request->input('passport_number');
            }
            if ($request->has('birth_date')) {
                $customerInfo->birth_date = $request->input('birth_date');
            }
            if ($request->has('gender')) {
                $customerInfo->gender = $request->input('gender');
            }
            if ($request->has('nationality')) {
                $customerInfo->nationality = $request->input('nationality');
            }

            $customerInfo->save();
            $booking->save();

        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json(['error' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        DB::commit();

        // Reload relationships in case we want fresh data
        $booking->load('customerInfo');
        $booking->makeHidden(['receipt_path']);
        $booking->customerInfo->makeHidden(['document_path']);

        return response()->json($booking, Response::HTTP_OK);
    }

    /**
     * Delete a booking by its ID.
     *
     * @OA\Delete(
     *     path="/bookings/{id}",
     *     summary="Delete a booking by ID",
     *     tags={"Booking"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the booking to delete",
     *         required=true,
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Booking deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     )
     * )
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        Log::info("User {$user->id} is attempting to delete booking {$id}.");

        // Retrieve the booking
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], Response::HTTP_NOT_FOUND);
        }

         if (!$user->can('delete booking')) {
             return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
         }

        // (Optional) Delete associated receipt file from storage
        // if ($booking->receipt_path) {
        //     Storage::delete($booking->receipt_path);
        // }

        // (Optional) Delete associated CustomerInfo or the customer's passport file:
        // if you want to remove the entire customer record:
         $booking->customerInfo()->delete();
        // or if you only want to remove the file:
        // if ($booking->customerInfo && $booking->customerInfo->document_path) {
        //     Storage::delete($booking->customerInfo->document_path);
        // }

        if ($booking->unit) {
            $booking->unit->status = 'Available';
            $booking->unit->save();
        }

        // Delete the booking record
        $booking->delete();

        // Return 204 No Content on success
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Download either the receipt or the passport document for a specific booking.
     *
     * @OA\Get(
     *     path="/bookings/{id}/download-document",
     *     summary="Download receipt or passport file for a booking",
     *     tags={"Booking"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the booking",
     *         required=true,
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Type of file to download (passport or receipt)",
     *         required=true,
     *         @OA\Schema(type="string", enum={"passport","receipt"}, example="receipt")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File downloaded successfully",
     *         @OA\MediaType(
     *             mediaType="application/octet-stream"
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Booking or file not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function downloadDocument(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:passport,receipt',
        ]);

        $user = $request->user();
        if (!$user->can('view booking')) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = $request->user();
        Log::info("User {$user->id} is attempting to download the {$request->type} for booking {$id}.");

        // 1. Retrieve the booking
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], Response::HTTP_NOT_FOUND);
        }

        if ($request->type === 'receipt' && !$booking->receipt_path) {
            return response()->json(['message' => 'No receipt found for this booking'], Response::HTTP_NOT_FOUND);
        }

        if ($request->type === 'passport' && !$booking->customerInfo->document_path) {
            return response()->json(['message' => 'No passport found for this booking customer'], Response::HTTP_NOT_FOUND);
        }

        // 4. Download the file from storage
        $docPath = $request->type === 'receipt' ? $booking->receipt_path : $booking->customerInfo->document_path;
        return Storage::download($docPath, "{$request->type}_{$booking->id}.pdf");
    }

    /**
     * Approve a booking by the currently logged-in user (role-based logic).
     *
     * @OA\Post(
     *     path="/bookings/{id}/approve",
     *     summary="Approve a booking (CEO can single-approve; CSO & Accountant require two distinct approvals)",
     *     tags={"Booking"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the booking to approve",
     *         required=true,
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Booking approved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Booking approved by role: CSO"),
     *             @OA\Property(
     *                 property="approval",
     *                 type="object",
     *                 description="Details of the newly created approval record",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="ref_id", type="integer", example=42),
     *                 @OA\Property(property="ref_type", type="string", example="App\\Models\\Booking"),
     *                 @OA\Property(property="approved_by", type="integer", example=10),
     *                 @OA\Property(property="approval_type", type="string", example="CSO"),
     *                 @OA\Property(property="status", type="string", example="Approved"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-10T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-10T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Booking not found"),
     *     @OA\Response(response=422, description="Validation error or duplicate approval from the same role")
     * )
     */
    public function approveBooking(Request $request, $id)
    {
        $user = $request->user();
        Log::info("User {$user->id} is attempting to approve booking ID: {$id}");

        // 1. Retrieve the booking
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        // 2. Check if user can approve bookings (adjust if you use a different policy/gate)
        if (!$user->can('approve booking')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // 3. Identify the user's role (assuming each user has exactly one main role)
        //    If users can have multiple roles, handle that logic here.
        $role = $user->getRoleNames()->first();
        if (!$role) {
            return response()->json(['error' => 'User has no role'], 403);
        }

        // 4. Check for existing approval from this same role (avoid duplicates)
        $existingApproval = $booking->approvals()
            ->where('approval_type', $role)
            ->where('status', 'Approved')
            ->first();

        if ($existingApproval) {
            return response()->json([
                'error' => "Booking already has an approval from role: {$role}."
            ], 422);
        }

        // 5. CEO Flow => Single Approval is Enough OR CEO as Second Approval
        if ($role === 'CEO' || $role === 'System Maintenance') {
            // Create CEO approval
            $approval = Approval::create([
                'ref_id'        => $booking->id,
                'ref_type'      => 'App\Models\Booking', // Must match your morphTo
                'approved_by'   => $user->id,
                'approval_type' => $role,
                'status'        => 'Approved',
            ]);

            $booking->status = 'RF Pending';
            $booking->save();

            $booking->unit->status = 'Booked';
            $booking->unit->save();

            return response()->json([
                'message'  => "Booking approved by {$role}.",
                'approval' => $approval,
            ], 201);
        }

        // 6. Non-CEO Flow => Need 2 Distinct Approvals: CSO & Accountant
        //    Check if the user's role is either "CSO" or "Accountant"
        if (!in_array($role, ['CSO','Accountant'])) {
            return response()->json(['error' => "Role {$role} is not allowed to approve bookings."], 403);
        }

        // Create the new approval for CSO/Accountant
        $approval = Approval::create([
            'ref_id'        => $booking->id,
            'ref_type'      => 'App\Models\Booking',
            'approved_by'   => $user->id,
            'approval_type' => $role,
            'status'        => 'Approved',
        ]);

        // 7. Check how many distinct roles have approved so far
        $rolesApproved = $booking->approvals()
            ->where('status', 'Approved')
            ->pluck('approval_type')
            ->unique();

        // If we have both "CSO" and "Accountant", then the booking is fully approved
        if ($rolesApproved->contains('CSO') && $rolesApproved->contains('Accountant')) {
            // e.g., finalize the booking or set status
             $booking->status = 'RF Pending';
             $booking->save();

            $booking->unit->status = 'Booked';
            $booking->unit->save();
        }

        return response()->json([
            'message'  => "Booking approved by {$role}",
            'approval' => $approval,
        ], 201);
    }
}
