<?php

namespace App\Http\Controllers;

use App\Events\UnitCreated;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
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
 *     required={"prop_type", "unit_type", "unit_no", "floor", "suite_area", "total_area", "furnished", "unit_view", "price", "building_id", "status", "completion_date", "dld_fee_percentage", "admin_fee", "EOI", "FCID"},
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
 *     @OA\Property(property="FCID", type="string", format="date", example="2025-03-15"),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true, example="2025-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true, example="2025-01-02T00:00:00Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, readOnly=true, example=null)
 * )
 *  * @OA\Schema(
 *     schema="UnitInput",
 *     type="object",
 *     title="Unit Input",
 *     required={"prop_type", "unit_type", "unit_no", "floor", "suite_area", "furnished", "unit_view", "price", "building_id", "status", "completion_date", "dld_fee_percentage", "admin_fee", "FCID"},
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
 *     @OA\Property(property="floor_plan", type="string", format="binary", description="Optional floor plan file (pdf, jpg, jpeg, png)"),
 *     @OA\Property(property="dld_fee_percentage", type="number", format="float", example=65000.00),
 *     @OA\Property(property="admin_fee", type="number", format="float", example=4000.00),
 *     @OA\Property(property="EOI", type="number", format="float", example=100000.00),
 *     @OA\Property(property="FCID", type="string", format="date", example="2025-03-15")
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
     * Display a listing of the units.
     *
     * @OA\Get(
     *     path="/units",
     *     summary="List all units",
     *     tags={"Unit"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="A list of units",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Unit"))
     *     ),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        Log::info("User {$user->id} called requested unit listing.");

        // If the user has the Sales role, show only available units.
        if ($user->hasRole('Sales')) {
            $units = Unit::whereIn('status', ['Available', 'Cancelled'])->get();
        } else {
            $units = Unit::all();
        }

        return response()->json($units, Response::HTTP_OK);
    }

    /**
     * Store a newly created unit.
     *
     * @OA\Post(
     *     path="/units",
     *     summary="Create a new unit",
     *     tags={"Unit"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/Unit")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Unit created successfully with its payment plans",
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
            abort(403, 'Unauthorized');
        }

        $validator = Validator::make($request->all(), [
            'prop_type'             => 'required|string|max:255',
            'unit_type'             => 'required|string|max:255',
            'unit_no'               => 'required|string|max:255',
            'floor'                 => 'required|string|max:50',
            'parking'               => 'nullable|string|max:255',
            'pool_jacuzzi'          => 'nullable|string|max:255',
            'suite_area'            => 'required|numeric',
            'balcony_area'          => 'nullable|numeric',
            'furnished'             => 'required|boolean',
            'unit_view'             => 'required|string|max:255',
            'price'                 => 'required|numeric',
            'completion_date'       => 'required|date|after_or_equal:today',
            'building_id'           => 'required|exists:buildings,id',
            'floor_plan'            => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'dld_fee_percentage'    => 'required|numeric',
            'admin_fee'             => 'required|numeric',
            'EOI'                   => 'nullable|numeric',
            'FCID'                  => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $validator->validated();

        // Process file upload for floor_plan if provided.
        if ($request->hasFile('floor_plan')) {
            $path = $request->file('floor_plan')->store('floor_plans', 'public');
            $data['floor_plan'] = $path;
        }

        // Auto-calculate total_area = suite_area + (balcony_area or 0)
        $data['total_area'] = $data['suite_area'] + ($data['balcony_area'] ?? 0);
        $data['status'] = 'Pending';

        DB::beginTransaction();

        try {
            // Create the Unit record.
            $unit = Unit::create($data);

            // Update unit with additional payment fields
            $unit->dld_fee_percentage = $data['dld_fee_percentage'];
            $unit->admin_fee = $data['admin_fee'];
            $unit->EOI = $data['EOI'] ?? 100000;
            $unit->FCID = $data['FCID'];

            // Dispatch an event to generate payment plans for the unit.
            event(new UnitCreated($unit));

            // Eager-load the payment plans (and their installments) to return them with the unit.
            $unit->load('paymentPlans.installments');
        } catch (\Exception $ex) {
            DB::rollback();
            return response()->json(['error' => $ex->getMessage()], 500);
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
     *     tags={"Unit"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the unit",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unit details retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/UnitWithPaymentPlans")
     *     ),
     *     @OA\Response(response=404, description="Unit not found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        Log::info("User {$user->id} requested details for unit ID: {$id}");

        if (!$user->can('view unit')) {
            abort(403, 'Unauthorized');
        }

        $unit = Unit::find($id);
        if (!$unit) {
            return response()->json(['message' => 'Unit not found'], Response::HTTP_NOT_FOUND);
        }

        // Sales users can only view if status is "Available" or "Cancelled"
        if ($user->hasRole('Sales') && !in_array($unit->status, ['Available', 'Cancelled'])) {
            return response()->json(['message' => 'Unit not available for sales role'], Response::HTTP_FORBIDDEN);
        }

        // Eager-load the payment plans (and their installments) to return them with the unit.
        $unit->load('paymentPlans.installments');

        return response()->json($unit, Response::HTTP_OK);
    }

    /**
     * Update the specified unit.
     *
     * @OA\Put(
     *     path="/units/{id}",
     *     summary="Update an existing unit",
     *     tags={"Unit"},
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
     *                 @OA\Property(property="floor_plan", type="string", format="binary", description="Optional floor plan file (pdf, jpg, jpeg, png)")
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
            abort(403, 'Unauthorized');
        }

        $validator = Validator::make($request->all(), [
            'prop_type'     => 'sometimes|required|string|max:255',
            'unit_type'     => 'sometimes|required|string|max:255',
            'unit_no'       => 'sometimes|required|string|unique:units,unit_no,' . $id,
            'floor'         => 'sometimes|required|string|max:50',
            'parking'       => 'nullable|string|max:255',
            'pool_jacuzzi'  => 'nullable|string|max:255',
            'suite_area'    => 'sometimes|required|numeric',
            'balcony_area'  => 'nullable|numeric',
            'furnished'     => 'sometimes|required|boolean',
            'unit_view'     => 'sometimes|required|string|max:255',
            'price'         => 'sometimes|required|numeric',
            'building_id'   => 'sometimes|required|exists:buildings,id',
            'status'        => 'sometimes|required|in:Pending,Available,Pre-Booked,Booked,Sold,Pre-Hold,Hold,Cancelled',
            'floor_plan'    => 'nullable|file|mimes:pdf,jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $unit = Unit::findOrFail($id);

        // For Sales role, only allow update if unit status is "Available" or "Cancelled"
        if ($user->hasRole('Sales') && !in_array($unit->status, ['Available', 'Cancelled'])) {
            return response()->json(['message' => 'Unit cannot be updated as it is not available or cancelled'], Response::HTTP_FORBIDDEN);
        }

        $data = $validator->validated();

        // Process file upload for floor_plan if provided.
        if ($request->hasFile('floor_plan')) {
            $path = $request->file('floor_plan')->store('floor_plans', 'public');
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
        return response()->json($unit, Response::HTTP_OK);
    }

    /**
     * Remove the specified unit.
     *
     * @OA\Delete(
     *     path="/units/{id}",
     *     summary="Delete a unit",
     *     tags={"Unit"},
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
            abort(403, 'Unauthorized');
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
     *     tags={"Unit"},
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
        $user = $request->user();
        Log::info("User {$user->id} is attempting to approve unit ID: {$id}");

        if (!$user->can('approve unit')) {
            abort(403, 'Unauthorized');
        }

        $unit = Unit::find($id);
        if (!$unit) {
            return response()->json(['message' => 'Unit not found'], Response::HTTP_NOT_FOUND);
        }

        // Ensure the unit is in a state that can be approved (e.g., Pending)
        if ($unit->status !== 'Pending') {
            return response()->json(['message' => 'Unit is not in pending status'], Response::HTTP_BAD_REQUEST);
        }

        // For example, set the unit status to "Available" once approved.
        $unit->status = 'Available';
        $unit->save();

        return response()->json($unit, Response::HTTP_OK);
    }
}
