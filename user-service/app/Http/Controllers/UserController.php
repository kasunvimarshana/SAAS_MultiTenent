<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends BaseController
{
    public function __construct(private readonly UserService $userService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $tenant = app('current_tenant');
            $params = $request->only(['per_page', 'page', 'search', 'sort_by', 'sort_direction', 'filters']);
            $users = $this->userService->getUsersByTenant($tenant->id, $params);
            return $this->paginatedResponse($users, 'Users retrieved successfully.');
        } catch (\Throwable $e) {
            Log::error('UserController: index failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to retrieve users.', 500);
        }
    }

    public function show(int|string $id): JsonResponse
    {
        try {
            $user = $this->userService->getById($id);
            if (!$user) {
                return $this->errorResponse('User not found.', 404);
            }
            return $this->successResponse($user->load('roles.permissions'));
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to retrieve user.', 500);
        }
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        try {
            $data = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'password' => 'sometimes|string|min:8',
            ]);
            $user = $this->userService->updateUser($id, $data);
            return $this->successResponse($user, 'User updated successfully.');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to update user: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(int|string $id): JsonResponse
    {
        try {
            $deleted = $this->userService->delete($id);
            if (!$deleted) {
                return $this->errorResponse('User not found.', 404);
            }
            return $this->successResponse(null, 'User deleted successfully.');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to delete user.', 500);
        }
    }

    public function assignRole(Request $request, int|string $id): JsonResponse
    {
        try {
            $request->validate(['role_id' => 'required|integer|exists:roles,id']);
            $this->userService->assignRole($id, $request->input('role_id'));
            return $this->successResponse(null, 'Role assigned successfully.');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to assign role: ' . $e->getMessage(), 500);
        }
    }
}
