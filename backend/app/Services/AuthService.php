<?php

namespace App\Services;

use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Models\Role;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use MongoDB\BSON\ObjectId;
use Carbon\Carbon;

class AuthService
{
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::User->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Direct database insert to avoid Spatie Permission connection issues
        $connection = $user->getConnectionName();
        $roleId = DB::connection($connection)->table('roles')->where('name', UserRole::User->value)->value('id');
        if ($roleId) {
            DB::connection($connection)->table('model_has_roles')->insert([
                'model_id' => $user->getKey(),
                'model_type' => User::class,
                'role_id' => $roleId,
            ]);
        }

        return $user;
    }

    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $this->createToken($user, 'api-token');
        
        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user, string $tokenId = null): bool
    {
        $connection = $user->getConnectionName();
        $query = DB::connection($connection)->table('personal_access_tokens')
            ->where('tokenable_id', $user->getKey())
            ->where('tokenable_type', User::class);
        
        if ($tokenId) {
            $query->where('id', $tokenId);
        }
        
        $query->delete();
        return true;
    }

    public function createToken(User $user, string $name, array $abilities = ['*'], $expiresAt = null): string
    {
        $plainTextToken = Str::random(40);
        $tokenId = (string) new ObjectId();
        $hashedToken = hash('sha256', $plainTextToken);
        $connection = $user->getConnectionName();

        DB::connection($connection)->table('personal_access_tokens')->insert([
            '_id' => $tokenId,
            'tokenable_id' => $user->getKey(),
            'tokenable_type' => User::class,
            'name' => $name,
            'token' => $hashedToken,
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $tokenId . '|' . $plainTextToken;
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->fill($data);
        $user->save();
        
        return $user;
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->password = Hash::make($newPassword);
        $user->save();
        
        // Revoke all tokens
        $this->logout($user);
        
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
            
            $this->logout($user);
        });
        
        return $status === Password::PASSWORD_RESET;
    }
}