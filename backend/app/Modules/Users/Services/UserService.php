<?php

namespace App\Modules\Users\Services;

use App\Enums\AuctionStatus;
use App\Enums\BidStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Modules\Users\Repositories\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        protected UserRepository $repository
    ) {}

    public function show(string $id): User
    {
        $user = $this->repository->findById($id);

        if (! $user) {
            throw new ModelNotFoundException;
        }

        return $user->load('roles', 'permissions');
    }

    public function update(User $user, array $data): User
    {
        return $this->repository->update($user, $data);
    }

    public function updatePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update(['password' => Hash::make($newPassword)]);

        DB::connection($user->getConnectionName())
            ->table('personal_access_tokens')
            ->where('tokenable_id', (string) $user->getKey())
            ->where('tokenable_type', $user->getMorphClass())
            ->delete();

        return true;
    }

    public function suspend(User $user): User
    {
        if ($user->hasRole(UserRole::Admin->value)) {
            throw new \DomainException('Cannot suspend an admin user.');
        }

        return $this->repository->suspend($user);
    }

    public function unsuspend(User $user): User
    {
        return $this->repository->unsuspend($user);
    }

    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }

    public function dashboardStats(): array
    {
        return [
            'users_count' => User::count(),
            'products_count' => Product::count(),
            'auctions_count' => Auction::count(),
            'categories_count' => Category::count(),
            'pending_products_count' => Product::where('status', ProductStatus::Draft->value)->count(),
            'active_auctions_count' => Auction::where('status', AuctionStatus::Active->value)->count(),
            'revenue' => (float) (string) Bid::where('status', BidStatus::Won->value)->sum('amount'),
        ];
    }
}
