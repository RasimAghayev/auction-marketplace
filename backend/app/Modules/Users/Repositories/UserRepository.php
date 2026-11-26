<?php

namespace App\Modules\Users\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use MongoDB\BSON\ObjectId;

class UserRepository
{
    public function findById(string $id): ?User
    {
        if (preg_match('/^[0-9a-fA-F]{24}$/', $id)) {
            return User::where('_id', new ObjectId($id))->first();
        }

        return User::find($id);
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = User::with('roles')
            ->when($filters['search'] ?? null, fn($q) => $q->where('name', 'like', "%{$filters['search']}%")
                ->orWhere('email', 'like', "%{$filters['search']}%"))
            ->when($filters['role'] ?? null, fn($q) => $q->whereHas('roles', fn($r) => $r->where('name', $filters['role'])));

        return $query->latest()->paginate($filters['per_page'] ?? 20);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user;
    }

    public function suspend(User $user): User
    {
        $user->update(['is_active' => false]);

        return $user;
    }

    public function unsuspend(User $user): User
    {
        $user->update(['is_active' => true]);

        return $user;
    }

    public function count(): int
    {
        return User::count();
    }
}
