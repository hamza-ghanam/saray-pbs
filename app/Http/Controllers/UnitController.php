<?php

namespace App\Http\Controllers;

use App\Events\UnitCreated;
use App\Models\Approval;
use App\Models\Building;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Info(
 *     title="Unit API",
 *     version="1.0",
 *     description="API for managing units in the Property Booking System"
 * )
 */

/**
 * @OA\Schema(
 *     schema="Unit",
 *     type="object",
 *     title="Unit",
 *     required={"prop_type", "unit_type", "unit_no", "floor", "suite_area", "total_area", "furnished", "unit_view", "price", "building_id", "status", "completion_date", "dld_fee_percentage", "admin_fee", "EOI"},
 *     @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *     @OA\Property(property="prop_type", type="string", example="Residential"),
 *     @OA\Property(property="unit_type", type="string", example="Apartment"),
 *     @OA\Property(property="unit_no", type="string", example="A101"),
 *     @OA\Property(property="floor", type="string", example="1"),
 *     @OA\Property(property="parking", type="string", example="Covered"),
 *     @OA\Property(property="pool_jacuzzi", type="string", example="None"),
 *     @OA\Property(property="suite_area", type="number", format="float", example=120.50),
 *     @OA\Property(property="balcony_area", type="number", format="float", example=15.75),
 *     @OA\Property(property="total_area", type="number", format="float", example=136.25),
 *     @OA\Property(property="furnished", type="boolean", example=true),
 *     @OA\Property(property="unit_view", type="string", example="City View"),
 *     @OA\Property(property="price", type="number", format="float", example=350000.00),
 *     @OA\Property(property="building_id", type="integer", example=5),
 *     @OA\Property(property="status", type="string", enum={"Pending", "Available", "Pre-Booked", "Booked", "Sold", "Pre-Hold", "Hold", "Cancelled"}, example="Pending"),
 *     @OA\Property(property="completion_date", type="string", format="date", example="2025-12-15"),
 *     @OA\Property(property="floor_plan", type="string", example="floor_plans/abc123.jpg"),
 *     @OA\Property(property="dld_fee_percentage", type="number", format="float", example=65000.00),
 *     @OA\Property(property="admin_fee", type="number", format="float", example=4000.00),
 *     @OA\Property(property="EOI", type="number", format="float", example=100000.00),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true, example="2025-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true, example="2025-01-02T00:00:00Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, readOnly=true, example=null)
 * )
 *  * @OA\Schema(
 *     schema="UnitInput",
 *     type="object",
 *     title="Unit Input",
 *     required={"prop_type", "unit_type", "unit_no", "floor", "suite_area", "furnished", "unit_view", "price", "building_id", "status", "completion_date", "dld_fee_percentage", "admin_fee"},
 *     @OA\Property(property="prop_type", type="string", example="Residential"),
 *     @OA\Property(property="unit_type", type="string", example="Apartment"),
 *     @OA\Property(property="unit_no", type="string", example="A101"),
 *     @OA\Property(property="floor", type="string", example="1"),
 *     @OA\Property(property="parking", type="string", example="Covered"),
 *     @OA\Property(property="pool_jacuzzi", type="string", example="None"),
 *     @OA\Property(property="suite_area", type="number", format="float", example=120.50),
 *     @OA\Property(property="balcony_area", type="number", format="float", example=15.75),
 *     @OA\Property(property="furnished", type="boolean", example=true),
 *     @OA\Property(property="unit_view", type="string", example="City View"),
 *     @OA\Property(property="price", type="number", format="float", example=350000.00),
 *     @OA\Property(property="building_id", type="integer", example=5),
 *     @OA\Property(property="status", type="string", enum={"Pending", "Available", "Pre-Booked", "Booked", "Sold", "Pre-Hold", "Hold", "Cancelled"}, example="Pending"),
 *     @OA\Property(property="completion_date", type="string", format="date", example="2025-12-15"),
 *     @OA\Property(property="floor_plan", type="string", format="binary", description="Optional floor plan file (jpg, jpeg, png)"),
 *     @OA\Property(property="dld_fee_percentage", type="number", format="float", example=65000.00),
 *     @OA\Property(property="admin_fee", type="number", format="float", example=4000.00),
 *     @OA\Property(property="EOI", type="number", format="float", example=100000.00),
 * )
 *  * @OA\Schema(
 *     schema="UnitWithPaymentPlans",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Unit"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(
 *                 property="paymentPlans",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/PaymentPlan")
 *             )
 *         )
 *     }
 * )
 *  * @OA\Schema(
 *     schema="PaymentPlan",
 *     type="object",
 *     title="Payment Plan",
 *     required={"unit_id", "name", "selling_price", "dld_fee_percentage", "admin_fee", "discount", "EOI", "booking_percentage", "handover_percentage", "construction_percentage", "first_construction_installment_date"},
 *     @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *     @OA\Property(property="unit_id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="60/40"),
 *     @OA\Property(property="selling_price", type="number", format="float", example=350000.00),
 *     @OA\Property(property="dld_fee_percentage", type="number", format="float", example=65000.00),
 *     @OA\Property(property="admin_fee", type="number", format="float", example=4000.00),
 *     @OA\Property(property="discount", type="number", format="float", example=0),
 *     @OA\Property(property="EOI", type="number", format="float", example=100000.00),
 *     @OA\Property(property="booking_percentage", type="number", format="float", example=20),
 *     @OA\Property(property="handover_percentage", type="number", format="float", example=40),
 *     @OA\Property(property="construction_percentage", type="number", format="float", example=40),
 *     @OA\Property(property="first_construction_installment_date", type="string", format="date", example="2025-03-15"),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true, example="2025-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true, example="2025-01-02T00:00:00Z")
 * )
 */
class UnitController extends Controller
{
    /**
     * Display a paginated listing of the units.
     *
     * @OA\Get(
     *     path="/units",
     *     summary="List all units with optional filters and pagination",
     *     tags={"Units"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="prop_type",
     *         in="query",
     *         description="Filter units by property type",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="unit_type",
     *         in="query",
     *         description="Filter units by unit type",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="unit_no",
     *         in="query",
     *         description="Filter units by unit number",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="floor",
     *         in="query",
     *         description="Filter units by floor",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter units by status",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"Pending","Available","Pre-Booked","Booked","Sold","Pre-Hold","Hold","Cancelled"}
     *         )
     *     ),
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
     *         description="Number of items per page (max 100)",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="A paginated list of units",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id",               type="integer", format="int64", example=1),
     *                     @OA\Property(property="prop_type",        type="string",                example="Residential"),
     *                     @OA\Property(property="unit_type",        type="string",                example="3 Bedroom"),
     *                     @OA\Property(property="unit_no",          type="string",                example="203"),
     *                     @OA\Property(property="floor",            type="string",                example="2"),
     *                     @OA\Property(property="suite_area",       type="number", format="float", example=102.04),
     *                     @OA\Property(property="balcony_area",     type="number", format="float", example=38.15),
     *                     @OA\Property(property="total_area",       type="number", format="float", example=140.19),
     *                     @OA\Property(property="furnished",        type="boolean",               example=true),
     *                     @OA\Property(property="unit_view",        type="string",                example="-"),
     *                     @OA\Property(property="price",            type="number", format="float", example=1621554.74),
     *                     @OA\Property(property="status",           type="string",                example="Booked"),
     *                     @OA\Property(property="completion_date",  type="string", format="date",  example="2025-12-15"),
     *                     @OA\Property(
     *                         property="floor_plan_url",
     *                         type="string",
     *                         format="url",
     *                         nullable=true,
     *                         example="https://your-domain.com/api/units/1/floor_plan"
     *                     ),
     *                     @OA\Property(
     *                         property="approvals",
     *                         type="array",
     *                         @OA\Items(ref="#/components/schemas/Approval")
     *                     ),
     *                     @OA\Property(
     *                         property="latest_holding",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="id",          type="integer", format="int64", example=2),
     *                         @OA\Property(property="status",      type="string",                example="Hold"),
     *                         @OA\Property(property="created_by",  type="integer", format="int64", example=17),
     *                         @OA\Property(property="created_at",  type="string", format="date-time", example="2025-05-02T15:58:33Z"),
     *                         @OA\Property(
     *                             property="user",
     *                             type="object",
     *                             @OA\Property(property="id",    type="integer", format="int64", example=17),
     *                             @OA\Property(property="name",  type="string",                example="Sales"),
     *                             @OA\Property(property="email", type="string",                example="sales@test.com")
     *                         ),
     *                         @OA\Property(
     *                             property="approvals",
     *                             type="array",
     *                             @OA\Items(ref="#/components/schemas/Approval")
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="latest_booking",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="id",          type="integer", format="int64", example=6),
     *                         @OA\Property(property="status",      type="string",                example="RF Pending"),
     *                         @OA\Property(property="created_by",  type="integer", format="int64", example=17),
     *                         @OA\Property(property="created_at",  type="string", format="date-time", example="2025-05-02T16:08:40Z"),
     *                         @OA\Property(
     *                             property="user",
     *                             type="object",
     *                             @OA\Property(property="id",    type="integer", format="int64", example=17),
     *                             @OA\Property(property="name",  type="string",                example="Sales"),
     *                             @OA\Property(property="email", type="string",                example="sales@test.com")
     *                         ),
     *                         @OA\Property(
     *                             property="approvals",
     *                             type="array",
     *                             @OA\Items(ref="#/components/schemas/Approval")
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="building",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="id",       type="integer", format="int64", example=1),
     *                         @OA\Property(property="name",     type="string",                example="Cove Edition Residence"),
     *                         @OA\Property(property="location", type="string",                example="Dubailand Residential Complex"),
     *                         @OA\Property(property="status",   type="string",                example="Off-Plan")
     *                     )
     *                 )
     *             )
     *         ),
     *         @OA\Property(property="current_page", type="integer", example=1),
     *         @OA\Property(property="last_page",    type="integer", example=5),
     *         @OA\Property(property="per_page",     type="integer", example=10),
     *         @OA\Property(property="total",        type="integer", example=50)
     *     ),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        Log::info("User {$user->id} requested unit listing.");

        if (!$user->can('view unit')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        // If the user has the Sales role, restrict to available/cancelled units.
        if ($user->hasRole('Sales')) {
            $salesId = $user->id;

            $query = Unit::where(function ($q) use ($salesId) {
                $q->whereIn('status', ['Available', 'Cancelled'])
                    ->orWhereHas('bookings', function ($b) use ($salesId) {
                        $b->where('created_by', $salesId)
                            ->where('status', '!=', 'Cancelled');
                    })
                    ->orWhereHas('holdings', function ($h) use ($salesId) {
                        $h->where('created_by', $salesId)
                            ->whereIn('status', ['Hold', 'Pre-Hold', 'Processed']);
                    });
            });
        } elseif ($user->hasRole('Broker')) {
            $brokerId = $user->id;

            $query = Unit::where(function ($q) use ($brokerId) {
                $q->where('status', 'Available')
                    ->orWhereHas('holdings', function ($h) use ($brokerId) {
                        $h->where('created_by', $brokerId)
                            ->whereIn('status', ['Hold', 'Pre-Hold', 'Processed']);
                    });
            });
        } else {
            $query = Unit::query();
        }

        // Apply filtering based on query parameters
        if ($request->filled('prop_type')) {
            $query->where('prop_type', 'like', "%" . $request->input('prop_type') . "%");
        }

        if ($request->filled('unit_type')) {
            $query->where('unit_type', 'like', "%" . $request->input('unit_type') . "%");
        }

        if ($request->filled('unit_no')) {
            $query->where('unit_no', 'like', "%" . $request->input('unit_no') . "%");
        }

        if ($request->filled('floor')) {
            $query->where('floor', $request->input('floor'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Dynamic pagination: retrieve 'limit', cast to integer, and cap at 100 items per page.
        $limit = min((int)$request->get('limit', 10), 100);
        $units = $query
            ->with([
                'building',
                'approvals',
                'paymentPlans.installments',
                'latestHolding.user',
                'latestHolding.approvals',
                'latestBooking.user',
                'latestBooking.approvals',
            ])
            ->paginate($limit);

        return response()->json($units, Response::HTTP_OK);
    }

    /**
     * Store a newly created unit.
     *
     * @OA\Post(
     *     path="/units",
     *     summary="Create a new unit",
     *     tags={"Units"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/UnitInput")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Unit created successfully with its payment plans and building information",
     *         @OA\JsonContent(ref="#/components/schemas/UnitWithPaymentPlans")
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function store(Request $request)
    {
        //  return response()->json($request->all());
        $user = $request->user();
        Log::info("User {$user->id} is attempting to add a new unit.");

        if (!$user->can('add unit')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        $validator = Validator::make($request->all(), [
            'building_id' => 'required|exists:buildings,id',
            'prop_type' => 'required|string|max:255',
            'unit_type' => 'required|string|max:255',
            'unit_no'     => [
                'required',
                'string',
                Rule::unique('units','unit_no')
                    ->where(fn($q) => $q->where('building_id', $request->building_id)),
            ],
            'floor' => 'required|string|max:50',
            'parking' => 'nullable|string|max:255',
            'pool_jacuzzi' => 'nullable|string|max:255',
            'suite_area' => 'required|numeric',
            'balcony_area' => 'nullable|numeric',
            'furnished' => 'required|boolean',
            'unit_view' => 'required|string|max:255',
            'price' => 'required|numeric',
            'completion_date' => 'nullable|date|after_or_equal:today',
            'floor_plan' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'dld_fee_percentage' => 'required|numeric',
            'admin_fee' => 'required|numeric',
            'EOI' => 'nullable|numeric',
            'FCID' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $validator->validated();

        // Process file upload for floor_plan if provided.
        if ($request->hasFile('floor_plan')) {
            $path = $request->file('floor_plan')->store('floor_plans', 'local');
            $data['floor_plan'] = $path;
        }

        // Auto-calculate total_area = suite_area + (balcony_area or 0)
        $data['total_area'] = $data['suite_area'] + ($data['balcony_area'] ?? 0);
        $data['status'] = 'Pending';
        $data['status_changed_at'] = now();

        DB::beginTransaction();

        try {
            // Create the Unit record.
            $unit = Unit::create($data);

            // Update unit with additional payment fields
            $unit->dld_fee_percentage = $data['dld_fee_percentage'];
            $unit->admin_fee = $data['admin_fee'];
            $unit->EOI = $data['EOI'] ?? 100000;
            //$unit->FCID = $data['FCID'];
            $unit->floor_plan = $unit->floor_plan ? route('units.floor_plan', ['id' => $unit->id]) : null;

            // Dispatch an event to generate payment plans for the unit.
            event(new UnitCreated($unit));

            // Eager-load the payment plans (and their installments) and the building that contains the unit.
            $unit->load('paymentPlans.installments', 'building', 'approvals');
        } catch (\Exception $ex) {
            DB::rollback();
            return response()->json(['error' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        DB::commit();

        return response()->json($unit, Response::HTTP_CREATED);
    }

    /**
     * Display the specified unit.
     *
     * @OA\Get(
     *     path="/units/{id}",
     *     summary="Get unit details",
     *     tags={"Units"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the unit",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unit details retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id",              type="integer",               example=1),
     *             @OA\Property(property="prop_type",       type="string",                example="Residential"),
     *             @OA\Property(property="unit_type",       type="string",                example="Apartment"),
     *             @OA\Property(property="unit_no",         type="string",                example="A101"),
     *             @OA\Property(property="floor",           type="string",                example="1"),
     *             @OA\Property(property="suite_area",      type="number", format="float", example=120.50),
     *             @OA\Property(property="balcony_area",    type="number", format="float", example=15.75),
     *             @OA\Property(property="total_area",      type="number", format="float", example=136.25),
     *             @OA\Property(property="furnished",       type="boolean",               example=true),
     *             @OA\Property(property="unit_view",       type="string",                example="City View"),
     *             @OA\Property(property="price",           type="number", format="float", example=350000.00),
     *             @OA\Property(property="status",          type="string",                example="Available"),
     *             @OA\Property(property="completion_date", type="string", format="date",  example="2025-12-15"),
     *             @OA\Property(
     *                 property="floor_plan_url",
     *                 type="string",
     *                 format="url",
     *                 nullable=true,
     *                 description="Authenticated URL to fetch the unit’s floor plan",
     *                 example="https://your-domain.com/api/units/1/floor_plan"
     *             ),
     *             @OA\Property(
     *                 property="approvals",
     *                 type="array",
     *                 description="All approvals directly on this unit",
     *                 @OA\Items(ref="#/components/schemas/Approval")
     *             ),
     *             @OA\Property(
     *                 property="payment_plans",
     *                 type="array",
     *                 description="Payment plans with nested installments",
     *                 @OA\Items(ref="#/components/schemas/PaymentPlan")
     *             ),
     *             @OA\Property(
     *                 property="latest_holding",
     *                 type="object",
     *                 nullable=true,
     *                 description="The most recent holding by the authenticated user",
     *                 @OA\Property(property="id",         type="integer", format="int64", example=5),
     *                 @OA\Property(property="unit_id",    type="integer", format="int64", example=1),
     *                 @OA\Property(property="status",     type="string",                example="Hold"),
     *                 @OA\Property(property="created_by", type="integer", format="int64", example=2),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-15T10:00:00Z"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     description="User who created the hold",
     *                     @OA\Property(property="id",    type="integer", format="int64", example=2),
     *                     @OA\Property(property="name",  type="string",                example="Jane Sales"),
     *                     @OA\Property(property="email", type="string",                example="jane.sales@example.com")
     *                 ),
     *                 @OA\Property(
     *                     property="approvals",
     *                     type="array",
     *                     description="Approvals for this holding",
     *                     @OA\Items(ref="#/components/schemas/Approval")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="latest_booking",
     *                 type="object",
     *                 nullable=true,
     *                 description="The most recent booking by the authenticated user",
     *                 @OA\Property(property="id",         type="integer", format="int64", example=6),
     *                 @OA\Property(property="unit_id",    type="integer", format="int64", example=1),
     *                 @OA\Property(property="status",     type="string",                example="Booked"),
     *                 @OA\Property(property="created_by", type="integer", format="int64", example=2),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-15T11:00:00Z"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     description="User who created the booking",
     *                     @OA\Property(property="id",    type="integer", format="int64", example=2),
     *                     @OA\Property(property="name",  type="string",                example="Jane Sales"),
     *                     @OA\Property(property="email", type="string",                example="jane.sales@example.com")
     *                 ),
     *                 @OA\Property(
     *                     property="approvals",
     *                     type="array",
     *                     description="Approvals for this booking",
     *                     @OA\Items(ref="#/components/schemas/Approval")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="building",
     *                 type="object",
     *                 nullable=true,
     *                 description="Building to which the unit belongs",
     *                 @OA\Property(property="id",       type="integer", format="int64", example=5),
     *                 @OA\Property(property="name",     type="string",                example="Building A"),
     *                 @OA\Property(property="location", type="string",                example="Downtown"),
     *                 @OA\Property(property="status",   type="string",                example="Active")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Unit not found")
     * )
     */
    public function show(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $salesId = $user->id;

        Log::info("User {$user->id} requested details for unit ID: {$id}");

        if (!$user->can('view unit')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        $unit = Unit::find($id);
        if (!$unit) {
            return response()->json(['message' => 'Unit not found'], Response::HTTP_NOT_FOUND);
        }

        // Sales‐only guard
        if ($user->hasRole('Sales')) {
            $isOpen = in_array($unit->status, ['Available', 'Cancelled']);
            $hasMyBooking = $unit->bookings()
                    ->where('created_by', $salesId)
                    ->where('status', '!=', 'Cancelled')
                    ->exists() && in_array($unit->status, ['Pre-Booked','Booked']);
            $hasMyHolding = $unit->holdings()
                    ->where('created_by', $salesId)
                    ->whereIn('status',['Hold','Pre-Hold', 'Processed'])
                    ->exists() && in_array($unit->status, ['Hold','Pre-Hold', 'Processed']);

            if (! ($isOpen || $hasMyBooking || $hasMyHolding)) {
                return response()->json([
                    'message' => 'Unit not available for you at this stage.'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        // Eager-load only what you actually return
        $unit->load([
            'building',
            'approvals',
            'paymentPlans.installments',
            'latestHolding.user',
            'latestHolding.approvals',
            'latestBooking.user',
            'latestBooking.approvals',
        ]);

        // 2) Turn your stored path into the authenticated URL
        $unit->floor_plan_url = $unit->floor_plan
            ? route('units.floor_plan', ['id' => $unit->id])
            : null;

        // 3) Hide the raw `floor_plan` attribute (the path)
        $unit->makeHidden(['floor_plan']);

        return response()->json($unit, Response::HTTP_OK);
    }

    /**
     * Update the specified unit.
     *
     * @OA\Put(
     *     path="/units/{id}",
     *     summary="Update an existing unit",
     *     tags={"Units"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the unit to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="prop_type", type="string", example="Residential"),
     *                 @OA\Property(property="unit_type", type="string", example="Apartment"),
     *                 @OA\Property(property="unit_no", type="string", example="A101"),
     *                 @OA\Property(property="floor", type="string", example="1"),
     *                 @OA\Property(property="parking", type="string", example="Covered"),
     *                 @OA\Property(property="pool_jacuzzi", type="string", example="None"),
     *                 @OA\Property(property="suite_area", type="number", format="float", example=120.50),
     *                 @OA\Property(property="balcony_area", type="number", format="float", example=15.75),
     *                 @OA\Property(property="furnished", type="boolean", example=true),
     *                 @OA\Property(property="unit_view", type="string", example="City View"),
     *                 @OA\Property(property="price", type="number", format="float", example=350000.00),
     *                 @OA\Property(property="building_id", type="integer", example=5),
     *                 @OA\Property(property="status", type="string", enum={"Pending", "Available", "Pre-Booked", "Booked", "Sold", "Pre-Hold", "Hold", "Cancelled"}, example="Available"),
     *                 @OA\Property(property="floor_plan", type="string", format="binary", description="Optional floor plan file (jpg, jpeg, png)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unit updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Unit")
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        Log::info("User {$user->id} is attempting to update unit ID: {$id}");

        if (!$user->can('edit unit')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        $unit = Unit::findOrFail($id);
        $buildingId = $request->input('building_id', $unit->building_id);

        $validator = Validator::make($request->all(), [
            'prop_type' => 'sometimes|required|string|max:255',
            'unit_type' => 'sometimes|required|string|max:255',
            'unit_no'     => [
                'required',
                'string',
                Rule::unique('units', 'unit_no')
                    ->ignore($unit->id)
                    ->where(fn($query) =>
                        $query->where('building_id', $buildingId)
                    ),
            ],
            'floor' => 'sometimes|required|string|max:50',
            'parking' => 'nullable|string|max:255',
            'pool_jacuzzi' => 'nullable|string|max:255',
            'suite_area' => 'sometimes|required|numeric',
            'balcony_area' => 'nullable|numeric',
            'furnished' => 'sometimes|required|boolean',
            'unit_view' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric',
            'building_id' => 'sometimes|required|exists:buildings,id',
            'status' => 'sometimes|required|in:Pending,Available,Pre-Booked,Booked,Sold,Pre-Hold,Hold,Cancelled',
            'floor_plan' => 'sometimes|nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // For Sales role, only allow update if unit status is "Available" or "Cancelled"
        if ($user->hasRole('Sales') && !in_array($unit->status, ['Available', 'Cancelled'])) {
            return response()->json(['message' => 'Unit cannot be updated as it is not available or cancelled'], Response::HTTP_FORBIDDEN);
        }

        $data = $validator->validated();

        // Process file upload for floor_plan if provided.
        if ($request->hasFile('floor_plan')) {
            $path = $request->file('floor_plan')->store('floor_plans', 'local');
            $data['floor_plan'] = $path;
        }

        // Auto-calculate total_area if suite_area is provided.
        if (isset($data['suite_area'])) {
            // If balcony_area is not provided, use existing balcony_area or default 0.
            $existingBalcony = $unit->balcony_area ?? 0;
            $data['total_area'] = $data['suite_area'] + ($data['balcony_area'] ?? $existingBalcony);
        }
        // Alternatively, if balcony_area is provided but suite_area is not, you might recalc based on existing suite_area:
        if (!isset($data['suite_area']) && isset($data['balcony_area'])) {
            $existingSuite = $unit->suite_area;
            $data['total_area'] = $existingSuite + $data['balcony_area'];
        }

        $unit->update($data);
        $unit->floor_plan = $unit->floor_plan ? route('units.floor_plan', ['id' => $unit->id]) : null;

        // Eager-load the building relationship to attach the building information.
        $unit->load('building');
        return response()->json($unit, Response::HTTP_OK);
    }

    /**
     * Remove the specified unit.
     *
     * @OA\Delete(
     *     path="/units/{id}",
     *     summary="Delete a unit",
     *     tags={"Units"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the unit to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Unit deleted successfully"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        Log::info("User {$user->id} is attempting to delete unit ID: {$id}");

        if (!$user->can('delete unit')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        $unit = Unit::findOrFail($id);
        $unit->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Approve a unit.
     *
     * @OA\Post(
     *     path="/units/{id}/approve",
     *     summary="Approve a unit",
     *     tags={"Units"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the unit to approve",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unit approved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Unit")
     *     ),
     *     @OA\Response(response=400, description="Invalid unit status"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(Request $request, $id)
    {
        $unit = Unit::find($id);
        if (!$unit) {
            return response()->json(['message' => 'Unit not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();
        Log::info("User {$user->id} is attempting to approve unit ID: {$id}");

        if (!$user->can('approve unit')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        // Ensure the unit is in a state that can be approved (e.g., Pending)
        if ($unit->status !== 'Pending') {
            return response()->json(['message' => 'Unit is not in pending status'], Response::HTTP_BAD_REQUEST);
        }

        DB::beginTransaction();

        try {
            $unit->status = 'Available';
            $unit->status_changed_at = now();
            $unit->save();

            Approval::create([
                'ref_id' => $unit->id,
                'ref_type' => 'App\Models\Unit',
                'approved_by' => $request->user()->id,
                'approval_type' => $request->user()->getRoleNames()->first(),
                'status' => 'Approved',
            ]);

        } catch (\Exception $ex) {
            DB::rollback();
            return response()->json(['error' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        DB::commit();

        // Eager-load the building relationship to attach the building information.
        $unit->load('building');
        return response()->json($unit, Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/units/{unitId}/assign",
     *     summary="Assign a unit to a contractor",
     *     description="Assigns a unit to a contractor. The contractor must have the 'Contractor' role.",
     *     operationId="assignUnitToContractor",
     *     tags={"Units"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="unitId",
     *         in="path",
     *         description="ID of the unit to assign",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="ID of the contractor to assign the unit to",
     *         @OA\JsonContent(
     *             required={"contractor_id"},
     *             @OA\Property(property="contractor_id", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unit assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unit assigned to contractor successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function assignUnit(Request $request, $unitId)
    {
        $user = $request->user();
        Log::info("User {$user->id} requested to assign unit {$unitId} to a contractor.");

        // Authorization check for assigning a unit (you can adjust the permission as needed)
        if (!$user->can('assign unit')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        // Validate the contractor_id from the request
        $data = $request->validate([
            'contractor_id' => 'required|exists:users,id'
        ]);

        // Find the unit and the contractor
        $unit = Unit::findOrFail($unitId);
        $contractor = User::findOrFail($data['contractor_id']);

        // Ensure that the given user has the Contractor role
        if (!$contractor->hasRole('Contractor')) {
            return response()->json(['error' => 'User is not a contractor'], Response::HTTP_BAD_REQUEST);
        }

        // Assign the unit to the contractor
        $unit->contractor_id = $contractor->id;
        $unit->save();

        return response()->json(['message' => 'Unit assigned to contractor successfully'], Response::HTTP_OK);
    }

    public function showFloorPlan(Request $request, $id)
    {
        $unit = Unit::findOrFail($id);

        if (!auth()->user()->can('view unit')) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $path = $unit->floor_plan; // e.g. "floor_plans/xyz.png"
        if (! Storage::disk('local')->exists($path)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $fullPath = Storage::disk('local')->path($path);
        $lastModified = gmdate('D, d M Y H:i:s', filemtime($fullPath)) . ' GMT';
        $eTag = '"' . md5_file($fullPath) . '"';

        // If the client already has the latest, short-circuit with 304
        if ($request->headers->get('if-none-match') === $eTag ||
            $request->headers->get('if-modified-since') === $lastModified
        ) {
            return response('', 304)
                ->header('Cache-Control', 'no-cache, must-revalidate, max-age=0, proxy-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0')
                ->header('ETag', $eTag)
                ->header('Last-Modified', $lastModified);
        }

        // Otherwise send the file with no-cache + validators
        return response()->file($fullPath, [
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            'Cache-Control'       => 'no-cache, must-revalidate, max-age=0, proxy-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
            'Last-Modified'       => $lastModified,
            'ETag'                => $eTag,
        ]);
    }
}
