<?php

namespace App\Modules\Auth\Repositories;

use App\Models\User;
use App\Models\PersonalAccessToken;
use App\Enums\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use MongoDB\BSON\ObjectId;

class AuthRepository
{
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findById(string $id): ?User
    {
        if (preg_match('/^[0-9a-fA-F]{24}$/', $id)) {
            return User::where('_id', new ObjectId($id))->first();
        }

        return User::find($id);
    }

    public function createUser(array $data): User
    {
        return User::create($data);
    }

    public function assignDefaultRole(User $user): void
    {
        $connection = $user->getConnectionName();
        $roleId = DB::connection($connection)->table('roles')
            ->where('name', UserRole::User->value)
            ->value('id');

        if ($roleId) {
            DB::connection($connection)->table('model_has_roles')->insert([
                'model_id' => $user->getKey(),
                'model_type' => User::class,
                'role_id' => $roleId,
            ]);
        }
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

    public function revokeTokens(User $user, ?string $tokenId = null): bool
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
}
