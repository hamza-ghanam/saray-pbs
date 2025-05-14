<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Booking;
use App\Models\CustomerInfo;
use App\Models\PaymentPlan;
use App\Models\Unit;
use App\Models\User;
use App\Services\PaymentPlanService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Rakibdevs\MrzParser\MrzParser;
use Mindee\Client;
use Mindee\Product\Passport\PassportV1;
use Illuminate\Support\Str;
use App\Services\FCMService;

/**
 * @OA\Schema(
 *     schema="Booking",
 *     type="object",
 *     title="Booking",
 *     required={
 *         "id",
 *         "unit_id",
 *         "customer_info_id",
 *         "status",
 *         "created_by",
 *         "created_at",
 *         "updated_at"
 *     },
 *     @OA\Property(property="id",                 type="integer", format="int64", example=42),
 *     @OA\Property(property="unit_id",            type="integer", example=12),
 *     @OA\Property(property="payment_plan_id",    type="integer", nullable=true, example=5),
 *     @OA\Property(property="customer_info_id",   type="integer", example=7),
 *     @OA\Property(property="status",             type="string",  example="Pre-Booked"),
 *     @OA\Property(property="price",              type="number",  format="float", example=1535432.00),
 *     @OA\Property(property="discount",           type="number",  format="float", example=5),
 *     @OA\Property(property="receipt_path",       type="string",             example="receipts/abc123.pdf"),
 *     @OA\Property(property="created_by",         type="integer",            example=3),
 *     @OA\Property(property="confirmed_by",       type="integer", nullable=true, example=null),
 *     @OA\Property(property="confirmed_at",       type="string",  format="date-time", nullable=true, example=null),
 *     @OA\Property(property="created_at",         type="string",  format="date-time", example="2025-05-02T16:00:00Z"),
 *     @OA\Property(property="updated_at",         type="string",  format="date-time", example="2025-05-02T16:00:00Z")
 * )
 */
class BookingController extends Controller
{
    use AuthorizesRequests;

    protected PaymentPlanService $paymentPlanService;

    public function __construct(PaymentPlanService $paymentPlanService)
    {
        $this->paymentPlanService = $paymentPlanService;
    }

//    protected $fcmService;

//    public function __construct(FCMService $fcmService)
//    {
//        $this->fcmService = $fcmService;
//    }

    /**
     * Get a booking by its ID, including related customer info, reservation form, SPA, and approvals.
     *
     * @OA\Get(
     *     path="/bookings/{id}",
     *     summary="Retrieve a booking and its related records by ID",
     *     tags={"Bookings"},
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
     *             @OA\Property(property="id",                type="integer", example=42),
     *             @OA\Property(property="unit_id",           type="integer", example=12),
     *             @OA\Property(property="customer_info_id",  type="integer", example=7),
     *             @OA\Property(property="status",            type="string",  example="Pre-Booked"),
     *             @OA\Property(
     *                 property="approvals",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Approval")
     *             ),
     *             @OA\Property(
     *                 property="customer_info",
     *                 type="object",
     *                 description="Nested customer info",
     *                 @OA\Property(property="id",              type="integer", example=7),
     *                 @OA\Property(property="name",            type="string",  example="John Smith"),
     *                 @OA\Property(property="passport_number", type="string",  example="N001234567"),
     *                 @OA\Property(property="birth_date",      type="string",  format="date", example="1992-02-05"),
     *                 @OA\Property(property="gender",          type="string",  example="Male"),
     *                 @OA\Property(property="nationality",     type="string",  example="Syrian Arab Republic (the)")
     *             ),
     *             @OA\Property(
     *                 property="reservation_form",
     *                 type="object",
     *                 nullable=true,
     *                 description="Associated reservation form",
     *                 @OA\Property(property="id",         type="integer", example=10),
     *                 @OA\Property(property="booking_id", type="integer", example=42),
     *                 @OA\Property(property="status",     type="string",  example="Signed"),
     *                 @OA\Property(property="signed_at",  type="string",  format="date-time", nullable=true, example="2025-05-03T12:34:56Z"),
     *                 @OA\Property(
     *                     property="approvals",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Approval")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="spa",
     *                 type="object",
     *                 nullable=true,
     *                 description="Associated Sales and Purchase Agreement",
     *                 @OA\Property(property="id",         type="integer", example=11),
     *                 @OA\Property(property="booking_id", type="integer", example=42),
     *                 @OA\Property(property="status",     type="string",  example="Pending"),
     *                 @OA\Property(property="signed_at",  type="string",  format="date-time", nullable=true, example=null),
     *                 @OA\Property(
     *                     property="approvals",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Approval")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Booking not found")
     * )
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

        // Sales-only extra guard
        if ($user->hasRole('Sales') && $booking->created_by !== $user->id) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // (Optional) Hide sensitive fields
        $booking->load([
            'approvals',
            'reservationForm.approvals',
            'spa.approvals',
        ]);

        return response()->json($booking, Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/bookings/scan-passport",
     *     summary="Scan a customer passport to get their information.",
     *     tags={"Bookings"},
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
     *             @OA\Property(property="passport", type="object", description="Parsed passport data",
     *                 @OA\Property(property="type",             type="string", example="Passport"),
     *                 @OA\Property(property="card_no",          type="string", example="015194164"),
     *                 @OA\Property(property="issuer",           type="string", example="Syrian Arab Republic"),
     *                 @OA\Property(property="date_of_expiry",    type="string", example="2024-07-05"),
     *                 @OA\Property(property="first_name",       type="string", example="JOHN"),
     *                 @OA\Property(property="last_name",        type="string", example="SMITH"),
     *                 @OA\Property(property="date_of_birth",    type="string", example="1988-10-09"),
     *                 @OA\Property(property="gender",           type="string", example="Male"),
     *                 @OA\Property(property="personal_number",  type="string", example="01092683756"),
     *                 @OA\Property(property="nationality",      type="string", example="Syrian Arab Republic")
     *             ),
     *             @OA\Property(
     *                 property="upload_token",
     *                 type="string",
     *                 example="3d1ad42a-49bb-4171-83af-f67dd83e97c3",
     *                 description="Token to reference the stored upload for later use"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="document",
     *                 type="array",
     *                 @OA\Items(type="string", example="The document field is required.")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
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
            'document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
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

        $filePath = $file->store('passports', 'local');

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
     * Book a unit with provided customer information, optional passport upload, receipt, discount and notes.
     *
     * @OA\Post(
     *     path="/bookings/book-unit",
     *     summary="Book a unit by creating CustomerInfo and Booking with status Pre-Booked",
     *     tags={"Bookings"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","passport_number","birth_date","gender","nationality","unit_id"},
     *                 @OA\Property(
     *                     property="upload_token",
     *                     type="string",
     *                     nullable=true,
     *                     example="3d1ad42a-49bb-4171-83af-f67dd83e97c3",
     *                     description="Optional token for a previously uploaded passport; omit to skip document"
     *                 ),
     *                 @OA\Property(property="name",            type="string", maxLength=255, example="John Smith"),
     *                 @OA\Property(property="passport_number", type="string", maxLength=50,  example="N001234567"),
     *                 @OA\Property(property="birth_date",      type="string", format="date", example="1992-02-05"),
     *                 @OA\Property(property="gender",          type="string", maxLength=10,  example="Male"),
     *                 @OA\Property(property="nationality",     type="string", maxLength=255, example="Syrian Arab Republic"),
     *                 @OA\Property(property="unit_id",         type="integer", example=12),
     *                 @OA\Property(property="payment_plan_id", type="integer", nullable=true, example=5),
     *                 @OA\Property(property="receipt",         type="string", format="binary"),
     *                 @OA\Property(property="discount",        type="number", format="float", example=5),
     *                 @OA\Property(property="notes",           type="string", example="Customer requests early handover.")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Booking created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Booking")
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=500, description="Internal Server Error")
     * )
     */
    public function bookUnit(Request $request)
    {
        $user = $request->user();
        Log::info("User {$user->id} is attempting to book unit {$request->unit_id}.");

        if (!$user->can('book unit')) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'upload_token' => 'sometimes|string',
            'name' => 'required|string|max:255',
            'passport_number' => 'required|string|max:50',
            'birth_date' => 'required|date',
            'gender' => 'required|string|max:10',
            'nationality' => 'required|string|max:255',
            'unit_id' => 'required|integer|exists:units,id',
            'payment_plan_id' => 'sometimes|integer|exists:payment_plans,id',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'discount' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();

        try {
            // Optional passport path
            $passportPath = null;
            if ($request->filled('upload_token')) {
                $upload = DB::table('uploads')
                    ->where('token', $request->upload_token)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$upload) {
                    return response()->json(['message' => 'Invalid or expired token'], Response::HTTP_FORBIDDEN);
                }

                $passportPath = $upload->path;
            }

            // Ensure unit can be booked
            $unit = Unit::findOrFail($request->unit_id);
            $myHold = $unit->holdings()
                ->where('created_by', $user->id)
                ->where('status', 'Hold')
                ->first();

            if (
                !in_array($unit->status, ['Available', 'Cancelled']) &&
                !($myHold && $unit->status === 'Hold')
            ) {
                return response()->json([
                    'error' => "Unit status must be 'Available' or 'Cancelled' to book (unless you hold it)."
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Determine payment plan
            if ($request->has('payment_plan_id')) {
                $paymentPlan = PaymentPlan::find($request->payment_plan_id);
            } else {
                $paymentPlan = PaymentPlan::where('is_default', true)->first();
            }

            if (!$paymentPlan) {
                throw new \Exception('Selected payment plan does not exist for this unit.');
            }

            // Optional receipt
            $receiptPath = null;
            if ($request->hasFile('receipt')) {
                $receiptPath = $request->file('receipt')->store('receipts', 'local');
            }

            // Create customer info
            $customerInfo = CustomerInfo::create([
                'name' => $request->name,
                'passport_number' => $request->passport_number,
                'birth_date' => $request->birth_date,
                'gender' => $request->gender,
                'nationality' => $request->nationality,
                'document_path' => $passportPath,
            ]);

            // Compute booking price
            $basePrice = $unit->price;
            $discountPct = $request->input('discount', 0);
            $bookingPrice = $discountPct > 0
                ? round($basePrice * (1 - $discountPct / 100), 2)
                : $basePrice;

            // Create booking
            $booking = Booking::create([
                'unit_id' => $unit->id,
                'payment_plan_id' => $paymentPlan->id,
                'customer_info_id' => $customerInfo->id,
                'status' => 'Pre-Booked',
                'discount' => $discountPct,
                'price' => $bookingPrice,
                'receipt_path' => $receiptPath,
                'created_by' => $user->id,
                'notes' => $request->input('notes'),
            ]);

            // Generate and persist installments
            $template = $this->paymentPlanService
                ->generateInstallments($unit, $paymentPlan, $discountPct);

            $rows = $template->map(fn($i) => [
                'payment_plan_id' => $paymentPlan->id,
                'description' => $i->description,
                'percentage' => $i->percentage,
                'date' => $i->date,
                'amount' => $i->amount,
            ])->all();

            $saved = $booking->installments()->createMany($rows);
            $booking->setRelation('installments', $saved);

            // Update unit status
            $unit->status = 'Pre-Booked';
            $unit->status_changed_at = now();
            $unit->save();

            if ($myHold) {
                $myHold->status = 'Processed';
                $myHold->save();
            }
        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json(['error' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        DB::commit();

        // Clean up upload token if used
        if ($request->filled('upload_token')) {
            DB::table('uploads')
                ->where('token', $request->upload_token)
                ->where('user_id', $user->id)
                ->delete();
        }

        $booking->load('customerInfo');
        return response()->json($booking, Response::HTTP_CREATED);
    }

    /**
     * Upload or replace either the customer’s ID document **or** a payment receipt.
     *
     * @OA\Post(
     *     path="/bookings/{id}/upload-document",
     *     summary="Upload (or replace) an ID document or a payment receipt for an existing booking",
     *     tags={"Bookings"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the booking",
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 description="Provide **either** `id_document` **or** `receipt`",
     *                 oneOf={
     *                     @OA\Schema(required={"id_document"}),
     *                     @OA\Schema(required={"receipt"})
     *                 },
     *                 @OA\Property(
     *                     property="id_document",
     *                     type="string",
     *                     format="binary",
     *                     description="Customer ID document (pdf, jpg, jpeg, png)"
     *                 ),
     *                 @OA\Property(
     *                     property="receipt",
     *                     type="string",
     *                     format="binary",
     *                     description="Payment receipt (pdf, jpg, jpeg, png)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document uploaded successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Booking")
     *     ),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Booking not found"),
     *     @OA\Response(response=422, description="Validation error or missing file")
     * )
     */
    public function uploadDocument(Request $request, $id)
    {
        $user = $request->user();
        Log::info("User {$user->id} is uploading a document for booking {$id}.");

        if (!$user->can('edit booking') || !$user->can('approve booking')) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'id_document' => 'sometimes|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'receipt' => 'sometimes|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$request->hasFile('id_document') && !$request->hasFile('receipt')) {
            return response()->json([
                'error' => 'You must upload either id_document or receipt.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $booking = Booking::with('customerInfo')->find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], Response::HTTP_NOT_FOUND);
        }

        // Sales‐only guard
        if ($user->hasRole('Sales') && $booking->created_by !== $user->id) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        DB::beginTransaction();
        try {
            // Handle ID document
            if ($request->hasFile('id_document')) {
                $file = $request->file('id_document');
                $path = $file->store('customer_docs', 'local');
                $booking->customerInfo->update([
                    'document_path' => $path,
                ]);
            }

            // Handle payment receipt
            if ($request->hasFile('receipt')) {
                $file = $request->file('receipt');
                $path = $file->store('receipts', 'local');
                $booking->update([
                    'receipt_path' => $path,
                ]);
            }

            DB::commit();

            // Reload nested customerInfo
            $booking->load('customerInfo');

            $booking->customerInfo->document_url = $booking->customerInfo->document_path ?
                route(
                    'bookings.download_document',
                    ['booking' => $booking->id, 'type' => 'passport']
                ) : null;

            $booking->receipt_url = $booking->receipt_path
                ? route(
                    'bookings.download_document',
                    ['booking' => $booking->id, 'type' => 'receipt']
                ) : null;

            return response()->json($booking, Response::HTTP_OK);

        } catch (\Exception $ex) {
            DB::rollback();
            return response()->json(
                ['error' => $ex->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/bookings/{id}",
     *     summary="Update a booking (and its customer info) by ID",
     *     tags={"Bookings"},
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
     *                 @OA\Property(property="name",             type="string", example="John Smith"),
     *                 @OA\Property(property="passport_number",  type="string", example="N001234567"),
     *                 @OA\Property(property="birth_date",       type="string", format="date", example="1992-02-05"),
     *                 @OA\Property(property="gender",           type="string", example="Male"),
     *                 @OA\Property(property="nationality",      type="string", example="Syrian Arab Republic"),
     *                 @OA\Property(property="payment_plan_id",  type="integer", example=5),
     *                 @OA\Property(
     *                     property="receipt",
     *                     type="string",
     *                     format="binary",
     *                     description="Optional new receipt file"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Booking")
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
            'name' => 'sometimes|required|string|max:255',
            'passport_number' => 'sometimes|required|string|max:50',
            'birth_date' => 'sometimes|required|date',
            'gender' => 'sometimes|required|string|max:10',
            'nationality' => 'sometimes|required|string|max:255',
            'payment_plan_id' => 'sometimes|integer|exists:payment_plans,id',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
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

        // Sales-only extra guard
        if ($user->hasRole('Sales') && $booking->created_by !== $user->id) {
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
                $receiptPath = $receiptFile->store('receipts', 'local');
                $booking->receipt_path = $receiptPath;
                $booking->save();
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

        return response()->json($booking, Response::HTTP_OK);
    }

    /**
     * @OA\Delete(
     *     path="/api/bookings/{id}",
     *     summary="Delete a booking and reset its unit to Available",
     *     operationId="destroyBooking",
     *     tags={"Bookings"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the booking to delete",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Booking deleted successfully, unit status reset to Available"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden — user lacks deletion permission",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found — booking does not exist",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Booking not found")
     *         )
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
     * Download a booking document.
     *
     * @OA\Get(
     *     path="/bookings/{id}/download-document/{type}",
     *     summary="Download a specific document for a booking",
     *     tags={"Bookings"},
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
     *         in="path",
     *         description="Which document to download",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             enum={"passport","receipt","rf","signed_rf","spa","signed_spa","dld"},
     *             example="receipt"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File downloaded successfully",
     *         @OA\MediaType(mediaType="application/octet-stream")
     *     ),
     *     @OA\Response(response=403, description="Forbidden — lacking permission or Sales user requesting others’ bookings"),
     *     @OA\Response(response=404, description="Booking or requested document not found"),
     *     @OA\Response(response=422, description="Invalid document type")
     * )
     */
    public function downloadDocument(Request $request, int $id, string $type)
    {
        // 1. Validate the `type` path parameter
        $validated = Validator::make(
            ['type' => $type],
            ['type' => ['required', Rule::in([
                'passport', 'receipt', 'rf', 'signed_rf', 'spa', 'signed_spa', 'dld'
            ])]]
        )->validate();

        $user = $request->user();

        // 2. Permission check
        if (!$user->can('view booking')) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // 3. Load the booking
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], Response::HTTP_NOT_FOUND);
        }

        // 4. Sales-only extra guard
        if ($user->hasRole('Sales') && $booking->created_by !== $user->id) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        Log::info("User {$user->id} downloading {$type} for booking {$id}");

        // 5. Map `type` → file path
        $getPath = [
            'passport' => fn() => optional($booking->customerInfo)->document_path,
            'receipt' => fn() => $booking->receipt_path,
            'rf' => fn() => optional($booking->reservationForm)->file_path,
            'signed_rf' => fn() => optional($booking->signedReservationForm)->signed_file_path,
            'spa' => fn() => optional($booking->spa)->file_path,
            'signed_spa' => fn() => optional($booking->signedSpa)->signed_file_path,
            'dld' => fn() => optional($booking->dldDocument)->file_path,
        ];
        $path = $getPath[$validated['type']]();

        if (!$path) {
            return response()->json([
                'message' => ucfirst(str_replace('_', ' ', $validated['type'])) . ' not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // 6. Stream the download
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $filename = "{$validated['type']}_booking_{$booking->id}.{$ext}";

        return Storage::disk('local')->download($path, $filename);
    }

    /**
     * Approve a booking by the currently logged-in user (role-based logic).
     *
     * @OA\Post(
     *     path="/bookings/{id}/approve",
     *     summary="Approve a booking (CEO can single-approve; CSO & Accountant require two distinct approvals)",
     *     tags={"Bookings"},
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
                'ref_id' => $booking->id,
                'ref_type' => 'App\Models\Booking', // Must match your morphTo
                'approved_by' => $user->id,
                'approval_type' => $role,
                'status' => 'Approved',
            ]);

            $booking->status = 'RF Pending';
            $booking->save();

            $booking->unit->status = 'Booked';
            $booking->unit->status_changed_at = now();
            $booking->unit->save();

            return response()->json([
                'message' => "Booking approved by {$role}.",
                'approval' => $approval,
            ], 201);
        }

        // 6. Non-CEO Flow => Need 2 Distinct Approvals: CSO & Accountant
        //    Check if the user's role is either "CSO" or "Accountant"
        if (!in_array($role, ['CSO', 'Accountant'])) {
            return response()->json(['error' => "Role {$role} is not allowed to approve bookings."], 403);
        }

        // Create the new approval for CSO/Accountant
        $approval = Approval::create([
            'ref_id' => $booking->id,
            'ref_type' => 'App\Models\Booking',
            'approved_by' => $user->id,
            'approval_type' => $role,
            'status' => 'Approved',
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

            $ceoUsers = User::role('CEO')->with('deviceTokens')->get();

            // Extract tokens into a flat array
            $createdByTokens = $booking->unit->user
                ? $booking->unit->user->deviceTokens->pluck('token')->toArray()
                : [];

            $ceoTokens = $ceoUsers->pluck('deviceTokens')
                ->flatten()
                ->pluck('token')
                ->toArray();

            $deviceTokens = array_merge($ceoTokens, $createdByTokens);

            $title = "Booking Approved";
            $body = "Booking ID: {$booking->id} has been approved by {$role}.";
            $data = [
                'booking_id' => (string)$booking->id,
                'approval_role' => $role,
                'new_status' => $booking->status,
                'timestamp' => now()->toIso8601String(),
            ];
            //    $this->fcmService->sendPushNotification($deviceTokens, $title, $body, $data);

            return response()->json([
                'message' => "Booking approved by {$role}.",
                'approval' => $approval,
            ], Response::HTTP_CREATED);
        }

        return response()->json([
            'message' => "Booking approved by {$role}",
            'approval' => $approval,
        ], Response::HTTP_CREATED);
    }
}
