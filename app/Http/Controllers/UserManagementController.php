<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserDoc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class UserManagementController extends Controller
{
    /**
     * List all users, with optional filters.
     *
     * You can optionally pass `role` and/or `status` as query parameters to filter the results.
     * Examples:
     *  - GET /users?role=Broker
     *  - GET /users?status=Active
     *  - GET /users?role=Broker&status=Pending
     *
     * @OA\Get(
     *     path="/users",
     *     summary="List all users, with optional role/status filters",
     *     tags={"User Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Filter by user role (e.g. 'Broker', 'CFO', 'CEO', etc.)",
     *         required=false,
     *         @OA\Schema(type="string", example="Broker")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by user status (e.g. 'Active', 'Pending', 'Inactive')",
     *         required=false,
     *         @OA\Schema(type="string", example="Pending")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of filtered users",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=42),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="status", type="string", example="Active"),
     *                 @OA\Property(property="role", type="string", example="Broker")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden (no permission to view users)"),
     *     @OA\Response(response=422, description="Validation error"),
     * )
     */
    public function listAllUsers(Request $request)
    {
        $authUser = $request->user();
        Log::info("User {$authUser->id} requested a list of all users.");

        if (!$authUser->can('view users')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $query = User::whereDoesntHave('roles', function ($q) {
            $q->where('name', 'System Maintenance');
        });

        // Optional role filter
        // e.g. /users?role=Broker
        if ($request->filled('role')) {
            $role = $request->input('role');
            // We can use Spatie's scope to filter users who have that role
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        // Optional status filter
        // e.g. /users?status=Active
        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->where('status', $status);
        }

        // Execute the query
        $users = $query->get();

        // Map each user to a simple array: [id, name, email, status, role]
        $result = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'role' => $user->roles->pluck('name')->first() // only one role per user
            ];
        });

        return response()->json($result, Response::HTTP_OK);
    }

    /**
     * Get user details, including role, permissions, and doc download links.
     *
     * @OA\Get(
     *     path="/users/{id}",
     *     summary="Get user details, with docs download URLs",
     *     tags={"User Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the user to retrieve",
     *         required=true,
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User details retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=42),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="status", type="string", example="Active"),
     *             @OA\Property(property="role", type="string", example="Broker"),
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="string", example="add broker")
     *             ),
     *             @OA\Property(
     *                 property="docs",
     *                 type="array",
     *                 description="User documents, each with doc_type and download_url",
     *                 @OA\Items(
     *                     @OA\Property(property="doc_type", type="string", example="rera_cert"),
     *                     @OA\Property(property="download_url", type="string", format="uri", example="http://your-domain.test/storage/docs/rera_cert_42.pdf")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function getUserDetails(Request $request, $id)
    {
        $authUser = $request->user();
        Log::info("User {$authUser->id} requested details for user ID: {$id}.");

        if (!$authUser->can('view users')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // Retrieve the user
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if ($user->roles->pluck('name')->contains('System Maintenance')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // For one-role-per-user, we can get the single role
        $roleName = $user->roles->pluck('name')->first();

        // Retrieve the permissions for that role
        $permissions = [];
        if ($roleName) {
            $role = Role::where('name', $roleName)->with('permissions')->first();
            if ($role) {
                $permissions = $role->permissions->pluck('name')->toArray();
            }
        }

        // Map each doc to { doc_type, download_url }
        $docs = $user->docs->map(function ($doc) {
            return [
                'doc_id' => $doc->id,
                'doc_type' => $doc->doc_type,
                'created_at' => $doc->created_at,
                'updated_at' => $doc->updated_at,

            ];
        });

        $result = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'role' => $roleName,
            'permissions' => $permissions,
            'docs' => $docs,
        ];

        return response()->json($result, Response::HTTP_OK);
    }

    /**
     * Register a new user with a specific role, optionally uploading docs if Broker/Contractor.
     *
     * @OA\Post(
     *     path="/users/register",
     *     summary="Register a new user with optional docs for Broker/Contractor",
     *     tags={"User Management"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "email", "password", "password_confirmation", "role"},
     *                 @OA\Property(property="name", type="string", example="John Doe", description="User's full name"),
     *                 @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *                 @OA\Property(property="password", type="string", format="password", example="secret123"),
     *                 @OA\Property(property="password_confirmation", type="string", format="password", example="secret123"),
     *                 @OA\Property(
     *                     property="role",
     *                     type="string",
     *                     description="Role to assign (e.g., 'Broker', 'Contractor', 'Sales', etc.)",
     *                     example="Broker",
     *                     enum={
     *                         "CRM Officer","Sales","CSO","Accountant","CFO","CEO","HR Admin",
     *                         "Broker","Contractor","System Maintenance"
     *                     }
     *                 ),
     *                 @OA\Property(
     *                     property="rera_cert",
     *                     type="string",
     *                     format="binary",
     *                     description="Optional doc if role=Broker (pdf, jpg, jpeg, png)"
     *                 ),
     *                 @OA\Property(
     *                     property="trade_license",
     *                     type="string",
     *                     format="binary",
     *                     description="Optional doc if role=Broker or Contractor (pdf, jpg, jpeg, png)"
     *                 ),
     *                 @OA\Property(
     *                     property="bank_account",
     *                     type="string",
     *                     format="binary",
     *                     description="Optional doc if role=Broker"
     *                 ),
     *                 @OA\Property(
     *                     property="tax_registration",
     *                     type="string",
     *                     format="binary",
     *                     description="Optional doc if role=Broker"
     *                 ),
     *                 @OA\Property(
     *                     property="contract",
     *                     type="string",
     *                     format="binary",
     *                     description="Optional doc if role=Contractor"
     *                 ),
     *                 @OA\Property(
     *                     property="scope_of_work",
     *                     type="string",
     *                     format="binary",
     *                     description="Optional doc if role=Contractor"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="User registered successfully"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="status", type="string", example="Pending"),
     *                 @OA\Property(property="role", type="string", example="Broker")
     *             ),
     *             @OA\Property(
     *                 property="docs",
     *                 type="array",
     *                 description="List of uploaded docs if any",
     *                 @OA\Items(
     *                     @OA\Property(property="doc_type", type="string", example="rera_cert"),
     *                     @OA\Property(property="doc_id", type="integer", example="53")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden (no permission to assign this role)"),
     *     @OA\Response(response=422, description="Validation error (e.g., email taken, password mismatch)"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function registerUser(Request $request)
    {
        $authUser = $request->user();
        Log::info("User {$authUser->id} is attempting to register a new user.");

        // Define role-permission mappings
        $rolePermissions = [
            'CRM Officer' => 'add crm officer',
            'Sales' => 'add sales',
            'CSO' => 'add cso',
            'Accountant' => 'add accountant',
            'CFO' => 'add cfo',
            'CEO' => 'add ceo',
            'HR Admin' => 'add hr admin',
            'Broker' => 'manage broker',
            'Contractor' => 'manage contractor',
            'System Maintenance' => 'add system maintenance',
        ];

        // Basic validation rules
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|string|in:' . implode(',', array_keys($rolePermissions)),

            // Optional doc fields (just define them as sometimes|file)
            'rera_cert' => 'sometimes|file|mimes:pdf,jpg,jpeg,png',
            'trade_license' => 'sometimes|file|mimes:pdf,jpg,jpeg,png',
            'bank_account' => 'sometimes|file|mimes:pdf,jpg,jpeg,png',
            'tax_registration' => 'sometimes|file|mimes:pdf,jpg,jpeg,png',
            'contract' => 'sometimes|file|mimes:pdf,jpg,jpeg,png',
            'scope_of_work' => 'sometimes|file|mimes:pdf,jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $validator->validated();
        $selectedRole = $data['role'];

        // Check if the authenticated user has permission to assign this role
        if (!$authUser->can($rolePermissions[$selectedRole])) {
            return response()->json(['error' => $rolePermissions[$selectedRole]], Response::HTTP_FORBIDDEN);
        }

        // Ensure the role exists in the database
        $role = Role::where('name', $selectedRole)->where('guard_name', 'web')->first();
        if (!$role) {
            return response()->json(['error' => 'Role does not exist'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            // Create the user
            $newUser = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'status' => !in_array($selectedRole, ['Broker', 'Contractor']) ? 'Active' : 'Pending',
            ]);

            // Assign role
            $newUser->syncRoles([$role->name]);

            // If role is Broker, we can handle doc fields for rera_cert, etc.
            // If role is Contractor, handle doc fields for contract, etc.
            // We'll do it in one pass, but only store if file is present.
            if ($selectedRole === 'Broker') {
                $docFields = ['rera_cert', 'trade_license', 'bank_account', 'tax_registration'];
            } elseif ($selectedRole === 'Contractor') {
                $docFields = ['contract', 'scope_of_work', 'trade_license'];
            } else {
                $docFields = [];
            }

            $docsCreated = [];
            foreach ($docFields as $docType) {
                if ($request->hasFile($docType)) {
                    $file = $request->file($docType);
                    $fileName = "{$docType}_{$newUser->id}." . $file->getClientOriginalExtension();

                    // Store in a private disk ("local" or "private" depending on your config)
                    // e.g. 'private' => ['driver' => 'local', 'root' => storage_path('app/private'), ...]
                    $filePath = $file->storeAs('docs', $fileName, 'local');
                    // physically: storage/app/private/docs/<docType>_<userId>.<ext>

                    // Create the doc record in user_docs
                    $doc = $newUser->docs()->create([
                        'doc_type' => $docType,
                        'file_path' => $filePath, // e.g. "docs/rera_cert_15.pdf"
                    ]);

                    // Return only doc_id and doc_type (no public download link)
                    $docsCreated[] = [
                        'doc_type' => $docType,
                        'doc_id' => $doc->id
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'User registered successfully',
                'user' => [
                    'id' => $newUser->id,
                    'name' => $newUser->name,
                    'email' => $newUser->email,
                    'status' => $newUser->status,
                    'role' => $role->name,
                ],
                'docs' => $docsCreated,
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
     * Update a user (using method override for PUT in multipart/form-data).
     *
     * This endpoint is defined as a POST, but you must include `_method=PUT` in the form data,
     * so Laravel treats it as a PUT request and parses files correctly.
     *
     * @OA\Post(
     *     path="/users/{id}",
     *     summary="Update a user via method override (PUT) in multipart/form-data",
     *     tags={"User Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the user to update",
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"_method"},
     *                 @OA\Property(
     *                     property="_method",
     *                     type="string",
     *                     description="Must be 'PUT' so Laravel interprets this as a PUT request",
     *                     example="PUT"
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     description="User's name",
     *                     example="Hanna Hathway"
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     format="email",
     *                     description="User's email address",
     *                     example="hanna@example.com"
     *                 ),
     *                 @OA\Property(
     *                     property="bank_account",
     *                     type="string",
     *                     format="binary",
     *                     description="Optional file upload if needed (Broker) (pdf, jpg, jpeg, png)"
     *                 ),
     *                 @OA\Property(
     *                     property="tax_registration",
     *                     type="string",
     *                     format="binary",
     *                     description="Optional file upload if needed (Broker) (pdf, jpg, jpeg, png)"
     *                 ),
     *                 @OA\Property(
     *                     property="contract",
     *                     type="string",
     *                     format="binary",
     *                     description="Optional file upload if needed (Contractor) (pdf, jpg, jpeg, png)"
     *                 ),
     *                 @OA\Property(
     *                     property="scope_of_work",
     *                     type="string",
     *                     format="binary",
     *                     description="Optional file upload if needed (Contractor) (pdf, jpg, jpeg, png)"
     *                 ),
     *                 @OA\Property(
     *                     property="trade_license",
     *                     type="string",
     *                     format="binary",
     *                     description="Optional file upload if needed (Broker or Contractor) (pdf, jpg, jpeg, png)"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="User updated successfully"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=42),
     *                 @OA\Property(property="name", type="string", example="Hanna Hathway"),
     *                 @OA\Property(property="email", type="string", example="hanna@example.com"),
     *                 @OA\Property(property="status", type="string", example="Active")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function updateUser(Request $request, $id)
    {
        $authUser = $request->user();
        Log::info("User {$authUser->id} is attempting to update user ID: {$id}.");

        // Check permission (e.g., 'edit user')
        if (!$authUser->can('edit user')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // Retrieve the user
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if ($user->roles->pluck('name')->contains('System Maintenance')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // Build validation rules
        $rules = [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,

            // Optional doc uploads
            'rera_cert' => 'sometimes|file|mimes:pdf,jpg,jpeg,png',
            'trade_license' => 'sometimes|file|mimes:pdf,jpg,jpeg,png',
            'bank_account' => 'sometimes|file|mimes:pdf,jpg,jpeg,png',
            'tax_registration' => 'sometimes|file|mimes:pdf,jpg,jpeg,png',
            'contract' => 'sometimes|file|mimes:pdf,jpg,jpeg,png',
            'scope_of_work' => 'sometimes|file|mimes:pdf,jpg,jpeg,png',

        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $validator->validated();

        DB::beginTransaction();
        try {
            // Update fields if present
            if (isset($data['name'])) {
                $user->name = $data['name'];
            }
            if (isset($data['email'])) {
                $user->email = $data['email'];
            }
            $user->save();

            // Handle doc uploads
            // We'll define an array of doc field => doc_type
            $docFields = [
                'rera_cert' => 'rera_cert',
                'trade_license' => 'trade_license',
                'bank_account' => 'bank_account',
                'tax_registration' => 'tax_registration',
                'contract' => 'contract',
                'scope_of_work' => 'scope_of_work',
            ];

            foreach ($docFields as $field => $docType) {
                if ($request->hasFile($field)) {

                    $file = $request->file($field);
                    $fileName = "{$docType}_{$user->id}." . $file->getClientOriginalExtension();

                    // Store in a private disk named "local" or "private" (adjust as per your config)
                    $filePath = $file->storeAs('docs', $fileName, 'local');
                    // e.g., physically stored at storage/app/private/docs/<docType>_<userId>.<ext>

                    // Upsert doc in user_docs
                    $existingDoc = $user->docs()->where('doc_type', $docType)->first();
                    if ($existingDoc) {
                        $existingDoc->update(['file_path' => $filePath]);
                    } else {
                        $user->docs()->create([
                            'doc_type' => $docType,
                            'file_path' => $filePath,
                        ]);
                    }
                }
            }

            DB::commit();

            $docs = $user->docs->map(function ($doc) {
                return [
                    'doc_id' => $doc->id,
                    'doc_type' => $doc->doc_type,
                    'created_at' => $doc->created_at,
                    'updated_at' => $doc->updated_at,
                ];
            });

            return response()->json([
                'message' => 'User updated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->getRoleNames()->first(),
                    'status' => $user->status,
                ],
                'docs' => $docs,
            ], Response::HTTP_OK);


        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json([
                'error' => 'Server error',
                'message' => $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a user and their user_docs.
     *
     * @OA\Delete(
     *     path="/users/{id}",
     *     summary="Delete a user and their associated docs",
     *     tags={"User Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the user to delete",
     *         required=true,
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function deleteUser(Request $request, $id)
    {
        $authUser = $request->user();
        Log::info("User {$authUser->id} is attempting to delete user ID: {$id}.");

        // Check permission (e.g., 'delete user')
        if (!$authUser->can('delete user')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // Retrieve the user
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if ($user->roles->pluck('name')->contains('System Maintenance')) {
            return response()->json(['error' => 'Forbidden: user cannot be deleted'], Response::HTTP_FORBIDDEN);
        }

        DB::beginTransaction();
        try {
            // 1. Delete associated docs (both DB and physical files)
            $user->docs->each(function ($doc) {
                // Remove the file from storage if it exists
                if (Storage::disk('local')->exists($doc->file_path)) {
                    Storage::disk('local')->delete($doc->file_path);
                }
                // Delete the doc record
                $doc->delete();
            });

            // 2. Delete the user record
            $user->delete();

            DB::commit();

            return response()->json(['message' => 'User deleted successfully'], Response::HTTP_OK);

        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json([
                'error' => 'Server error',
                'message' => $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Authenticate a user and return an API token.
     *
     * @OA\Post(
     *     path="/auth/login",
     *     summary="User Login",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="securepassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="token", type="string", example="1|A23sj9ds89d7a9sd87as"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="user@example.com"),
     *                 @OA\Property(property="role", type="string", example="Sales"),
     *                 @OA\Property(property="status", type="string", example="Active")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=403, description="Account is inactive"),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::where('email', $request->email)->first();

        // Check credentials
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->status !== 'Active') {
            if ($user->status === 'Pending') {
                // Check user role
                $role = $user->getRoleNames()->first();

                // If user is a Broker, check if they have doc_type = "signed_agreement"
                if ($role === 'Broker') {
                    $hasSignedAgreement = $user->docs()->where('doc_type', 'signed_agreement')->exists();

                    if (!$hasSignedAgreement) {
                        // They haven't uploaded the signed agreement yet
                        return response()->json(['error' => 'Please sign the agreement and upload it back'], Response::HTTP_FORBIDDEN);
                    } else {
                        // They have uploaded the signed agreement, but still pending admin approval
                        return response()->json(['error' => 'Your account is pending approval'], Response::HTTP_FORBIDDEN);
                    }
                }

                // For other roles with pending status, just return "pending approval" or a similar message
                return response()->json(['error' => 'Your account is pending approval'], Response::HTTP_FORBIDDEN);
            }

            // If status is "Inactive"
            return response()->json(['error' => 'Account is inactive'], Response::HTTP_FORBIDDEN);
        }

        // Generate token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
                'status' => $user->status,
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Change user password.
     *
     * This endpoint allows authenticated users to change their password.
     *
     * @OA\Post(
     *     path="/users/change-password",
     *     summary="Change user password",
     *     tags={"User Management"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password", "new_password", "new_password_confirmation"},
     *             @OA\Property(property="current_password", type="string", example="oldpassword123"),
     *             @OA\Property(property="new_password", type="string", example="newpassword123"),
     *             @OA\Property(property="new_password_confirmation", type="string", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password changed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password changed successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Current password is incorrect",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Current password is incorrect")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="New password cannot be the same as the current password",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="New password cannot be the same as the current password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="new_password", type="array", @OA\Items(type="string", example="The new password must be at least 6 characters."))
     *         )
     *     )
     * )
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();
        Log::info("User {$user->id} is attempting to change their password.");

        // Validate the request
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string|min:6',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Check if the current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'Current password is incorrect'], Response::HTTP_UNAUTHORIZED);
        }

        // Prevent changing to the same password
        if (Hash::check($request->new_password, $user->password)) {
            return response()->json(['error' => 'New password cannot be the same as the current password'], Response::HTTP_BAD_REQUEST);
        }

        // Update the password
        $user->password = Hash::make($request->new_password);
        $user->save();

        Log::info("User {$user->id} has successfully changed their password.");

        return response()->json(['message' => 'Password changed successfully'], Response::HTTP_OK);
    }

    /**
     * Activate a user (set status to "Active").
     *
     * @OA\Put(
     *     path="/users/{id}/activate",
     *     summary="Activate a user",
     *     tags={"User Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the user to activate",
     *         required=true,
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User activated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="User activated successfully"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                 @OA\Property(property="status", type="string", example="Active")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden (no permission to activate user)"),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=422, description="User is already active or other validation error")
     * )
     */
    public function activate(Request $request, $id)
    {
        $authUser = $request->user();
        Log::info("User {$authUser->id} is attempting to activate user ID: {$id}");

        if (!$authUser->can('activate user')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $targetUser = User::find($id);
        if (!$targetUser) {
            return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if ($targetUser->status === 'Active') {
            return response()->json(['error' => 'User is already active'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $targetUser->status = 'Active';
        $targetUser->save();

        return response()->json([
            'message' => 'User activated successfully',
            'user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
                'status' => $targetUser->status,
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Deactivate (inactivate) a user (set status to "Inactive").
     *
     * @OA\Put(
     *     path="/users/{id}/deactivate",
     *     summary="Deactivate a user",
     *     tags={"User Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the user to deactivate",
     *         required=true,
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deactivated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="User deactivated successfully"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                 @OA\Property(property="status", type="string", example="Inactive")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden (no permission to deactivate user)"),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=422, description="User is already inactive or other validation error")
     * )
     */
    public function deactivate(Request $request, $id)
    {
        $authUser = $request->user();
        Log::info("User {$authUser->id} is attempting to deactivate user ID: {$id}");

        if (!$authUser->can('deactivate user')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $targetUser = User::find($id);
        if (!$targetUser) {
            return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if ($targetUser->status === 'Inactive') {
            return response()->json(['error' => 'User is already inactive'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $targetUser->status = 'Inactive';
        $targetUser->save();

        return response()->json([
            'message' => 'User deactivated successfully',
            'user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
                'status' => $targetUser->status,
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Download a user document from a private disk.
     *
     * Checks if the current user is authorized via canDownload($doc).
     *
     * @OA\Get(
     *     path="/docs/{docId}/download",
     *     summary="Download a user document by docId",
     *     tags={"Documents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="docId",
     *         in="path",
     *         required=true,
     *         description="ID of the UserDoc to download",
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File downloaded successfully (binary data)",
     *         @OA\MediaType(
     *             mediaType="application/octet-stream",
     *             @OA\Schema(
     *                 type="string",
     *                 format="binary",
     *                 description="The file content is returned as a stream"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden if the user lacks permission or doc belongs to another user"),
     *     @OA\Response(response=404, description="Document not found")
     * )
     */
    public function downloadDoc($docId)
    {
        $doc = UserDoc::findOrFail($docId);

        if (!$this->canDownload($doc)) {
            abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        // Serve file from private/local disk
        return Storage::disk('local')->download($doc->file_path);
    }

    private function canDownload(UserDoc $doc)
    {
        $user = auth()->user();

        return $user->id === $doc->user_id || $user->can('view users');
    }
}
