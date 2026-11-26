<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Requests\Api\V1\ResetPasswordRequest;
use App\Http\Requests\Api\V1\UpdatePasswordRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Modules\Auth\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    #[OA\Post(path: '/auth/register', tags: ['Auth'], summary: 'Register a new user', responses: [
        new OA\Response(response: 201, description: 'Registration successful'),
        new OA\Response(response: 422, description: 'Validation error'),
    ])]
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());
        $token = $this->authService->createToken($user, 'api-token');

        return response()->json([
            'message' => 'Registration successful. Please verify your email.',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    #[OA\Post(path: '/auth/login', tags: ['Auth'], summary: 'Authenticate a user', responses: [
        new OA\Response(response: 200, description: 'Login successful'),
        new OA\Response(response: 422, description: 'Invalid credentials'),
    ])]
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return response()->json($result);
    }

    #[OA\Post(path: '/auth/logout', tags: ['Auth'], summary: 'Revoke the current access token', security: [['bearerAuth' => []]], responses: [
        new OA\Response(response: 200, description: 'Logged out successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ])]
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    #[OA\Get(path: '/auth/user', tags: ['Auth'], summary: 'Get the authenticated user', security: [['bearerAuth' => []]], responses: [
        new OA\Response(response: 200, description: 'Current user'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ])]
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    #[OA\Post(path: '/auth/forgot-password', tags: ['Auth'], summary: 'Send a password reset link', responses: [
        new OA\Response(response: 200, description: 'Reset link sent'),
        new OA\Response(response: 422, description: 'Validation error'),
    ])]
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendPasswordResetLink($request->email);

        return response()->json([
            'message' => 'Password reset link sent to your email.',
        ]);
    }

    #[OA\Post(path: '/auth/reset-password', tags: ['Auth'], summary: 'Reset a user password', responses: [
        new OA\Response(response: 200, description: 'Password reset'),
        new OA\Response(response: 422, description: 'Validation error'),
    ])]
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword($request->validated());

        return response()->json([
            'message' => 'Password has been reset successfully.',
        ]);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $this->authService->verifyEmail($request->user());

        return response()->json([
            'message' => 'Email verified successfully.',
        ]);
    }

    public function sendVerificationEmail(Request $request): JsonResponse
    {
        $this->authService->sendVerificationEmail($request->user());

        return response()->json([
            'message' => 'Verification email sent.',
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->authService->updateProfile($request->user(), $request->validated());

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user,
        ]);
    }

    #[OA\Put(path: '/auth/password', tags: ['Auth'], summary: 'Update the authenticated user password', security: [['bearerAuth' => []]], responses: [
        new OA\Response(response: 200, description: 'Password updated'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 422, description: 'Validation error'),
    ])]
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $this->authService->updatePassword($request->user(), $request->validated());

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }
}
