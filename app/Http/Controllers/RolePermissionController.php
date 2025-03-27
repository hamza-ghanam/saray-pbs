<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class RolePermissionController extends Controller
{
    /**
     * List all roles and their assigned permissions.
     *
     * @OA\Get(
     *     path="/roles",
     *     summary="List all roles and their permissions",
     *     tags={"Roles & Permissions"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Roles and their permissions",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="role", type="string", example="Admin"),
     *                 @OA\Property(property="permissions", type="array", @OA\Items(type="string", example="create users"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function listRoles(Request $request)
    {
        $user = $request->user();
        Log::info("User {$user->id} is listing roles and their permissions.");

        if (!$user->can('manage roles and permissions')) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $roles = Role::where('name', '!=', 'System Maintenance')
            ->with('permissions')
            ->get();

        return response()->json($roles->map(function ($role) {
            return [
                'role' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ];
        }), Response::HTTP_OK);
    }

    /**
     * List all permission names.
     *
     * @OA\Get(
     *     path="/permissions",
     *     summary="List all permission names",
     *     description="Returns an array of permission names in the system.",
     *     operationId="listPermissionNames",
     *     tags={"Roles & Permissions"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="A list of permission names",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="string", example="edit articles")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden (user lacks permission)",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Forbidden")
     *         )
     *     )
     * )
     */
    public function listPermissions(Request $request)
    {
        if (!$request->user()->can('manage roles and permissions')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $permissions = Permission::all()->pluck('name');
        return response()->json($permissions, Response::HTTP_OK);
    }

    /**
     * Update permissions of a role.
     *
     * @OA\Put(
     *     path="/roles/{role}",
     *     summary="Update a role's permissions",
     *     tags={"Roles & Permissions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="role",
     *         in="path",
     *         required=true,
     *         description="Role name",
     *         @OA\Schema(type="string", example="Manager")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string", example="edit posts"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Permissions updated successfully"),
     *     @OA\Response(response=404, description="Role not found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function updateRolePermissions(Request $request, $roleName)
    {
        $user = $request->user();

        // Ensure the authenticated user has permission to view permissions.
        if (!$user->can('manage roles and permissions')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            return response()->json(['error' => 'Role not found'], Response::HTTP_NOT_FOUND);
        }

        Log::info("User {$user->id} is attempting to update role {$roleName}.");

        if ($roleName === 'System Maintenance') {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Sync the new permissions
        $role->syncPermissions($request->permissions);

        return response()->json([
            'message' => "Permissions updated successfully",
            'role' => $role->name,
            'permissions' => $role->permissions->pluck('name'),
        ], Response::HTTP_OK);
    }

    /**
     * Create a new role and assign permissions.
     *
     * @OA\Post(
     *     path="/roles",
     *     summary="Create a new role and assign permissions",
     *     tags={"Roles & Permissions"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "permissions"},
     *             @OA\Property(property="name", type="string", example="Supervisor"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string", example="view reports"))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Role created successfully"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function storeRole(Request $request)
    {
        $user = $request->user();

        // Ensure the authenticated user has permission to view permissions.
        if (!$user->can('manage roles and permissions')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        Log::info("User {$user->id} is attempting to create a new role {$request->name}.");

        if ($request->name === 'System Maintenance') {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);
        $role->syncPermissions($request->permissions);

        return response()->json([
            'message' => "Role '{$request->name}' created successfully",
            'role' => $role->name,
            'permissions' => $role->permissions->pluck('name'),
        ], Response::HTTP_CREATED);
    }

    /**
     * Change the role of a user.
     *
     * @OA\Put(
     *     path="/users/{user}/role",
     *     summary="Change a user's role",
     *     tags={"User Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"role"},
     *             @OA\Property(property="role", type="string", example="Manager")
     *         )
     *     ),
     *     @OA\Response(response=200, description="User role updated successfully"),
     *     @OA\Response(response=404, description="User or role not found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function changeUserRole(Request $request, $userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $callUser = $request->user();
        Log::info("User {$callUser->id} is listing roles and their permissions.");

        if (!$callUser->can('manage roles and permissions')) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|string|exists:roles,name'
        ]);

        if ($request->role === 'System Maintenance') {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Remove existing roles and assign the new one
        $user->syncRoles([$request->role]);

        return response()->json([
            'message' => "User '{$user->name}' role updated to '{$request->role}'",
            'user' => $user->only(['id', 'name', 'email']),
            'role' => $user->roles->pluck('name')->first(),
        ], Response::HTTP_OK);
    }

    /**
     * Delete a role.
     *
     * @OA\Delete(
     *     path="/roles/{role}",
     *     summary="Delete a role",
     *     tags={"Roles & Permissions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="role",
     *         in="path",
     *         required=true,
     *         description="Role name",
     *         @OA\Schema(type="string", example="Manager")
     *     ),
     *     @OA\Response(response=200, description="Role deleted successfully"),
     *     @OA\Response(response=404, description="Role not found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function destroy(Request $request, $roleName)
    {
        $callUser = $request->user();

        if (!$callUser->can('manage roles and permissions')) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        if ($roleName === 'System Maintenance') {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        Log::info("User {$callUser->id} is attempting to delete a role {$roleName}.");

        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            return response()->json(['error' => 'Role not found'], Response::HTTP_NOT_FOUND);
        }

        // Prevent deletion of core roles (optional)
        if (in_array($role->name, ['Admin', 'Super Admin'])) {
            return response()->json([
                'error' => 'This role cannot be deleted'
            ], Response::HTTP_FORBIDDEN);
        }

        $role->delete();

        return response()->json([
            'message' => "Role '{$role->name}' deleted successfully"
        ], Response::HTTP_OK);
    }
}
