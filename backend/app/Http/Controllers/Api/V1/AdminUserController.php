<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Modules\Users\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminUserController extends Controller
{
    public function __construct(protected UserService $userService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $users = $this->userService->list([
            'search' => $request->search,
            'role' => $request->role,
            'per_page' => $request->integer('per_page', 20),
        ]);

        return UserResource::collection($users);
    }

    public function suspend(User $user): JsonResponse
    {
        try {
            $user = $this->userService->suspend($user);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json([
            'message' => 'User suspended successfully.',
            'user' => new UserResource($user->fresh()->load('roles')),
        ]);
    }

    public function unsuspend(User $user): JsonResponse
    {
        $user = $this->userService->unsuspend($user);

        return response()->json([
            'message' => 'User unsuspended successfully.',
            'user' => new UserResource($user->fresh()->load('roles')),
        ]);
    }
}
