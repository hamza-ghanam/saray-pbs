<?php

namespace App\Http\Controllers;

use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
     * Display a paginated listing of buildings.
     *
     * @OA\Get(
     *     path="/buildings",
     *     summary="List all buildings with optional filters and pagination",
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
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="A paginated list of buildings",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Building")),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="per_page", type="integer", example=10),
     *             @OA\Property(property="total", type="integer", example=50)
     *         )
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

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->input('location') . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Dynamic pagination
        $limit = min((int)$request->get('limit', 10), 100);
        $buildings = $query->paginate($limit);

        return response()->json($buildings, Response::HTTP_OK);
    }

    /**
     * Store a newly created building in storage, optionally with an image.
     *
     * @OA\Post(
     *     path="/buildings",
     *     summary="Create a new building",
     *     tags={"Building"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "location", "status", "ecd"},
     *                 @OA\Property(property="name",     type="string", example="Building A"),
     *                 @OA\Property(property="location", type="string", example="Downtown"),
     *                 @OA\Property(property="status",   type="string", example="Off-Plan"),
     *                 @OA\Property(property="ecd",      type="string", example="Q4-2026"),
     *                 @OA\Property(
     *                     property="image",
     *                     type="string",
     *                     format="binary",
     *                     description="Optional building image (jpeg, png, gif)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Building created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id",            type="integer", format="int64", example=123),
     *             @OA\Property(property="name",          type="string",             example="Building A"),
     *             @OA\Property(property="location",      type="string",             example="Downtown"),
     *             @OA\Property(property="status",        type="string",             example="Off-Plan"),
     *             @OA\Property(property="ecd",           type="string",             example="Q4-2026"),
     *             @OA\Property(property="added_by_id",   type="integer",            example=45),
     *             @OA\Property(
     *                 property="image_url",
     *                 type="string",
     *                 format="url",
     *                 description="Authenticated URL to fetch the building’s image",
     *                 example="https://your-domain.com/api/buildings/123/image"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function store(Request $request)
    {
        $user = $request->user();
        Log::info("User {$user->id} called BuildingController@store");

        if (!$user->can('add building')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        // Validate inputs including optional image
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'status' => 'required|string|max:50',
            'ecd' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Gather validated data
        $data = $validator->validated();
        $data['added_by_id'] = $user->id;

        // If an image was uploaded, store it on the 'public' disk
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')
                ->store('building_images', 'local');
        }

        // Create building record
        $building = Building::create($data);

        $building->image_url = $building->image_path ? route('buildings.image', ['id' => $building->id]) : null;
        $building->makeHidden(['image_path']);

        return response()->json($building, Response::HTTP_CREATED);
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
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        $building = Building::findOrFail($id);
        $building->image_url = $building->image_path ? route('buildings.image', ['id' => $building->id]) : null;
        $building->makeHidden(['image_path']);

        return response()->json($building, Response::HTTP_OK);
    }

    /**
     * Update an existing building, optionally replacing its image.
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
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name",     type="string", example="Building A Updated"),
     *                 @OA\Property(property="location", type="string", example="New Location"),
     *                 @OA\Property(property="status",   type="string", example="Off-Plan"),
     *                 @OA\Property(property="ecd",      type="string", example="Q4-2026"),
     *                 @OA\Property(
     *                     property="image",
     *                     type="string",
     *                     format="binary",
     *                     description="Optional new building image (jpeg, png, gif)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Building updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id",          type="integer", example=123),
     *             @OA\Property(property="name",        type="string",  example="Building A Updated"),
     *             @OA\Property(property="location",    type="string",  example="New Location"),
     *             @OA\Property(property="status",      type="string",  example="Off-Plan"),
     *             @OA\Property(property="ecd",         type="string",  example="Q4-2026"),
     *             @OA\Property(property="added_by_id", type="integer", example=45),
     *             @OA\Property(
     *                 property="image_url",
     *                 type="string",
     *                 format="url",
     *                 description="Authenticated URL to fetch the building’s image",
     *                 example="https://your-domain.com/api/buildings/123/image"
     *             )
     *         )
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
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'location' => 'sometimes|string|max:255',
            'status' => 'sometimes|string|max:50',
            'ecd' => 'sometimes|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $building = Building::findOrFail($id);
        $data = $request->only(['name', 'location', 'status', 'ecd']);

        // If there's a new image, delete the old one and store the new
        if ($request->hasFile('image')) {
            // delete old file if it exists
            if ($building->image_path
                && Storage::disk('local')->exists($building->image_path)) {
                Storage::disk('local')->delete($building->image_path);
            }

            // store new upload & capture its path
            $path = $request->file('image')->store('building_images', 'local');
            $data['image_path'] = $path;
        }

        $building->update($data);

        $building->image_url = $building->image_path ? route('buildings.image', ['id' => $building->id]) : null;
        $building->makeHidden(['image_path']);

        return response()->json($building, Response::HTTP_OK);
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

    /**
     * Display a paginated listing of units within a specific building.
     *
     * @OA\Get(
     *     path="/buildings/{buildingId}/units",
     *     summary="List all units for a specific building with optional filters and pagination",
     *     tags={"Building"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="buildingId",
     *         in="path",
     *         description="ID of the building",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="unit_no",
     *         in="query",
     *         description="Filter units by unit number",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter units by status",
     *         required=false,
     *         @OA\Schema(type="string")
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
     *         description="A paginated list of units for the specified building",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Unit")),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="per_page", type="integer", example=10),
     *             @OA\Property(property="total", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Building not found")
     * )
     */
    public function getUnitsByBuilding(Request $request, $buildingId)
    {
        $user = $request->user();
        Log::info("User {$user->id} requested units for building {$buildingId}.");

        // Check permission: ensure the user can view units.
        if (!$user->can('view unit')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        // Retrieve the building; if not found, Laravel will throw a 404.
        $building = Building::findOrFail($buildingId);

        // Build the query using the units' relationship.
        $query = $building->units();

        // Apply optional filters.
        if ($request->filled('unit_no')) {
            $query->where('unit_no', 'like', '%' . $request->input('unit_no') . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Dynamic pagination: get the 'limit' (default 10, capped at 100)
        $limit = min((int)$request->get('limit', 10), 100);
        $units = $query->paginate($limit);

        return response()->json($units, Response::HTTP_OK);
    }

    public function showImage(Request $request, $id)
    {
        $building = Building::findOrFail($id);

        if (! auth()->user()->can('view building')) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $path = $building->image_path; // e.g. "building_images/xyz.png"
        if (! Storage::disk('local')->exists($path)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $fullPath = Storage::disk('local')->path($path);

        // Compute strong validators
        $lastModified = gmdate('D, d M Y H:i:s', filemtime($fullPath)) . ' GMT';
        $eTag         = '"' . md5_file($fullPath) . '"';

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
