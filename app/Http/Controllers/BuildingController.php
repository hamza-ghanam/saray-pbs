<?php

namespace App\Http\Controllers;

use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Info(
 *     title="Building API",
 *     version="1.0",
 *     description="API for managing buildings in the Property Booking System"
 * )
 */

/**
 * @OA\Schema(
 *     schema="Building",
 *     type="object",
 *     title="Building",
 *     required={"id", "name", "location", "status", "ecd", "added_by_id"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Building A"),
 *     @OA\Property(property="location", type="string", example="Downtown"),
 *     @OA\Property(property="status", type="string", example="Off-Plan"),
 *     @OA\Property(property="ecd", type="string", example="Q4-2026 (Estimated Completion Date)"),
 *     @OA\Property(property="added_by_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-01-02T00:00:00Z")
 * )
 */
class BuildingController extends Controller
{
    /**
     * Display a listing of buildings.
     *
     * @OA\Get(
     *     path="/buildings",
     *     summary="List all buildings",
     *     tags={"Building"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Filter buildings by name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="location",
     *         in="query",
     *         description="Filter buildings by location",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter buildings by status",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="A list of buildings",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Building"))
     *     ),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        Log::info('User ' . $user->id . ' called BuildingController@index');

        if (!$user->can('view building')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        $query = Building::query();

        // Filter by name if provided
        if ($request->filled('name')) {
            $name = $request->input('name');
            $query->where('name', 'like', "%{$name}%");
        }

        // Filter by location if provided
        if ($request->filled('location')) {
            $location = $request->input('location');
            $query->where('location', 'like', "%{$location}%");
        }

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $buildings = $query->get();

        return response()->json($buildings, Response::HTTP_OK);
    }

    /**
     * Store a newly created building in storage.
     *
     * @OA\Post(
     *     path="/buildings",
     *     summary="Create a new building",
     *     tags={"Building"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "location", "status", "ecd"},
     *             @OA\Property(property="name", type="string", example="Building A"),
     *             @OA\Property(property="location", type="string", example="Downtown"),
     *             @OA\Property(property="status", type="string", example="Off-Plan"),
     *             @OA\Property(property="ecd", type="string", example="Q4-2026")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Building created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Building")
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function store(Request $request)
    {
        $user = $request->user();
        Log::info('User ' . $user->id . ' called BuildingController@store');

        if (!$user->can('add building')) {
            abort(403, 'Unauthorized');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'status' => 'required|string|max:50',
            'ecd' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Here we set the added_by_id to the current user's id.
        $building = Building::create([
            'name' => $request->name,
            'location' => $request->location,
            'status' => $request->status,
            'ecd' => $request->ecd,
            'added_by_id' => $user->id,
        ]);

        return response()->json($building, 201);
    }

    /**
     * Display the specified building.
     *
     * @OA\Get(
     *     path="/buildings/{id}",
     *     summary="Get building details",
     *     tags={"Building"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the building",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Building details",
     *         @OA\JsonContent(ref="#/components/schemas/Building")
     *     ),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        Log::info('User ' . $user->id . ' called BuildingController@show for building id: ' . $id);

        if (!$user->can('view building')) {
            abort(403, 'Unauthorized');
        }

        $building = Building::findOrFail($id);
        return response()->json($building, 200);
    }

    /**
     * Update the specified building in storage.
     *
     * @OA\Put(
     *     path="/buildings/{id}",
     *     summary="Update an existing building",
     *     tags={"Building"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the building to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "location", "status", "ecd"},
     *             @OA\Property(property="name", type="string", example="Building A Updated"),
     *             @OA\Property(property="location", type="string", example="New Location"),
     *             @OA\Property(property="status", type="string", example="Off-Plan"),
     *             @OA\Property(property="ecd", type="string", example="Q4-2026")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Building updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Building")
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        Log::info('User ' . $user->id . ' called BuildingController@update for building id: ' . $id);

        if (!$user->can('edit building')) {
            abort(403, 'Unauthorized');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'status' => 'required|string|max:50',
            'ecd' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $building = Building::findOrFail($id);
        $building->update($request->only(['name', 'location', 'status', 'ecd']));

        return response()->json($building, 200);
    }

    /**
     * Remove the specified building from storage.
     *
     * @OA\Delete(
     *     path="/buildings/{id}",
     *     summary="Delete a building",
     *     tags={"Building"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the building to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Building deleted successfully"
     *     ),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        Log::info('User ' . $user->id . ' called BuildingController@destroy for building id: ' . $id);

        if (!$user->can('delete building')) {
            abort(403, 'Unauthorized');
        }

        $building = Building::findOrFail($id);
        $building->delete();

        return response()->json(null, 204);
    }
}
