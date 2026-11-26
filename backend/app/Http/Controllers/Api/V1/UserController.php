<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateUserPasswordRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Modules\Users\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function show(User $user): UserResource
    {
        return new UserResource($this->userService->show((string) $user->id));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user = $this->userService->update($user, $data);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => new UserResource($user->fresh()->load('roles', 'permissions')),
        ]);
    }

    public function updatePassword(UpdateUserPasswordRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $this->userService->updatePassword($user, $request->current_password, $request->password);

        return response()->json(['message' => 'Password updated successfully.']);
    }
}
