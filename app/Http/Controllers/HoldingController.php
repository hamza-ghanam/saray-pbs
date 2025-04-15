<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Booking;
use App\Models\Holding;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class HoldingController extends Controller
{
    /**
     * List all holding requests sorted by their status, with Pre-Hold first.
     *
     * @OA\Get(
     *     path="/holdings",
     *     summary="List all holding requests sorted by status",
     *     description="Returns a paginated list of holding requests sorted by status, with 'Pre-Hold' requests appearing first.",
     *     tags={"Holdings"},
     *     security={{"sanctum":{}}},
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
     *         description="A paginated list of holding requests sorted by status (Pre-Hold first)",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="unit_id", type="integer"),
     *                     @OA\Property(property="status", type="string", example="Pre-Hold"),
     *                     @OA\Property(property="created_by", type="integer"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                     @OA\Property(property="user", type="object", nullable=true,
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="email", type="string")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="per_page", type="integer", example=10),
     *             @OA\Property(property="total", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function listHoldings(Request $request)
    {
        $user = $request->user();
        Log::info("User {$user->id} requested listing of holding requests.");

        // Ensure the user has permission to view holding requests.
        if (!$user->can('approve hold')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        // Retrieve the dynamic limit from the request (default: 10, max: 100)
        $limit = min((int) $request->get('limit', 10), 100);

        // Sort such that "Pre-Hold" statuses appear first. The CASE statement sets Pre-Hold to 0 (highest priority).
        $holdings = Holding::with(['user', 'unit'])
            ->orderByRaw("CASE WHEN status = 'Pre-Hold' THEN 0 ELSE 1 END")
            ->paginate($limit);

        return response()->json($holdings, Response::HTTP_OK);
    }

    /**
     * Place a unit on hold (status set to "Pre-Hold").
     *
     * Only allowed if the unit is currently "Available" or "Cancelled".
     *
     * @OA\Post(
     *     path="/units/{id}/hold",
     *     summary="Place a unit on hold (Pre-Hold)",
     *     description="Sets a unit on hold by creating a holding record and changing its status to Pre-Hold. Only allowed if the unit is Available or Cancelled.",
     *     tags={"Units"},
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
     *         description="Holding created and unit status changed to Pre-Hold",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Holding is created and pending approval."),
     *             @OA\Property(property="holding", type="object")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden (user lacks permission)"),
     *     @OA\Response(response=404, description="Unit not found"),
     *     @OA\Response(response=422, description="Unit not in a valid state to hold (must be Available or Cancelled)")
     * )
     */
    public function hold(Request $request, $id)
    {
        $user = $request->user();
        Log::info("User {$user->id} is attempting to hold unit ID: {$id}");

        if (!$user->can('hold unit')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $unit = Unit::find($id);
        if (!$unit) {
            return response()->json(['error' => 'Unit not found'], Response::HTTP_NOT_FOUND);
        }

        if (!in_array($unit->status, ['Available', 'Cancelled'])) {
            return response()->json([
                'error' => 'Cannot hold a unit unless it is "Available" or "Cancelled".'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $holding = Holding::create([
                'unit_id'    => $id,
                'status'     => 'Pre-Hold',
                'created_by' => $user->id,
            ]);

            $unit->status = 'Pre-Hold';
            $unit->save();

            DB::commit();
            return response()->json([
                'message' => 'Holding is created and pending approval.',
                'holding' => $holding
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            DB::rollback();
            return response()->json(['error' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Approve or reject hold for a unit.
     *
     * This method handles both approval and rejection to avoid duplication.
     * It expects a query parameter "action" with value either "approve" or "reject".
     *
     * For approval:
     * - The holding and the associated unit must be in "Pre-Hold".
     * - The holding status will be updated to "Hold" and the unit to "Hold".
     * - An approval record is created with status "Approved".
     *
     * For rejection:
     * - The holding and unit must be in "Pre-Hold".
     * - The holding status will be updated to "Rejected" and the unit reset to "Available".
     * - An approval record is created with status "Rejected".
     *
     * @OA\Post(
     *     path="/units/hold/{id}/respond",
     *     summary="Respond to hold for a unit (approve or reject)",
     *     description="Approves or rejects a hold on a unit. The holding and unit must be in Pre-Hold status. Use the 'action' query parameter with values 'approve' or 'reject'.",
     *     operationId="respondHold",
     *     tags={"Holdings"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the holding record",
     *         required=true,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="action",
     *         in="query",
     *         description="Action to perform: 'approve' or 'reject'",
     *         required=true,
     *         @OA\Schema(type="string", example="approve")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Hold response processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Holding approved by CEO."),
     *             @OA\Property(property="approval", type="object")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden (user lacks permission or role)"),
     *     @OA\Response(response=404, description="Holding or Unit not found"),
     *     @OA\Response(response=422, description="Invalid state: Holding/Unit not in Pre-Hold or invalid action")
     * )
     */
    public function respondHold(Request $request, $id)
    {
        $user = $request->user();

        // Determine the action from query parameters: must be either 'approve' or 'reject'
        $action = $request->query('action');
        if (!in_array($action, ['approve', 'reject'])) {
            return response()->json(['error' => 'Invalid action. Must be "approve" or "reject".'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (($action === 'approve' && !$user->can('approve hold')) ||
            ($action === 'reject' && !$user->can('reject hold'))
        ) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        list($holding, $unit) = $this->getHoldingAndUnitOrFail($id);

        Log::info("User {$user->id} is responding to hold for unit ID: {$unit->id}");

        $role = $user->getRoleNames()->first();
        if (!$role) {
            return response()->json(['error' => 'User has no role'], Response::HTTP_FORBIDDEN);
        }

        if ($action === 'approve') { // approve
            $holding->status = 'Hold';
            $unit->status = 'Hold';
            $approvalStatus = 'Approved';
            $messageText = "Holding approved by {$role}.";
        } else { // reject
            $holding->status = 'Rejected';
            $unit->status = 'Available';
            $approvalStatus = 'Rejected';
            $messageText = "Holding rejected by {$role}.";
        }
        $unit->status_changed_at = now();

        DB::beginTransaction();
        try {
            $holding->save();
            $unit->save();
            $approval = Approval::create([
                'ref_id'        => $holding->id,
                'ref_type'      => 'App\Models\Holding',
                'approved_by'   => $user->id,
                'approval_type' => $role,
                'status'        => $approvalStatus,
            ]);
            DB::commit();
            return response()->json([
                'message'  => $messageText,
                'approval' => $approval,
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            DB::rollback();
            return response()->json(['error' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Retrieve the holding and associated unit, ensuring both are in "Pre-Hold" status.
     *
     * @param  int $id
     * @return array An array containing [$holding, $unit]
     */
    private function getHoldingAndUnitOrFail($id): array
    {
        $holding = Holding::find($id);
        if (!$holding) {
            abort(Response::HTTP_NOT_FOUND, 'Holding not found');
        }

        $unit = $holding->unit;
        if (!$unit) {
            abort(Response::HTTP_NOT_FOUND, 'Unit not found');
        }

        if ($holding->status !== 'Pre-Hold') {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Holding is not in Pre-Hold status.');
        }

        if ($unit->status !== 'Pre-Hold') {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Unit is not in Pre-Hold status.');
        }

        return [$holding, $unit];
    }
}
