<?php

namespace App\Http\Controllers;

use App\Events\UnitCreated;
use App\Models\Approval;
use App\Models\Building;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UnitsImport;
use Maatwebsite\Excel\HeadingRowImport;
use ZipArchive;

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
 *     required={
 *         "id",
 *         "prop_type",
 *         "unit_type",
 *         "unit_no",
 *         "floor",
 *         "internal_square",
 *         "external_square",
 *         "furnished",
 *         "unit_view",
 *         "price",
 *         "min_price",
 *         "pre_lunch_price",
 *         "lunch_price",
 *         "building_id",
 *         "status",
 *         "status_changed_at"
 *     },
 *     @OA\Property(property="id",                  type="integer", format="int64", readOnly=true, example=1),
 *     @OA\Property(property="prop_type",           type="string",                example="Residential"),
 *     @OA\Property(property="unit_type",           type="string",                example="Apartment"),
 *     @OA\Property(property="unit_no",             type="string",                example="A101"),
 *     @OA\Property(property="floor",               type="string",                example="1"),
 *     @OA\Property(property="parking",             type="string",                example="Covered"),
 *     @OA\Property(property="amenity",        type="string",                example="None"),
 *     @OA\Property(property="internal_square",     type="number", format="float", example=120.50),
 *     @OA\Property(property="external_square",     type="number", format="float", example=15.75),
 *     @OA\Property(property="total_square",        type="number", format="float", example=136.25),
 *     @OA\Property(property="internal_square_m",  type="number", format="float", example=1295.83),
 *     @OA\Property(property="external_square_m",  type="number", format="float", example=169.57),
 *     @OA\Property(property="total_square_m",     type="number", format="float", example=1465.40),
 *     @OA\Property(property="furnished",           type="boolean",               example=true),
 *     @OA\Property(property="unit_view",           type="string",                example="City View"),
 *     @OA\Property(property="price",               type="number", format="float", example=350000.00),
 *     @OA\Property(property="min_price",           type="number", format="float", example=300000.00),
 *     @OA\Property(property="pre_lunch_price",     type="number", format="float", example=320000.00),
 *     @OA\Property(property="lunch_price",         type="number", format="float", example=340000.00),
 *     @OA\Property(property="building_id",         type="integer",               example=5),
 *     @OA\Property(property="status",              type="string",                example="Pending"),
 *     @OA\Property(property="status_changed_at",   type="string", format="date-time", example="2025-05-10T12:34:56Z"),
 *     @OA\Property(
 *         property="floor_plan_url",
 *         type="string",
 *         format="url",
 *         nullable=true,
 *         description="Authenticated URL to fetch the unit’s floor plan",
 *         example="https://your-domain.com/api/units/1/floor_plan"
 *     ),
 *     @OA\Property(property="created_at",          type="string", format="date-time", readOnly=true, example="2025-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at",          type="string", format="date-time", readOnly=true, example="2025-01-02T00:00:00Z"),
 *     @OA\Property(property="deleted_at",          type="string", format="date-time", nullable=true, readOnly=true, example=null)
 * ),
 *
 * @OA\Schema(
 *     schema="UnitInput",
 *     type="object",
 *     title="Unit Input",
 *     required={
 *         "building_id",
 *         "prop_type",
 *         "unit_type",
 *         "unit_no",
 *         "floor",
 *         "internal_square",
 *         "external_square",
 *         "furnished",
 *         "unit_view",
 *         "price",
 *         "min_price",
 *         "pre_lunch_price",
 *         "lunch_price",
 *         "status",
 *     },
 *     @OA\Property(property="building_id",        type="integer", example=5),
 *     @OA\Property(property="prop_type",          type="string",  example="Residential"),
 *     @OA\Property(property="unit_type",          type="string",  example="Apartment"),
 *     @OA\Property(property="unit_no",            type="string",  example="A101"),
 *     @OA\Property(property="floor",              type="string",  example="1"),
 *     @OA\Property(property="parking",            type="string",  example="Covered"),
 *     @OA\Property(property="amenity",       type="string",  example="None"),
 *     @OA\Property(property="internal_square",    type="number",  format="float", example=120.50),
 *     @OA\Property(property="external_square",    type="number",  format="float", example=15.75),
 *     @OA\Property(property="furnished",          type="boolean", example=true),
 *     @OA\Property(property="unit_view",          type="string",  example="City View"),
 *     @OA\Property(property="price",              type="number",  format="float", example=350000.00),
 *     @OA\Property(property="min_price",          type="number",  format="float", example=300000.00),
 *     @OA\Property(property="pre_lunch_price",    type="number",  format="float", example=320000.00),
 *     @OA\Property(property="lunch_price",        type="number",  format="float", example=340000.00),
 *     @OA\Property(property="status",             type="string",  example="Pending"),
 *     @OA\Property(
 *         property="floor_plan",
 *         type="string",
 *         format="binary",
 *         description="Optional floor plan file (jpg, jpeg, png)"
 *     ),
 * ),
 *
 * @OA\Schema(
 *     schema="PaymentPlan",
 *     type="object",
 *     title="Payment Plan",
 *     required={"unit_id", "name", "dld_fee_percentage", "admin_fee", "EOI", "booking_percentage", "handover_percentage", "construction_percentage"},
 *     @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *     @OA\Property(property="unit_id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="60/40"),
 *     @OA\Property(property="dld_fee_percentage", type="number", format="float", example=65000.00),
 *     @OA\Property(property="admin_fee", type="number", format="float", example=4000.00),
 *     @OA\Property(property="EOI", type="number", format="float", example=100000.00),
 *     @OA\Property(property="booking_percentage", type="number", format="float", example=20),
 *     @OA\Property(property="handover_percentage", type="number", format="float", example=40),
 *     @OA\Property(property="construction_percentage", type="number", format="float", example=40),
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
     *                     @OA\Property(property="internal_square",       type="number", format="float", example=102.04),
     *                     @OA\Property(property="external_square",     type="number", format="float", example=38.15),
     *                     @OA\Property(property="total_square",       type="number", format="float", example=140.19),
     *                     @OA\Property(property="furnished",        type="boolean",               example=true),
     *                     @OA\Property(property="unit_view",        type="string",                example="-"),
     *                     @OA\Property(property="price",            type="number", format="float", example=1621554.74),
     *                     @OA\Property(property="status",           type="string",                example="Booked"),
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
     *         @OA\JsonContent(ref="#/components/schemas/Unit")
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
            'unit_no' => [
                'required',
                'string',
                Rule::unique('units', 'unit_no')
                    ->where(fn($q) => $q->where('building_id', $request->building_id)),
            ],
            'floor' => 'required|string|max:50',
            'parking' => 'nullable|string|max:255',
            'amenity' => 'nullable|string|max:255',
            'internal_square' => 'required|numeric|min:1',
            'external_square' => 'nullable|numeric|min:0',
            'furnished' => 'required|boolean',
            'unit_view' => 'required|string|max:255',
            'price' => 'required|numeric',
            'min_price' => 'required|numeric',
            'pre_lunch_price' => 'required|numeric',
            'lunch_price' => 'required|numeric',
            'floor_plan' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
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

        $data['status'] = 'Pending';
        $data['status_changed_at'] = now();

        DB::beginTransaction();

        try {
            // Create the Unit record.
            $unit = Unit::create($data);

            $unit->floor_plan_path = $unit->floor_plan ? route('units.floor_plan', ['id' => $unit->id]) : null;

            // Dispatch an event to generate payment plans for the unit.
            // No need to link unit with PP
            // event(new UnitCreated($unit));

            // Eager-load the payment plans (and their installments) and the building that contains the unit.
            $unit->load('building', 'approvals');
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
     *             @OA\Property(property="internal_square",      type="number", format="float", example=120.50),
     *             @OA\Property(property="external_square",    type="number", format="float", example=15.75),
     *             @OA\Property(property="total_square",      type="number", format="float", example=136.25),
     *             @OA\Property(property="furnished",       type="boolean",               example=true),
     *             @OA\Property(property="unit_view",       type="string",                example="City View"),
     *             @OA\Property(property="price",           type="number", format="float", example=350000.00),
     *             @OA\Property(property="status",          type="string",                example="Available"),
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
            $isOpen = in_array($unit->status, [Unit::STATUS_AVAILABLE, Unit::STATUS_CANCELLED]);
            $hasMyBooking = $unit->bookings()
                    ->where('created_by', $salesId)
                    ->where('status', '!=', 'Cancelled')
                    ->exists() && in_array($unit->status, [Unit::STATUS_PRE_BOOKED, Unit::STATUS_BOOKED]);
            $hasMyHolding = $unit->holdings()
                    ->where('created_by', $salesId)
                    ->whereIn('status', ['Hold', 'Pre-Hold', 'Processed'])
                    ->exists() && in_array($unit->status, [Unit::STATUS_HOLD, Unit::STATUS_PRE_HOLD, Unit::STATUS_PROCESSED]);

            if (!($isOpen || $hasMyBooking || $hasMyHolding)) {
                return response()->json([
                    'message' => 'Unit not available for you at this stage.'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        // Eager-load only what you actually return
        $unit->load([
            'building',
            'approvals',
            'latestHolding.user',
            'latestHolding.approvals',
            'latestBooking.user',
            'latestBooking.approvals',
        ]);

        // 2) Turn your stored path into the authenticated URL
        $unit->floor_plan_url = $unit->floor_plan
            ? route('units.floor_plan', ['id' => $unit->id])
            : null;

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
     *                 @OA\Property(property="amenity", type="string", example="None"),
     *                 @OA\Property(property="internal_square", type="number", format="float", example=120.50),
     *                 @OA\Property(property="external_square", type="number", format="float", example=15.75),
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
            'unit_no' => [
                'required',
                'string',
                Rule::unique('units', 'unit_no')
                    ->ignore($unit->id)
                    ->where(fn($query) => $query->where('building_id', $buildingId)
                    ),
            ],
            'floor' => 'sometimes|required|string|max:50',
            'parking' => 'nullable|string|max:255',
            'amenity' => 'nullable|string|max:255',
            'internal_square' => 'sometimes|required|numeric',
            'external_square' => 'nullable|numeric',
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
        if ($user->hasRole('Sales') && !in_array($unit->status, [Unit::STATUS_AVAILABLE, Unit::STATUS_CANCELLED])) {
            return response()->json(['message' => 'Unit cannot be updated as it is not available or cancelled'], Response::HTTP_FORBIDDEN);
        }

        $data = $validator->validated();

        // Process file upload for floor_plan if provided.
        if ($request->hasFile('floor_plan')) {
            $path = $request->file('floor_plan')->store('floor_plans', 'local');
            $data['floor_plan'] = $path;
        }

        if (isset($data['internal_square'])) {
            // If external_square is not provided, use existing external_square or default 0.
            $existingBalcony = $unit->external_square ?? 0;
        }
        // Alternatively, if external_square is provided but internal_square is not, you might recalc based on existing internal_square:
        if (!isset($data['internal_square']) && isset($data['external_square'])) {
            $existingSuite = $unit->internal_square;
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
     *         name="id", in="path", description="ID of the unit to delete", required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Unit deleted successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Unit not found"),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict — unit has existing bookings or holdings",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Cannot delete a unit that has existing bookings or holdings.")
     *         )
     *     )
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

        if (
            $unit->bookings()->exists() ||
            $unit->holdings()->exists()
        ) {
            return response()->json([
                'error' => 'Cannot delete a unit that has existing bookings or holdings.'
            ], Response::HTTP_CONFLICT);
        }

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
     *         name="id", in="path", description="ID of the unit to approve", required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unit approved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Unit")
     *     ),
     *     @OA\Response(response=400, description="Invalid unit status"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Unit not found")
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
        if ($unit->status !== Unit::STATUS_PENDING) {
            return response()->json(['message' => 'Unit is not in pending status'], Response::HTTP_BAD_REQUEST);
        }

        DB::beginTransaction();

        try {
            $unit->status = Unit::STATUS_AVAILABLE;
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
        $unit->load(['building', 'approvals']);
        return response()->json($unit, Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/units/{unitId}/assign",
     *     summary="Assign a unit to a contractor",
     *     tags={"Units"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="unitId", in="path", description="ID of the unit to assign", required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
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
     *     @OA\Response(response=400, description="Bad Request — user is not a contractor"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Unit or contractor not found"),
     *     @OA\Response(response=422, description="Validation error")
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

    /**
     * @OA\Get(
     *     path="/units/{id}/floor_plan",
     *     summary="Retrieve the unit’s floor plan image",
     *     tags={"Units"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id", in="path", description="ID of the unit", required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Floor plan image binary",
     *         @OA\MediaType(mediaType="application/octet-stream")
     *     ),
     *     @OA\Response(response=304, description="Not Modified"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Floor plan not found")
     * )
     */
    public function showFloorPlan(Request $request, $id)
    {
        $unit = Unit::findOrFail($id);

        if (!auth()->user()->can('view unit')) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $path = $unit->floor_plan; // e.g. "floor_plans/xyz.png"
        if (!Storage::disk('local')->exists($path)) {
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

        // Otherwise, send the file with no-cache + validators
        return response()->file($fullPath, [
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            'Cache-Control' => 'no-cache, must-revalidate, max-age=0, proxy-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Last-Modified' => $lastModified,
            'ETag' => $eTag,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/units/import",
     *     summary="Bulk import units from Excel",
     *     tags={"Units"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"building_id","file"},
     *                 @OA\Property(property="building_id", type="integer", example=1),
     *                 @OA\Property(property="file",        type="file",    format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Units imported successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message",  type="string", example="Units imported successfully"),
     *             @OA\Property(property="imported", type="integer", example=120),
     *             @OA\Property(
     *                 property="skipped",
     *                 type="array",
     *                 description="List of unit_no(s) that were skipped (e.g. duplicates)",
     *                 @OA\Items(type="string", example="1109")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="building_id", type="array",
     *                 @OA\Items(type="string", example="The selected building_id is invalid.")
     *             ),
     *             @OA\Property(property="file", type="array",
     *                 @OA\Items(type="string", example="The file field is required.")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Import failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="error",   type="string", example="Import failed"),
     *             @OA\Property(property="details", type="string", example="Undefined array key 'use'")
     *         )
     *     )
     * )
     */
    public function importUnits(Request $request)
    {
        $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'file' => 'required|file|mimes:xlsx,xls,csv|max:30000',
        ]);

        $building = Building::findOrFail($request->building_id);

        $importer = new UnitsImport($building);

        try {
            DB::transaction(function () use ($importer, $request) {
                Excel::import($importer, $request->file('file'));
            });

            return response()->json([
                'message' => 'Units imported successfully',
                'imported' => UnitsImport::$totalCount,
                'skipped' => $importer->skippedRows,
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Import failed',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Post(
     *     path="/units/import-floor-plans",
     *     summary="Import floor plans from a ZIP file",
     *     description="Upload a ZIP file containing floor plans named by unit_no. Each file will be stored and assigned to the matching unit in the specified building.",
     *     operationId="importFloorPlans",
     *     tags={"Units"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"building_id", "file"},
     *                 @OA\Property(
     *                     property="building_id",
     *                     type="integer",
     *                     description="The ID of the building",
     *                     example=5
     *                 ),
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="ZIP file containing floor plans named by unit_no (e.g. 1109.jpg). Max size: 30 MB",
     *                     example="plans.zip"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Floor plans processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Floor plans processed successfully."),
     *             @OA\Property(
     *                 property="summary",
     *                 type="object",
     *                 @OA\Property(property="added", type="integer", example=5),
     *                 @OA\Property(property="replaced", type="integer", example=0),
     *                 @OA\Property(
     *                     property="skipped",
     *                     type="array",
     *                     @OA\Items(type="string", example="Skipped: unit_no '1234' not found in building 5")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or ZIP processing error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Could not open ZIP file")
     *         )
     *     )
     * )
     */
    public function importFloorPlans(Request $request)
    {
        $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'file' => 'required|file|mimes:zip|max:30000', // 50MB
        ]);

        $buildingId = $request->building_id;

        $zipFile = $request->file('file');
        $timestamp = now()->timestamp;

        // Save the ZIP into the 'local' disk, which maps to storage/app/private
        $tempZipPath = $zipFile->store('temp', 'local');

        // Build full path to ZIP file
        $fullZipPath = storage_path('app/private/' . ltrim($tempZipPath, '/\\'));

        if (!file_exists($fullZipPath)) {
            return response()->json(['error' => "ZIP file not found at path: {$fullZipPath}"], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Path to extract files
        $extractedPath = storage_path("app/private/temp/floor_plans_{$timestamp}");
        mkdir($extractedPath, 0777, true);

        // Extract
        $zip = new ZipArchive;
        if ($zip->open($fullZipPath) !== true) {
            return response()->json(['error' => 'Failed to open ZIP file'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $zip->extractTo($extractedPath);
        $zip->close();

        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $results = ['added' => 0, 'replaced' => 0, 'skipped' => []];

        foreach (File::files($extractedPath) as $file) {
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, $allowedExtensions)) {
                $results['skipped'][] = "Skipped '{$file->getFilename()}' (invalid extension)";
                continue;
            }

            $unitNo = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $unit = Unit::where('building_id', $buildingId)
                ->where('unit_no', $unitNo)
                ->first();

            if (!$unit) {
                $results['skipped'][] = "Skipped: unit_no '{$unitNo}' not found in building {$buildingId}";
                continue;
            }

            $newFileName = $unitNo . '.' . $file->getExtension();
            $storagePath = "floor_plans/{$newFileName}";

            $isReplacement = $unit->floor_plan && Storage::disk('local')->exists($unit->floor_plan);

            Storage::disk('local')->put($storagePath, file_get_contents($file->getRealPath()));

            $unit->floor_plan = $storagePath;
            $unit->save();

            $results[$isReplacement ? 'replaced' : 'added']++;
        }

        // Clean up
        Storage::disk('local')->delete($tempZipPath);
        File::deleteDirectory($extractedPath);

        return response()->json([
            'message' => 'Floor plans processed successfully.',
            'summary' => $results,
        ], Response::HTTP_OK);
    }
}
