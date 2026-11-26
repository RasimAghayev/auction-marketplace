<?php

namespace App\Modules\Auth\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Auth\Repositories\AuthRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        protected AuthRepository $repository
    ) {}

    public function register(array $data): User
    {
        $user = $this->repository->createUser([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::User->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->repository->assignDefaultRole($user);

        return $user;
    }

    public function login(array $credentials): array
    {
        $user = $this->repository->findByEmail($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $this->repository->createToken($user, 'api-token');

        activity()->causedBy($user)->event('auth.login')
            ->withProperties(['ip' => request()->ip()])
            ->log('User logged in.');

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user, ?string $tokenId = null): bool
    {
        activity()->causedBy($user)->event('auth.logout')->log('User logged out.');

        return $this->repository->revokeTokens($user, $tokenId);
    }

    public function createToken(User $user, string $name, array $abilities = ['*'], $expiresAt = null): string
    {
        return $this->repository->createToken($user, $name, $abilities, $expiresAt);
    }

    public function verifyEmail(User $user): void
    {
        if (! $user->email_verified_at) {
            $user->update(['email_verified_at' => now()]);
        }
    }

    public function sendVerificationEmail(User $user): void {}

    public function updateProfile(User $user, array $data): User
    {
        $user->fill($data);
        $user->save();

        return $user;
    }

    public function updatePassword(User $user, array $data): bool
    {
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        $this->repository->revokeTokens($user);

        activity()->causedBy($user)->event('auth.password_updated')->log('Password updated.');

        return true;
    }

    public function sendPasswordResetLink(string $email): bool
    {
        $status = Password::sendResetLink(['email' => $email]);

        return $status === Password::RESET_LINK_SENT;
    }

    public function resetPassword(array $data): bool
    {
        $status = Password::reset($data, function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->setRememberToken(Str::random(60));

            $user->save();

            $this->repository->revokeTokens($user);

            activity()->causedBy($user)->event('auth.password_reset')->log('Password reset.');
        });

        return $status === Password::PASSWORD_RESET;
    }
}
