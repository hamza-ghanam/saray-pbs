<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class UserManagementController extends Controller
{
    /**
     * List all users (except System Maintenance), with each user’s role.
     *
     * @OA\Get(
     *     path="/users",
     *     summary="List all users except those with System Maintenance role",
     *     tags={"User Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of users",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=42),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="status", type="string", example="Active"),
     *                 @OA\Property(property="role", type="string", example="Sales")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden"),
     * )
     */
    public function listAllUsers(Request $request)
    {
        $authUser = $request->user();
        Log::info("User {$authUser->id} requested a list of all users (excluding System Maintenance).");

        if (!$authUser->can('view users')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // Retrieve all users who do NOT have the "System Maintenance" role
        $users = User::whereDoesntHave('roles', function ($query) {
            $query->where('name', 'System Maintenance');
        })->get();

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
     * Get user details (excluding if the user has System Maintenance role),
     * plus the role’s permissions.
     *
     * @OA\Get(
     *     path="/users/{id}",
     *     summary="Get user details (excluding System Maintenance) and role permissions",
     *     tags={"User Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=42),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="status", type="string", example="Active"),
     *             @OA\Property(property="role", type="string", example="Sales"),
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="string", example="add sales")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden or user has System Maintenance role"),
     *     @OA\Response(response=404, description="User not found"),
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

        // Check if user has System Maintenance role
        if ($user->roles->pluck('name')->contains('System Maintenance')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // For one-role-per-user, we can get the single role
        $roleName = $user->roles->pluck('name')->first(); // e.g. "Sales"
        $permissions = [];
        if ($roleName) {
            $role = Role::where('name', $roleName)->with('permissions')->first();
            if ($role) {
                $permissions = $role->permissions->pluck('name')->toArray();
            }
        }

        $result = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'role' => $roleName,
            'permissions' => $permissions,
        ];

        return response()->json($result, Response::HTTP_OK);
    }

    /**
     * Register a new user.
     *
     * This endpoint allows an authenticated user to register a new user with a specific role.
     * The authenticated user must have the required permission to assign the selected role.
     *
     * @OA\Post(
     *     path="/users/register",
     *     summary="Register a new user",
     *     tags={"User Management"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation", "role"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="strongpassword123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="strongpassword123"),
     *             @OA\Property(property="role", type="string", enum={
     *                 "CRM Officer", "Sales", "CSO", "Accountant",
     *                 "CFO", "CEO", "HR Admin", "Broker", "Contractor"
     *             }, example="Sales")
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
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                 @OA\Property(property="status", type="string", example="Active"),
     *                 @OA\Property(property="role", type="string", example="Sales")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden - User does not have permission to assign this role"),
     *     @OA\Response(response=422, description="Validation error (e.g., missing required fields, email already exists, role does not exist)")
     * )
     */
    public function registerUser(Request $request)
    {
        $user = $request->user();
        Log::info("User {$user->id} is attempting to register a new user.");

        // Define role-permission mappings
        $rolePermissions = [
            'CRM Officer' => 'add crm officer',
            'Sales' => 'add sales',
            'CSO' => 'add cso',
            'Accountant' => 'add accountant',
            'CFO' => 'add cfo',
            'CEO' => 'add ceo',
            'HR Admin' => 'add hr admin',
            'Broker' => 'add broker',
            'Contractor' => 'add contractor',
            'System Maintenance' => 'add system maintenance',
        ];

        // Validation rules
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|string|in:' . implode(',', array_keys($rolePermissions)),
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $selectedRole = $request->role;

        // **Check if the user has the required permission to assign the selected role**
        if (!$user->can($rolePermissions[$selectedRole])) {
            return response()->json(['error' => "Forbidden"], Response::HTTP_FORBIDDEN);
        }

        // Ensure the role exists in the database
        $role = Role::where('name', $selectedRole)->where('guard_name', 'web')->first();
        if (!$role) {
            return response()->json(['error' => 'Role does not exist'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Create the user
        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => !in_array($selectedRole, ['Broker', 'Contractor']) ? 'Active' : 'Pending',
        ]);

        // Assign role (only one role per user)
        $newUser->syncRoles([$role->name]);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => [
                'id' => $newUser->id,
                'name' => $newUser->name,
                'email' => $newUser->email,
                'status' => $newUser->status,
                'role' => $role->name,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Update a user’s data (excluding System Maintenance).
     *
     * @OA\Put(
     *     path="/users/{id}",
     *     summary="Update a user's info (excluding System Maintenance)",
     *     tags={"User Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the user to update",
     *         required=true,
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe Updated"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe.updated@example.com"),
     *             @OA\Property(property="status", type="string", example="Active", description="e.g., 'Active' or 'Inactive'")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User updated successfully"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=42),
     *                 @OA\Property(property="name", type="string", example="John Doe Updated"),
     *                 @OA\Property(property="email", type="string", example="john.doe.updated@example.com"),
     *                 @OA\Property(property="status", type="string", example="Active")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden (user lacks permission or user has System Maintenance role)"),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=422, description="Validation error")
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

        // If user has System Maintenance role, forbid updates
        if ($user->roles->pluck('name')->contains('System Maintenance')) {
            return response()->json(['error' => 'Forbidden: user has System Maintenance role'], Response::HTTP_FORBIDDEN);
        }

        // Validate the input
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Update fields if present
        $data = $validator->validated();
        $user->fill($data);
        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Delete a user (excluding System Maintenance).
     *
     * @OA\Delete(
     *     path="/users/{id}",
     *     summary="Delete a user (excluding System Maintenance)",
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
     *     @OA\Response(response=403, description="Forbidden (user lacks permission or user has System Maintenance role)"),
     *     @OA\Response(response=404, description="User not found")
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

        // If user has System Maintenance role, forbid deletion
        if ($user->roles->pluck('name')->contains('System Maintenance')) {
            return response()->json(['error' => 'Forbidden: user has System Maintenance role'], Response::HTTP_FORBIDDEN);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], Response::HTTP_OK);
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

        // Check permission
        if (!$authUser->can('activate user')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $targetUser = User::find($id);
        if (!$targetUser) {
            return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Optionally check if already active
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


}
