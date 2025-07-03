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
 *     required={"id","name","location","status","ecd","added_by_id"},
 *     @OA\Property(property="id",           type="integer", format="int64", example=1),
 *     @OA\Property(property="name",         type="string",             example="Building A"),
 *     @OA\Property(property="location",     type="string",             example="Downtown"),
 *     @OA\Property(property="status",       type="string",             example="Off-Plan"),
 *     @OA\Property(property="ecd",          type="date",             example="2027-30-12"),
 *     @OA\Property(property="added_by_id",  type="integer",            example=1),
 *     @OA\Property(property="created_at",   type="string", format="date-time", example="2025-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at",   type="string", format="date-time", example="2025-01-02T00:00:00Z"),
 *     @OA\Property(
 *         property="image_url",
 *         type="string",
 *         format="url",
 *         description="URL to fetch the building’s image",
 *         example="https://your-domain.com/api/buildings/1/image"
 *     )
 * )
 */
class BuildingController extends Controller
{
    /**
     * Display a paginated listing of buildings.
     *
     * @OA\Get(
     *     path="/api/buildings",
     *     summary="List all buildings with optional filters and pagination",
     *     tags={"Building"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="name",     in="query", description="Filter by name",     @OA\Schema(type="string")),
     *     @OA\Parameter(name="location", in="query", description="Filter by location", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status",   in="query", description="Filter by status",   @OA\Schema(type="string")),
     *     @OA\Parameter(name="page",     in="query", description="Page number",         @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="limit",    in="query", description="Items per page (max 100)", @OA\Schema(type="integer", example=10)),
     *     @OA\Response(
     *         response=200,
     *         description="A paginated list of buildings",
     *         @OA\JsonContent(
     *             @OA\Property(property="data",        type="array", @OA\Items(ref="#/components/schemas/Building")),
     *             @OA\Property(property="current_page",type="integer", example=1),
     *             @OA\Property(property="last_page",   type="integer", example=5),
     *             @OA\Property(property="per_page",    type="integer", example=10),
     *             @OA\Property(property="total",       type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden — insufficient permissions")
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
     *     path="/api/buildings",
     *     summary="Create a new building",
     *     tags={"Building"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","location","status","ecd"},
     *                 @OA\Property(property="name",     type="string", example="Building A"),
     *                 @OA\Property(property="location", type="string", example="Downtown"),
     *                 @OA\Property(property="status",   type="string", example="Off-Plan"),
     *                 @OA\Property(property="ecd",      type="date", example="2027-30-12"),
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
     *         @OA\JsonContent(ref="#/components/schemas/Building")
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden — insufficient permissions")
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
            'ecd' => 'required|date',
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

        return response()->json($building, Response::HTTP_CREATED);
    }

    /**
     * Display the specified building.
     *
     * @OA\Get(
     *     path="/api/buildings/{id}",
     *     summary="Get building details",
     *     tags={"Building"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id", in="path", description="Building ID", required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Building details",
     *         @OA\JsonContent(ref="#/components/schemas/Building")
     *     ),
     *     @OA\Response(response=403, description="Forbidden — insufficient permissions"),
     *     @OA\Response(response=404, description="Not Found — building does not exist")
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

        return response()->json($building, Response::HTTP_OK);
    }

    /**
     * Update an existing building, optionally replacing its image.
     *
     * @OA\Put(
     *     path="/api/buildings/{id}",
     *     summary="Update a building",
     *     tags={"Building"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id", in="path", description="Building ID", required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name",     type="string", example="Building A Updated"),
     *                 @OA\Property(property="location", type="string", example="New Location"),
     *                 @OA\Property(property="status",   type="string", example="Off-Plan"),
     *                 @OA\Property(property="ecd",      type="date", example="2027-30-12"),
     *                 @OA\Property(
     *                     property="image",
     *                     type="string",
     *                     format="binary",
     *                     description="Optional new building image"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Building updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Building")
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden — insufficient permissions"),
     *     @OA\Response(response=404, description="Not Found — building does not exist")
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
            'ecd' => 'sometimes|date',
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

        return response()->json($building, Response::HTTP_OK);
    }

    /**
     * Remove the specified building from storage.
     *
     * @OA\Delete(
     *     path="/api/buildings/{id}",
     *     summary="Delete a building",
     *     tags={"Building"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id", in="path", description="Building ID", required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(response=204, description="Building deleted successfully"),
     *     @OA\Response(response=403, description="Forbidden — insufficient permissions"),
     *     @OA\Response(response=404, description="Not Found — building does not exist"),
     *     @OA\Response(response=409, description="Conflict — building has existing units")
     * )
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        Log::info('User ' . $user->id . ' called BuildingController@destroy for building id: ' . $id);

        if (!$user->can('delete building')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        $building = Building::findOrFail($id);

        if ($building->units()->exists()) {
            return response()->json([
                'error' => 'Cannot delete a building that has units.'
            ], Response::HTTP_CONFLICT);
        }

        $building->delete();

        return response()->json(null, 204);
    }

    /**
     * List all units for a specific building with optional filters.
     *
     * @OA\Get(
     *     path="/api/buildings/{buildingId}/units",
     *     summary="List units in a building",
     *     tags={"Building"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="buildingId", in="path", description="Building ID", required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Parameter(name="unit_no",  in="query", description="Filter by unit number", @OA\Schema(type="string", example="A101")),
     *     @OA\Parameter(
     *         name="status", in="query", description="Filter by status",
     *         @OA\Schema(type="string", enum={"Pending","Available","Pre-Booked","Booked","Sold","Pre-Hold","Hold","Cancelled"}, example="Available")
     *     ),
     *     @OA\Parameter(name="page",  in="query", description="Page number", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="limit", in="query", description="Items per page (max 100)", @OA\Schema(type="integer", example=10)),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of units",
     *         @OA\JsonContent(
     *             @OA\Property(property="data",        type="array", @OA\Items(ref="#/components/schemas/Unit")),
     *             @OA\Property(property="current_page",type="integer", example=1),
     *             @OA\Property(property="last_page",   type="integer", example=5),
     *             @OA\Property(property="per_page",    type="integer", example=10),
     *             @OA\Property(property="total",       type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden — insufficient permissions"),
     *     @OA\Response(response=404, description="Not Found — building does not exist")
     * )
     */
    public function getUnitsByBuilding(Request $request, $buildingId)
    {
        $user = $request->user();
        Log::info("User {$user->id} requested units for building {$buildingId}.");

        // Permission check
        if (! $user->can('view unit')) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        // Load the building or 404
        $building = Building::findOrFail($buildingId);

        // Start from the building's units relation
        $query = $building->units();

        // Role‐based scoping
        if ($user->hasRole('Sales')) {
            $salesId = $user->id;
            $query->where(function($q) use ($salesId) {
                $q->whereIn('status', ['Available', 'Cancelled'])
                    ->orWhereHas('bookings', function($b) use ($salesId) {
                        $b->where('created_by', $salesId)
                            ->where('status', '!=', 'Cancelled');
                    })
                    ->orWhereHas('holdings', function($h) use ($salesId) {
                        $h->where('created_by', $salesId)
                            ->whereIn('status', ['Hold', 'Pre-Hold', 'Processed']);
                    });
            });
        } elseif ($user->hasRole('Broker')) {
            $brokerId = $user->id;
            $query->where(function($q) use ($brokerId) {
                $q->where('status', 'Available')
                    ->orWhereHas('holdings', function($h) use ($brokerId) {
                        $h->where('created_by', $brokerId)
                            ->whereIn('status', ['Hold', 'Pre-Hold', 'Processed']);
                    });
            });
        }

        // Optional filters - Apply filtering based on query parameters
        if ($request->filled('prop_type')) {
            $query->where('prop_type', 'like', "%" . $request->input('prop_type') . "%");
        }

        if ($request->filled('unit_type')) {
            $query->where('unit_type', 'like', "%" . $request->input('unit_type') . "%");
        }

        if ($request->filled('unit_no')) {
            $query->where('unit_no', 'like', '%' . $request->input('unit_no') . '%');
        }

        if ($request->filled('floor')) {
            $query->where('floor', $request->input('floor'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Paginate (default 10, cap 100)
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
     * Serve the building’s image binary, with strong cache validators.
     *
     * @OA\Get(
     *     path="/api/buildings/{id}/image",
     *     summary="Retrieve the building image",
     *     tags={"Building"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id", in="path", description="Building ID", required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Building image binary",
     *         @OA\MediaType(mediaType="application/octet-stream")
     *     ),
     *     @OA\Response(response=304, description="Not Modified"),
     *     @OA\Response(response=403, description="Forbidden — insufficient permissions"),
     *     @OA\Response(response=404, description="Not Found — image does not exist")
     * )
     */
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
