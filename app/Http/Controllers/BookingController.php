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
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Rakibdevs\MrzParser\MrzParser;
use Mindee\Client;
use Mindee\Product\Passport\PassportV1;
use Illuminate\Support\Str;
use App\Services\FCMService;

use Mindee\ClientV2;
use Mindee\Input\InferenceParameters;
use Mindee\Input\PathInput;
use Mindee\Error\MindeeException;

/**
 * @OA\Schema(
 *     schema="CustomerInfo",
 *     type="object",
 *     title="Customer Info",
 *     required={
 *         "name",
 *         "passport_number",
 *         "birth_date",
 *         "gender",
 *         "nationality",
 *         "email",
 *         "phone_number",
 *         "address"
 *     },
 *     @OA\Property(property="name",            type="string", example="John Smith"),
 *     @OA\Property(property="passport_number", type="string", example="A1234567"),
 *     @OA\Property(property="birth_date",      type="string", format="date", example="1990-01-01"),
 *     @OA\Property(property="gender",          type="string", example="Male"),
 *     @OA\Property(property="nationality",     type="string", example="British"),
 *     @OA\Property(property="email",           type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="phone_number",    type="string", example="+971501234567"),
 *     @OA\Property(property="address",         type="string", example="123 Palm Jumeirah, Dubai"),
 *     @OA\Property(property="issuance_date",      type="string", format="date", nullable=true, example="2025-01-01"),
 *     @OA\Property(property="expiry_date",     type="string", format="date", nullable=true, example="2025-12-31"),
 *     @OA\Property(property="upload_token",    type="string", nullable=true, example="3d1ad42a-49bb-4171-83af-f67dd83e97c3")
 * ),
 *
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
 *
 * @OA\Schema(
 *     schema="BookingUpdateRequest",
 *     type="object",
 *     description="Fields allowed when updating a booking. Supports partial updates and file upload.",
 *
 *     @OA\Property(
 *         property="payment_plan_id",
 *         type="integer",
 *         nullable=true,
 *         description="Payment plan ID (must belong to the same unit as the booking)",
 *         example=5
 *     ),
 *
 *     @OA\Property(
 *         property="discount",
 *         type="number",
 *         nullable=true,
 *         description="The applicable discount of the booking price, required ONLY IF the payment_plan_id is passed.",
 *         example=10
 *     ),
 *
 *     @OA\Property(
 *         property="notes",
 *         type="string",
 *         nullable=true,
 *         description="Booking-level internal notes",
 *         example="Customer asked to change payment plan"
 *     ),
 *
 *     @OA\Property(
 *         property="agent_id",
 *         type="integer",
 *         nullable=true,
 *         description="Sales agent ID (cannot be a Broker or Contractor)",
 *         example=12
 *     ),
 *
 *     @OA\Property(
 *         property="sale_source_id",
 *         type="integer",
 *         nullable=true,
 *         description="Sale source user ID",
 *         example=4
 *     ),
 *
 *     @OA\Property(
 *         property="agency_com_agent",
 *         type="string",
 *         nullable=true,
 *         description="Agency commission text",
 *         example="2% commission for external broker"
 *     ),
 *
 *     @OA\Property(
 *         property="customer_id",
 *         type="integer",
 *         nullable=true,
 *         description="ID of the customer (from customer_infos table) to update",
 *         example=33
 *     ),
 *
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         nullable=true,
 *         description="Customer full name",
 *         example="John Doe"
 *     ),
 *
 *     @OA\Property(
 *         property="passport_number",
 *         type="string",
 *         nullable=true,
 *         description="Customer passport number",
 *         example="A1234567"
 *     ),
 *
 *     @OA\Property(
 *         property="birth_date",
 *         type="string",
 *         format="date",
 *         nullable=true,
 *         description="Customer date of birth (YYYY-MM-DD)",
 *         example="1991-01-15"
 *     ),
 *
 *     @OA\Property(
 *         property="gender",
 *         type="string",
 *         nullable=true,
 *         description="Customer gender",
 *         example="Male"
 *     ),
 *
 *     @OA\Property(
 *         property="nationality",
 *         type="string",
 *         nullable=true,
 *         description="Customer nationality",
 *         example="Jordanian"
 *     )
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
     * Get a booking by its ID, including related customer infos, reservation form, SPA, and approvals.
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
     *             @OA\Property(property="id",        type="integer", example=42),
     *             @OA\Property(property="unit_id",   type="integer", example=12),
     *             @OA\Property(property="status",    type="string",  example="Pre-Booked"),
     *             @OA\Property(property="agent",     type="object", nullable=true,
     *                 @OA\Property(property="id", type="integer", example=3),
     *                 @OA\Property(property="name", type="string", example="Ali Broker"),
     *             ),
     *             @OA\Property(property="sale_source", type="object", nullable=true,
     *                 @OA\Property(property="id", type="integer", example=7),
     *                 @OA\Property(property="name", type="string", example="Fatima Sales"),
     *                 @OA\Property(property="email", type="string", example="fatima@example.com"),
     *                 @OA\Property(property="type", type="string", example="Broker", description="Either 'Broker' or 'Direct'")
     *             ),
     *             @OA\Property(
     *                 property="customer_infos",
     *                 type="array",
     *                 description="List of customers associated with the booking",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id",              type="integer", example=7),
     *                     @OA\Property(property="name",            type="string",  example="John Smith"),
     *                     @OA\Property(property="passport_number", type="string",  example="N001234567"),
     *                     @OA\Property(property="birth_date",      type="string",  format="date", example="1992-02-05"),
     *                     @OA\Property(property="gender",          type="string",  example="Male"),
     *                     @OA\Property(property="nationality",     type="string",  example="Syrian Arab Republic")
     *                 )
     *             ),
     *
     *             @OA\Property(
     *                 property="approvals",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Approval")
     *             ),
     *
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
     *
     *             @OA\Property(
     *                 property="spa",
     *                 type="object",
     *                 nullable=true,
     *                 description="Associated Sales and Purchase Agreement",
     *                 @OA\Property(property="id",         type="integer", example=11),
     *                 @OA\Property(property="booking_id", type="integer", example=42),
     *                 @OA\Property(property="status",     type="string",  example="Pending"),
     *                 @OA\Property(property="agent_id", type="integer", example=3),
     *                 @OA\Property(property="sale_source_id", type="integer",example=7),
     *                 @OA\Property(property="agency_com_agent", type="string", example="John Smith"),
     *                 @OA\Property(property="note",     type="string",  example="Mr. Feras"),
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

        // ✅ Load booking with all related records (including multiple customer infos)
        $booking = Booking::with([
            'customerInfos',
            'approvals',
            'reservationForm.approvals',
            'spa.approvals',
        ])->find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], Response::HTTP_NOT_FOUND);
        }

        // ✅ Sales-only guard
        if ($user->hasRole('Sales') && $booking->created_by !== $user->id) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        if ($booking->saleSource) {
            $booking->saleSource->type = 'Broker Agency';
        } else {
            $booking->sale_source = [
                'id' => null,
                'name' => null,
                'email' => null,
                'status' => null,
                'type' => 'Direct',
            ];
        }

        $booking->load('customerInfos', 'installments', 'agent');

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
            $apiKey = config('services.mindee.api_key');
            $modelId = config('services.mindee.model_id');

            $mindeeClient = new ClientV2($apiKey);

            $inferenceParams = new InferenceParameters($modelId);

            $inputSource = new PathInput($path);

            $response = $mindeeClient->enqueueAndGetInference(
                $inputSource,
                $inferenceParams
            );

            $fields = $response->inference->result->fields;
            $mrzLine1 = $fields->getSimpleField('mrz_line_1')->value ?? null;
            $mrzLine2 = $fields->getSimpleField('mrz_line_2')->value ?? null;
            $mrz = $mrzLine1 . "\n" . $mrzLine2;

            $data = MrzParser::parse($mrz);

            $data['issuance_date'] = $fields->getSimpleField('date_of_issue')->value ?? null;
        } catch (\Exception $ex) {
            Log::error("OCR extractions failed: " . $ex->getMessage());
            return response()->json(['error' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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
     * Book a unit by creating one Booking and multiple CustomerInfo records
     *
     * @OA\Post(
     *     path="/bookings/book-unit",
     *     summary="Book a unit with multiple customers and status Pre-Booked",
     *     tags={"Bookings"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"unit_id", "customers"},
     *                 @OA\Property(property="unit_id", type="integer", example=12),
     *                 @OA\Property(property="payment_plan_id", type="integer", nullable=true, example=5),
     *                 @OA\Property(property="receipt", type="string", format="binary"),
     *                 @OA\Property(property="discount", type="number", format="float", example=5),
     *                 @OA\Property(property="agent_id", type="integer", nullable=true, example=3, description="User ID of the agent (must not be Broker or Contractor)"),
     *                 @OA\Property(property="agency_com_agent", type="string", example="John Smith."),
     *                 @OA\Property(property="sale_source_id", type="integer", nullable=true, example=7, description="User ID of the source of sale"),
     *                 @OA\Property(property="notes", type="string", example="Customer requests early handover."),
     *                 @OA\Property(
     *                     property="customers",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/CustomerInfo")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Booking created successfully"),
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

        $isSales = $user->hasRole('Sales');
        if ($isSales) {
            $request->merge(['agent_id' => $user->id, 'current_user_is_sales' => 1]);
        } else {
            $request->merge(['current_user_is_sales' => 0]);
        }

        $messages = [
            'agent_id.required_unless' => 'Agent field is required unless you are logged in as a sales user.',
            'agent_id.exists' => 'The selected agent does not exist in our records.',
            'agent_id.integer' => 'Agent must be a valid ID number.',
        ];

        $validated = $request->validate([
            'unit_id' => 'required|integer|exists:units,id',
            'payment_plan_id' => 'nullable|integer|exists:payment_plans,id',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'discount' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',

            'agent_id' => [
                'required_unless:current_user_is_sales,1',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $u = User::find($value);
                    if ($u && $u->hasAnyRole(['Broker', 'Contractor'])) {
                        $fail("Selected agent cannot be a Broker or Contractor.");
                    }
                },
            ],

            'sale_source_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $user = User::find($value);
                        if (!$user || !$user->hasRole('Broker')) {
                            $fail('The sale source must be a user with the Broker role.');
                        }
                    }
                }
            ],

            'agency_com_agent' => 'nullable|string|max:255',

            'customers' => 'required|array|min:1',
            'customers.*.name' => 'required|string|max:255',
            'customers.*.passport_number' => 'required|string|max:50',
            'customers.*.birth_date' => 'required|date',
            'customers.*.gender' => 'required|string|max:10',
            'customers.*.nationality' => 'required|string|max:255',
            'customers.*.issuance_date' => 'nullable|date',
            'customers.*.expiry_date' => 'nullable|date',
            'customers.*.email' => 'required|email|max:255',
            'customers.*.phone_number' => 'required|string|max:20',
            'customers.*.address' => 'required|string|max:255',
            'customers.*.upload_token' => 'nullable|string',
        ], $messages);

        DB::beginTransaction();

        try {
            // 1. Retrieve unit & check status
            $unit = Unit::findOrFail($validated['unit_id']);
            $myHold = $unit->holdings()
                ->where('created_by', $user->id)
                ->where('status', 'Hold')
                ->first();

            if (!in_array($unit->status, [Unit::STATUS_AVAILABLE, Unit::STATUS_CANCELLED]) &&
                !($myHold && $unit->status === Unit::STATUS_HOLD)) {
                return response()->json([
                    'error' => "Unit status must be 'Available' or 'Cancelled' to book (unless you hold it)."
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // 2. Payment plan
            $paymentPlanId = Arr::get($validated, 'payment_plan_id');

            $paymentPlan = $paymentPlanId
                ? PaymentPlan::find($paymentPlanId)
                : PaymentPlan::where('is_default', true)->first();

            if (!$paymentPlan) {
                throw new \Exception('Selected payment plan does not exist for this unit.');
            }

            // 3. Receipt upload
            $receiptPath = $request->hasFile('receipt')
                ? $request->file('receipt')->store('receipts', 'local')
                : null;

            // 4. Booking price
            $basePrice = $unit->price;
            $discountPct = $validated['discount'] ?? 0;
            $bookingPrice = $discountPct > 0
                ? round($basePrice * (1 - $discountPct / 100), 2)
                : $basePrice;

            // 5. Create Booking (customer_info_id removed)
            $booking = Booking::create([
                'unit_id' => $unit->id,
                'payment_plan_id' => $paymentPlan->id,
                'status' => 'Pre-Booked',
                'discount' => $discountPct,
                'price' => $bookingPrice,
                'receipt_path' => $receiptPath,
                'created_by' => $user->id,
                'notes' => $validated['notes'] ?? null,

                'agent_id' => $validated['agent_id'] ?? null,
                'sale_source_id' => $validated['sale_source_id'] ?? null,
                'agency_com_agent' => $validated['agency_com_agent'] ?? null,
            ]);

            // 6. Create CustomerInfo entries
            foreach ($validated['customers'] as $customer) {
                $passportPath = null;

                if (!empty($customer['upload_token'])) {
                    $upload = DB::table('uploads')
                        ->where('token', $customer['upload_token'])
                        ->where('user_id', $user->id)
                        ->first();

                    if (!$upload) {
                        throw new \Exception("Invalid or expired token for customer '{$customer['name']}'");
                    }

                    $passportPath = $upload->path;
                }

                $booking->customerInfos()->create(array_merge($customer, [
                    'document_path' => $passportPath,
                ]));

                if (!empty($customer['upload_token'])) {
                    DB::table('uploads')
                        ->where('token', $customer['upload_token'])
                        ->where('user_id', $user->id)
                        ->delete();
                }
            }

            // 7. Create installments
            $this->generateInstallmentOfPaymentPlan($unit, $paymentPlan, $discountPct, $booking);

            // 8. Update unit status
            $unit->update([
                'status' => 'Pre-Booked',
                'status_changed_at' => now(),
            ]);

            if ($myHold) {
                $myHold->update(['status' => 'Processed']);
            }

            DB::commit();

            if ($booking->saleSource) {
                $booking->saleSource->type = 'Broker Agency';
            } else {
                $booking->sale_source = [
                    'id' => null,
                    'name' => null,
                    'email' => null,
                    'status' => null,
                    'type' => 'Direct',
                ];
            }

            $booking->load('customerInfos', 'installments', 'agent');

            return response()->json($booking, Response::HTTP_CREATED);
        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json(
                ['error' => $ex->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Upload or replace either a customer’s ID document **or** a payment receipt.
     *
     * @OA\Post(
     *     path="/bookings/{id}/upload-document",
     *     summary="Upload (or replace) an ID document for a specific customer or a payment receipt for the booking",
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
     *                 description="Provide **either** `id_document` with `customer_id` **or** a `receipt`",
     *                 oneOf={
     *                     @OA\Schema(required={"id_document", "customer_id"}),
     *                     @OA\Schema(required={"receipt"})
     *                 },
     *                 @OA\Property(
     *                     property="id_document",
     *                     type="string",
     *                     format="binary",
     *                     description="Customer ID document (pdf, jpg, jpeg, png)"
     *                 ),
     *                 @OA\Property(
     *                     property="customer_id",
     *                     type="integer",
     *                     description="Required when uploading ID document; must belong to this booking",
     *                     example=101
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
            'customer_id' => 'required_if:id_document,!=,null|integer|exists:customer_infos,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$request->hasFile('id_document') && !$request->hasFile('receipt')) {
            return response()->json([
                'error' => 'You must upload either id_document or receipt, or both of them.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $booking = Booking::with('customerInfos')->find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], Response::HTTP_NOT_FOUND);
        }

        // Sales-only guard
        if ($user->hasRole('Sales') && $booking->created_by !== $user->id) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        DB::beginTransaction();
        try {
            // 1. Handle customer document
            if ($request->hasFile('id_document')) {
                $customer = $booking->customerInfos()
                    ->where('id', $request->customer_id)
                    ->first();

                if (!$customer) {
                    return response()->json(['message' => 'Customer not found in this booking'], Response::HTTP_NOT_FOUND);
                }

                $path = $request->file('id_document')->store('passports', 'local');
                $customer->update(['document_path' => $path]);
            }

            // 2. Handle payment receipt
            if ($request->hasFile('receipt')) {
                $path = $request->file('receipt')->store('receipts', 'local');
                $booking->update(['receipt_path' => $path]);
            }

            DB::commit();

            $booking->load('customerInfos');

            foreach ($booking->customerInfos as $customer) {
                $customer->document_url = $customer->document_path
                    ? route('bookings.download_document', [
                        'booking' => $booking->id,
                        'type' => 'passport',
                        'customer_id' => $customer->id
                    ])
                    : null;
            }

            $booking->receipt_url = $booking->receipt_path
                ? route('bookings.download_document', [
                    'booking' => $booking->id,
                    'type' => 'receipt'
                ])
                : null;

            return response()->json($booking, Response::HTTP_OK);

        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json(
                ['error' => $ex->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/bookings/{id}",
     *     summary="Update an existing booking",
     *     description="Update booking details (payment plan, agent, notes, etc.) and optionally update one customer that belongs to this booking.",
     *     operationId="updateBooking",
     *     tags={"Bookings"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the booking to update",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=false,
     *         description="Booking fields and optional customer data to update.",
     *
     *             @OA\JsonContent(
     *                 ref="#/components/schemas/BookingUpdateRequest",
     *                 example={
     *                     "payment_plan_id": 5,
     *                     "discount": 10,
     *                     "notes": "Customer requested to update passport and agent.",
     *                     "agent_id": 12,
     *                     "sale_source_id": 4,
     *                     "agency_com_agent": "2% external commission",
     *                     "customer_id": 33,
     *                     "name": "John Doe",
     *                     "passport_number": "A1234567",
     *                     "birth_date": "1991-01-15",
     *                     "gender": "Male",
     *                     "nationality": "Jordanian"
     *                 }
     *             ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/BookingUpdateRequest")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Booking updated successfully.",
     *         @OA\JsonContent(ref="#/components/schemas/Booking")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden. The authenticated user is not allowed to update this booking."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found, or specified customer does not belong to this booking."
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error.",
     *         @OA\JsonContent(
     *             type="object",
     *             example={
     *                 "payment_plan_id": {"The selected payment plan id is invalid."},
     *                 "customer_id": {"The customer id field is required when name is present."}
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected server error while updating the booking."
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        Log::info("User {$user->id} is updating the booking ID: {$id}.");

        $booking = Booking::with('customerInfos')->find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$user->can('edit booking', $booking)) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        if ($user->hasRole('Sales') && $booking->created_by !== $user->id) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'passport_number' => 'sometimes|required|string|max:50',
            'birth_date' => 'sometimes|required|date',
            'gender' => 'sometimes|required|string|max:10',
            'nationality' => 'sometimes|required|string|max:255',
            'payment_plan_id' => 'sometimes|integer|exists:payment_plans,id',
            'customer_id' => [
                'required_with:name,passport_number,birth_date,gender,nationality',
                'integer',
                'exists:customer_infos,id'
            ],

            'notes' => 'nullable|string',
            'agent_id' => [
                'sometimes', 'integer', 'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = User::find($value);
                    if ($user && $user->hasAnyRole(['Broker', 'Contractor'])) {
                        $fail("Selected agent cannot be a Broker or Contractor.");
                    }
                }
            ],
            'sale_source_id' => 'sometimes|integer|exists:users,id',
            'agency_com_agent' => 'nullable|string|max:255',

            'discount' => 'nullable|numeric|min:0|max:100|prohibited_if:payment_plan_id,null',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();

        try {
            // Update booking fields
            if ($request->has('payment_plan_id')) {
                if (!$booking->canChangePaymentPlan()) {
                    return response()->json([
                        'message' => 'Payment plan can only be changed for Pre-Booked or Booked bookings.'
                    ], Response::HTTP_FORBIDDEN);
                }

                $paymentPlan = PaymentPlan::where('id', $request->payment_plan_id)
                    ->first();

                if (!$paymentPlan) {
                    throw new \Exception('Selected payment plan does not belong to this unit.');
                }

                $booking->payment_plan_id = $paymentPlan->id;

                // Update Booking price details as per the new PP
                $unit = $booking->unit;
                $basePrice = $unit->price;
                $discountPct = $request->discount ?? 0;

                $bookingPrice = $discountPct > 0
                    ? round($basePrice * (1 - $discountPct / 100), 2)
                    : $basePrice;

                $booking->discount = $discountPct;
                $booking->price = $bookingPrice;

                // Delete all installments of the old PP
                $booking->installments()->delete();

                // Generate installment of the new PP
                $this->generateInstallmentOfPaymentPlan($unit, $paymentPlan, $discountPct, $booking);
            }

            if ($request->has('agent_id')) {
                $booking->agent_id = $request->agent_id;
            }

            if ($request->has('sale_source_id')) {
                $booking->sale_source_id = $request->sale_source_id;
            }

            if ($request->has('agency_com_agent')) {
                $booking->agency_com_agent = $request->agency_com_agent;
            }

            if ($request->has('notes')) {
                $booking->notes = $request->notes;
            }

            $booking->save();

            // Update specific customer info
            if ($request->filled('customer_id')) {
                $customer = $booking->customerInfos()->find($request->customer_id);

                if (!$customer) {
                    return response()->json(['message' => 'Customer not found in this booking'], Response::HTTP_NOT_FOUND);
                }

                if ($request->has('name')) {
                    $customer->name = $request->input('name');
                }
                if ($request->has('passport_number')) {
                    $customer->passport_number = $request->input('passport_number');
                }
                if ($request->has('birth_date')) {
                    $customer->birth_date = $request->input('birth_date');
                }
                if ($request->has('gender')) {
                    $customer->gender = $request->input('gender');
                }
                if ($request->has('nationality')) {
                    $customer->nationality = $request->input('nationality');
                }

                $customer->save();
            }

            DB::commit();
            $booking->load('customerInfos', 'agent', 'saleSource');

            return response()->json($booking, Response::HTTP_OK);

        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json(['error' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Cancel a booking and reset the unit status.
     *
     * @OA\Patch(
     *     path="/bookings/{id}/cancel",
     *     summary="Cancel a booking",
     *     description="Cancels a booking and resets the associated unit's status to 'Available'. Requires 'cancel booking' permission.",
     *     tags={"Bookings"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the booking to cancel",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Booking cancelled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Booking cancelled successfully."),
     *             @OA\Property(
     *                 property="booking",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(property="status", type="string", example="Cancelled"),
     *                 @OA\Property(
     *                     property="unit",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=45),
     *                     @OA\Property(property="status", type="string", example="Available")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden – user lacks permission to cancel bookings",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Booking not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error while cancelling booking",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to cancel booking.")
     *         )
     *     )
     * )
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        Log::info("User {$user->id} is attempting to cancel booking {$id}.");

        $booking = Booking::with('unit')->find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$user->can('cancel booking')) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        DB::beginTransaction();
        try {
            // Reset unit status
            if ($booking->unit) {
                $booking->unit->status = Unit::STATUS_PENDING;
                $booking->unit->save();
            }

            // Delete the booking
            $booking->status = Booking::STATUS_CANCELLED;
            $booking->save();

            DB::commit();
            return response()->json([
                'message' => 'Booking cancelled successfully.',
                'booking' => $booking,
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error("Error cancelling booking {$id}: " . $ex->getMessage());
            return response()->json(['error' => 'Failed to cancel booking.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Delete(
     *     path="/bookings/{id}",
     *     summary="Delete a booking and reset its unit to Available",
     *     operationId="destroyBooking",
     *     tags={"Bookings"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the booking to delete",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64", example=42)
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

        $booking = Booking::with('customerInfos', 'unit')->find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$user->can('delete booking')) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        DB::beginTransaction();
        try {
            // Optional: Delete related receipt file
            if ($booking->receipt_path) {
                Storage::disk('local')->delete($booking->receipt_path);
            }

            // Optional: Delete all customer document files
            foreach ($booking->customerInfos as $customer) {
                if ($customer->document_path) {
                    Storage::disk('local')->delete($customer->document_path);
                }
            }

            // Delete related CustomerInfo records
            $booking->customerInfos()->delete();

            // Reset unit status
            if ($booking->unit) {
                $booking->unit->status = Unit::STATUS_AVAILABLE;
                $booking->unit->save();
            }

            // Delete the booking
            $booking->delete();

            DB::commit();
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error("Error deleting booking {$id}: " . $ex->getMessage());
            return response()->json(['error' => 'Failed to delete booking.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
     *     @OA\Parameter(
     *         name="customer_id",
     *         in="query",
     *         description="Required only when type is passport — specifies which customer's ID document to download",
     *         required=false,
     *         @OA\Schema(type="integer", example=101)
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
     * @throws ValidationException
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
        $booking = Booking::with('customerInfos')->find($id);
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
            'passport' => function () use ($request, $booking) {
                $customerId = $request->query('customer_id');

                if (!$customerId) {
                    throw ValidationException::withMessages([
                        'customer_id' => 'The customer_id query parameter is required when downloading a passport.'
                    ]);
                }

                $customer = $booking->customerInfos()->find($customerId);

                if (!$customer || !$customer->document_path) {
                    throw new \Exception('Customer passport document not found.');
                }

                return $customer->document_path;
            },
            'receipt' => fn() => $booking->receipt_path,
            'rf' => fn() => optional($booking->reservationForm)->file_path,
            'signed_rf' => fn() => optional($booking->signedReservationForm)->signed_file_path,
            'spa' => fn() => optional($booking->spa)->file_path,
            'signed_spa' => fn() => optional($booking->signedSpa)->signed_file_path,
            'dld' => fn() => optional($booking->dldDocument)->file_path,
        ];

        try {
            $path = $getPath[$validated['type']]();
        } catch (ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $e) {
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
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // 3. Identify the user's role (assuming each user has exactly one main role)
        //    If users can have multiple roles, handle that logic here.
        $role = $user->getRoleNames()->first();
        if (!$role) {
            return response()->json(['error' => 'User has no role'], Response::HTTP_FORBIDDEN);
        }

        // 4. Check for existing approval from this same role (avoid duplicates)
        $existingApproval = $booking->approvals()
            ->where('approval_type', $role)
            ->where('status', 'Approved')
            ->first();

        if ($existingApproval) {
            return response()->json([
                'error' => "Booking already has an approval from role: {$role}."
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
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

            $booking->status = Booking::STATUS_RF_PENDING;
            $booking->save();

            $booking->unit->status = Unit::STATUS_BOOKED;
            $booking->unit->status_changed_at = now();
            $booking->unit->save();

            return response()->json([
                'message' => "Booking approved by {$role}.",
                'approval' => $approval,
            ], Response::HTTP_CREATED);
        }

        // 6. Non-CEO Flow => Need 2 Distinct Approvals: CSO & Accountant
        //    Check if the user's role is either "CSO" or "Accountant"
        if (!in_array($role, ['CSO', 'Accountant'])) {
            return response()->json([
                'error' => "Role {$role} is not allowed to approve bookings."
            ], Response::HTTP_FORBIDDEN);
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
            $booking->status = Booking::STATUS_RF_PENDING;
            $booking->save();

            $booking->unit->status = Unit::STATUS_BOOKED;
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

    /**
     * @param $unit
     * @param $paymentPlan
     * @param $discountPct
     * @param mixed $booking
     * @return void
     */
    public function generateInstallmentOfPaymentPlan($unit, $paymentPlan, $discountPct, mixed $booking): void
    {
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
    }

    private function getSaleSourceType($saleSource)
    {
        return $saleSource
            ? array_merge(
                $saleSource->only(['id', 'name', 'email', 'status']),
                ['type' => 'Broker Agency']
            )
            : ['id' => null, 'name' => null, 'email' => null, 'status' => null, 'type' => 'Direct'];
    }
}
