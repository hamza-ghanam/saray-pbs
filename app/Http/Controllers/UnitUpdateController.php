<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\UnitUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class UnitUpdateController extends Controller
{
    /**
     * @OA\Get(
     *     path="/unit-updates",
     *     summary="List all unit updates",
     *     description="Returns a list of unit updates. Contractor users will only see updates for units assigned to them.",
     *     operationId="getUnitUpdates",
     *     tags={"UnitUpdates"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="unit_id", type="integer"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="attachment_path", type="string", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="unit", type="object", nullable=true,
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="status", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        Log::info("User {$user->id} requested the list of unit updates.");

        // Check permission: ensure user is allowed to view unit updates.
        if (!$user->can('view unit update')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        // For contractor users, restrict the updates to those associated with units assigned to them.
        if ($user->hasRole('Contractor')) {
            $unitUpdates = UnitUpdate::with('unit')
                ->whereHas('unit', function ($query) use ($user) {
                    $query->where('contractor_id', $user->id);
                })->get();
        } else {
            // For non-contractor users, return all unit updates.
            $unitUpdates = UnitUpdate::with(['unit' => function ($query) {
                $query->select('id', 'unit_no');
            }])->get();
        }

        return response()->json($unitUpdates, Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/units/{unitId}/updates",
     *     summary="List updates for a specific unit",
     *     description="Retrieves the list of updates associated with a particular unit. Contractor users can only view updates for units assigned to them.",
     *     operationId="listUnitUpdatesForUnit",
     *     tags={"UnitUpdates"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="unitId",
     *         in="path",
     *         description="ID of the unit",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of unit updates retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="unit_id", type="integer"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="attachment_path", type="string", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unit not found"
     *     )
     * )
     */
    public function listUnitUpdates(Request $request, $unitId)
    {
        $user = $request->user();
        Log::info("User {$user->id} requested list of updates for unit {$unitId}.");

        // Ensure the user has permission to view unit updates.
        if (!$user->can('view unit update')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        // Retrieve the unit with its updates
        $unit = Unit::with('unitUpdates')->findOrFail($unitId);

        // For contractor users, ensure they only view updates for their assigned units.
        if ($user->hasRole('Contractor') && $unit->contractor_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        // Return the updates for the specified unit
        $updates = $unit->unitUpdates;

        return response()->json($updates, Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/unit-updates/{updateId}",
     *     summary="Get unit update details",
     *     description="Retrieves the details of a specific unit update (excluding the attachment). Contractor users can only view updates for units assigned to them.",
     *     operationId="getUnitUpdateDetails",
     *     tags={"UnitUpdates"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="updateId",
     *         in="path",
     *         description="ID of the unit update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unit update details retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="unit_id", type="integer"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="unit", type="object", nullable=true,
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="status", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unit update not found"
     *     )
     * )
     */
    public function show(Request $request, $updateId)
    {
        $user = $request->user();
        Log::info("User {$user->id} requested details for unit update {$updateId}.");

        // Check permission: ensure the user has permission to view unit updates.
        if (!$user->can('view unit update')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        // Retrieve the unit update along with its associated unit details.
        $unitUpdate = UnitUpdate::with('unit')->findOrFail($updateId);

        // For contractor users, ensure they only view updates for units assigned to them.
        if ($user->hasRole('Contractor')) {
            if (!$unitUpdate->unit || $unitUpdate->unit->contractor_id !== $user->id) {
                abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
            }
        }

        // Prepare the response data without the attachment field.
        $data = [
            'id'          => $unitUpdate->id,
            'unit_id'     => $unitUpdate->unit_id,
            'description' => $unitUpdate->description,
            'created_at'  => $unitUpdate->created_at,
            'updated_at'  => $unitUpdate->updated_at,
            'unit'        => $unitUpdate->unit ? [
                'id'     => $unitUpdate->unit->id,
                'name'   => $unitUpdate->unit->name,
                'status' => $unitUpdate->unit->status,
            ] : null,
        ];

        return response()->json($data, Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/units/{unitId}/updates",
     *     summary="Add a new update for a unit",
     *     description="Adds a UnitUpdate to the specified unit. An optional attachment can be uploaded.",
     *     operationId="addUnitUpdate",
     *     tags={"UnitUpdates"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="unitId",
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
     *                 required={"description"},
     *                 @OA\Property(property="description", type="string", example="Repaired the broken window"),
     *                 @OA\Property(property="attachment", type="file", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Unit update created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unit update created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error"
     *     )
     * )
     */
    public function store(Request $request, $unitId)
    {
        $user = $request->user();
        Log::info("User {$user->id} requested to add an update for unit {$unitId}.");

        // Authorization check: adjust the permission as needed
        if (!$user->can('add unit update')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        // Validate the request data
        $data = $request->validate([
            'description' => 'required|string',
            'attachment'  => 'nullable|file|mimes:pdf,jpg,jpeg,png'
        ]);

        // Find the specified unit
        $unit = Unit::findOrFail($unitId);

        DB::beginTransaction();
        try {
            if ($request->hasFile('attachment')) {
                $path = $request->file('attachment')->store('unit_updates', 'local');
                $data['attachment_path'] = $path;
            }

            // Associate the update with the unit
            $data['unit_id'] = $unit->id;

            // Create the unit update record
            $unitUpdate = UnitUpdate::create($data);

            DB::commit();

            return response()->json([
                'message' => 'Unit update created successfully',
                'data' => $unitUpdate
            ], Response::HTTP_CREATED);
        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json([
                'error' => 'Server error',
                'message' => $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Delete(
     *     path="/unit-updates/{updateId}",
     *     summary="Delete a unit update",
     *     description="Deletes a specific unit update. Only authorized users can delete unit updates.",
     *     operationId="deleteUnitUpdate",
     *     tags={"UnitUpdates"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="updateId",
     *         in="path",
     *         description="ID of the unit update to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unit update deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unit update deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unit update not found"
     *     )
     * )
     */
    public function destroy(Request $request, $updateId)
    {
        $user = $request->user();
        Log::info("User {$user->id} requested deletion of unit update {$updateId}.");

        // Authorization check: ensure the user has permission to delete unit updates
        if (!$user->can('delete unit update')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        // Retrieve and delete the unit update
        $unitUpdate = UnitUpdate::findOrFail($updateId);
        $unitUpdate->delete();

        return response()->json(['message' => 'Unit update deleted successfully'], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/unit-updates/{updateId}/download-attachment",
     *     summary="Download unit update attachment",
     *     description="Downloads the attachment for a specific unit update if the user is authorized.",
     *     operationId="downloadUnitUpdateAttachment",
     *     tags={"UnitUpdates"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="updateId",
     *         in="path",
     *         description="ID of the unit update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attachment downloaded successfully"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     )
     * )
     */
    public function downloadAttachment($updateId)
    {
        $update = UnitUpdate::findOrFail($updateId);

        if (!$this->canDownloadAttachment($update)) {
            abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        // Serve file from the 'local' disk (adjust the disk if needed)
        return Storage::disk('local')->download($update->attachment_path);
    }

    /**
     * Check if the current user is authorized to download the unit update attachment.
     *
     * @param UnitUpdate $update
     * @return bool
     */
    private function canDownloadAttachment(UnitUpdate $update)
    {
        $user = auth()->user();

        // Allow download if the user is the contractor assigned to the unit.
        if ($update->unit && $update->unit->contractor_id === $user->id) {
            return true;
        }

        // Alternatively, allow if the user has permission to view unit updates.
        if ($user->can('view unit update')) {
            return true;
        }

        return false;
    }
}
