<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class UnitHoldController extends Controller
{
    /**
     * Place a unit on hold (status set to "Pre-Hold").
     *
     * Only allowed if the unit is currently "Available" or "Cancelled".
     *
     * @OA\Post(
     *     path="/units/{id}/hold",
     *     summary="Place a unit on hold (Pre-Hold)",
     *     tags={"Unit"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the unit to hold",
     *         required=true,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unit status changed to Pre-Hold",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=10),
     *             @OA\Property(property="prop_type", type="string", example="Residential"),
     *             @OA\Property(property="unit_type", type="string", example="Apartment"),
     *             @OA\Property(property="unit_no", type="string", example="A101"),
     *             @OA\Property(property="floor", type="string", example="1"),
     *             @OA\Property(property="parking", type="string", example="Covered"),
     *             @OA\Property(property="pool_jacuzzi", type="string", example="None"),
     *             @OA\Property(property="suite_area", type="number", format="float", example=120.50),
     *             @OA\Property(property="balcony_area", type="number", format="float", example=15.75),
     *             @OA\Property(property="total_area", type="number", format="float", example=136.25),
     *             @OA\Property(property="furnished", type="boolean", example=true),
     *             @OA\Property(property="unit_view", type="string", example="City View"),
     *             @OA\Property(property="price", type="number", format="float", example=350000.00),
     *             @OA\Property(property="completion_date", type="string", format="date", example="2025-12-15"),
     *             @OA\Property(property="building_id", type="integer", example=5),
     *             @OA\Property(property="status", type="string", example="Pre-Hold"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-10T12:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-10T12:00:00Z")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden (user lacks permission)"),
     *     @OA\Response(response=404, description="Unit not found"),
     *     @OA\Response(
     *         response=422,
     *         description="Unit not in a valid state to hold (must be Available or Cancelled)"
     *     )
     * )
     */
    public function hold(Request $request, $id)
    {
        $user = $request->user();
        Log::info("User {$user->id} is attempting to hold unit ID: {$id}");

        // 1. Check permission if needed
        if (!$user->can('hold unit')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // 2. Retrieve the unit
        $unit = Unit::find($id);
        if (!$unit) {
            return response()->json(['error' => 'Unit not found'], Response::HTTP_NOT_FOUND);
        }

        // 3. Validate status
        if (!in_array($unit->status, ['Available', 'Cancelled'])) {
            return response()->json([
                'error' => 'Cannot hold a unit unless it is "Available" or "Cancelled".'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // 4. Set status to "Pre-Hold"
        $unit->status = 'Pre-Hold';
        $unit->save();

        return response()->json($unit, Response::HTTP_OK);
    }

    /**
     * Approve hold for a unit (status set to "Hold").
     *
     * Only allowed if the unit is currently in "Pre-Hold".
     *
     * @OA\Post(
     *     path="/units/{id}/hold/approve",
     *     summary="Approve hold for a unit",
     *     tags={"Unit"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the unit to approve hold",
     *         required=true,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unit status changed to Hold, status_changed_at recorded",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=10),
     *             @OA\Property(property="prop_type", type="string", example="Residential"),
     *             @OA\Property(property="unit_type", type="string", example="Apartment"),
     *             @OA\Property(property="unit_no", type="string", example="A101"),
     *             @OA\Property(property="floor", type="string", example="1"),
     *             @OA\Property(property="parking", type="string", example="Covered"),
     *             @OA\Property(property="pool_jacuzzi", type="string", example="None"),
     *             @OA\Property(property="suite_area", type="number", format="float", example=120.50),
     *             @OA\Property(property="balcony_area", type="number", format="float", example=15.75),
     *             @OA\Property(property="total_area", type="number", format="float", example=136.25),
     *             @OA\Property(property="furnished", type="boolean", example=true),
     *             @OA\Property(property="unit_view", type="string", example="City View"),
     *             @OA\Property(property="price", type="number", format="float", example=350000.00),
     *             @OA\Property(property="completion_date", type="string", format="date", example="2025-12-15"),
     *             @OA\Property(property="building_id", type="integer", example=5),
     *             @OA\Property(property="status", type="string", example="Hold"),
     *             @OA\Property(property="status_changed_at", type="string", format="date-time", example="2025-03-11T12:00:00Z"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-10T12:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-11T12:00:00Z")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden (user lacks permission)"),
     *     @OA\Response(response=404, description="Unit not found"),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot approve hold unless the unit is in 'Pre-Hold'"
     *     )
     * )
     */
    public function approveHold(Request $request, $id)
    {
        $user = $request->user();
        Log::info("User {$user->id} is approving hold for unit ID: {$id}");

        if (!$user->can('approve hold')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $unit = Unit::find($id);
        if (!$unit) {
            return response()->json(['error' => 'Unit not found'], Response::HTTP_NOT_FOUND);
        }

        if ($unit->status !== 'Pre-Hold') {
            return response()->json([
                'error' => 'Cannot approve hold unless the unit is in "Pre-Hold".'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Set status to "Hold" and record the status_changed_at time
        $unit->status = 'Hold';
        $unit->status_changed_at = now();
        $unit->save();

        return response()->json($unit, Response::HTTP_OK);
    }

    /**
     * Reject hold for a unit (resets status to "Available").
     *
     * Only allowed if the unit is currently in "Pre-Hold".
     *
     * @OA\Post(
     *     path="/units/{id}/hold/reject",
     *     summary="Reject hold for a unit",
     *     tags={"Unit"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the unit to reject hold",
     *         required=true,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unit status reset to Available",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=10),
     *             @OA\Property(property="prop_type", type="string", example="Residential"),
     *             @OA\Property(property="unit_type", type="string", example="Apartment"),
     *             @OA\Property(property="unit_no", type="string", example="A101"),
     *             @OA\Property(property="floor", type="string", example="1"),
     *             @OA\Property(property="parking", type="string", example="Covered"),
     *             @OA\Property(property="pool_jacuzzi", type="string", example="None"),
     *             @OA\Property(property="suite_area", type="number", format="float", example=120.50),
     *             @OA\Property(property="balcony_area", type="number", format="float", example=15.75),
     *             @OA\Property(property="total_area", type="number", format="float", example=136.25),
     *             @OA\Property(property="furnished", type="boolean", example=true),
     *             @OA\Property(property="unit_view", type="string", example="City View"),
     *             @OA\Property(property="price", type="number", format="float", example=350000.00),
     *             @OA\Property(property="completion_date", type="string", format="date", example="2025-12-15"),
     *             @OA\Property(property="building_id", type="integer", example=5),
     *             @OA\Property(property="status", type="string", example="Available"),
     *             @OA\Property(
     *                 property="status_changed_at",
     *                 type="string",
     *                 format="date-time",
     *                 nullable=true,
     *                 example=null
     *             ),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-10T12:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-10T12:00:00Z")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden (user lacks permission)"),
     *     @OA\Response(response=404, description="Unit not found"),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot reject hold unless the unit is in 'Pre-Hold'"
     *     )
     * )
     */
    public function rejectHold(Request $request, $id)
    {
        $user = $request->user();
        Log::info("User {$user->id} is rejecting hold for unit ID: {$id}");

        if (!$user->can('reject hold')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $unit = Unit::find($id);
        if (!$unit) {
            return response()->json(['error' => 'Unit not found'], Response::HTTP_NOT_FOUND);
        }

        if ($unit->status !== 'Pre-Hold') {
            return response()->json([
                'error' => 'Cannot reject hold unless the unit is in "Pre-Hold".'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Reset status to "Available"
        $unit->status = 'Available';
        $unit->status_changed_at = now();
        $unit->save();

        return response()->json($unit, Response::HTTP_OK);
    }
}
