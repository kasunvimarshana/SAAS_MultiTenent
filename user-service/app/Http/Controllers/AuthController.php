<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends BaseController
{
    public function __construct(private readonly UserService $userService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser($request->validated());
            $token = $user->createToken('user-service-token')->accessToken;

            return $this->successResponse([
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 'User registered successfully.', 201);
        } catch (\Throwable $e) {
            Log::error('AuthController: Registration failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return $this->errorResponse('Invalid credentials.', 401);
        }

        $user = Auth::user();

        // Tenant check
        if ($user->tenant_id) {
            $tenantId = $request->header('X-Tenant-ID') ?? $request->input('tenant_id');
            if ($tenantId && (string) $user->tenant_id !== (string) $tenantId) {
                Auth::logout();
                return $this->errorResponse('Tenant mismatch.', 403);
            }
        }

        $token = $user->createToken('user-service-token')->accessToken;

        return $this->successResponse([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'Login successful.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->token()->revoke();
        return $this->successResponse(null, 'Logged out successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->successResponse($request->user()->load('roles.permissions'));
    }

    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->token()->revoke();
        $token = $user->createToken('user-service-token')->accessToken;

        return $this->successResponse([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'Token refreshed successfully.');
    }
}
