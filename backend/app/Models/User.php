<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use MongoDB\Laravel\Auth\User as MongoAuthenticatable;
use MongoDB\Laravel\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class User extends MongoAuthenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes, LogsActivity;

    protected $collection = 'users';

    /**
     * Get the database connection for the model.
     */
    public function getConnectionName(): string
    {
        if (app()->environment('testing') || ($_ENV['APP_ENV'] ?? '') === 'testing' || (getenv('APP_ENV') ?? '') === 'testing') {
            return 'mongodb_testing';
        }

        return 'mongodb';
    }

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'role',
        'avatar',
        'phone',
        'address',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'string',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = [
        'email_verified_at',
        'last_login_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function auctions()
    {
        return $this->hasMany(Auction::class, 'seller_id');
    }

    public function bids()
    {
        return $this->hasMany(Bid::class, 'user_id');
    }

    public function wonAuctions()
    {
        return $this->hasMany(Auction::class, 'winner_id');
    }

    public function auctionParticipants()
    {
        return $this->hasMany(AuctionParticipant::class, 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function activityLogs()
    {
        return $this->morphToMany(
            config('permission.models.role'),
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            'role_id'
        )->using(ModelHasRole::class)
        ->withPivot('team_id');
    }

    public function permissions()
    {
        return $this->morphToMany(
            config('permission.models.permission'),
            'model',
            config('permission.table_names.model_has_permissions'),
            config('permission.column_names.model_morph_key'),
            'permission_id'
        )->using(ModelHasPermission::class)
        ->withPivot('team_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === \App\Enums\UserRole::Admin->value;
    }

    public function hasRole($roles, ?string $guard = null): bool
    {
        $roleModel = config('permission.models.role');
        $pivotTable = config('permission.table_names.model_has_roles');
        $morphKey = config('permission.column_names.model_morph_key');

        $connection = $this->getConnectionName();

        // First, get role IDs from the pivot table (MongoDB doesn't support JOINs)
        $roleIds = DB::connection($connection)->table($pivotTable)
            ->where('model_id', $this->getKey())
            ->where('model_type', self::class)
            ->pluck('role_id')
            ->toArray();

        if (empty($roleIds)) {
            return false;
        }

        // Build query for roles
        $query = DB::connection($connection)->table('roles')
            ->whereIn('id', $roleIds);

        if ($guard) {
            $query->where('guard_name', $guard);
        }

        if (is_string($roles) && str_contains($roles, '|')) {
            $roles = explode('|', $roles);
        }

        $roles = collect(Arr::wrap($roles));

        return $query->where(function ($q) use ($roles) {
            foreach ($roles as $role) {
                if ($role instanceof \BackedEnum) {
                    $q->orWhere('name', $role->value);
                } elseif (is_int($role) || (is_string($role) && ctype_digit($role))) {
                    $q->orWhere('id', $role);
                } elseif (is_string($role)) {
                    $q->orWhere('name', $role);
                } elseif ($role instanceof \Spatie\Permission\Contracts\Role) {
                    $q->orWhere('id', $role->getKey());
                }
            }
        })->exists();
    }

    public function hasAnyRole(...$roles): bool
    {
        // Flatten the roles array in case RoleMiddleware passes nested array
        $roles = array_merge([], ...$roles);
        return $this->hasRole($roles);
    }

    public function isSeller(): bool
    {
        return $this->role === \App\Enums\UserRole::Seller->value;
    }

    public function canCreateProducts(): bool
    {
        return $this->isSeller() || $this->isAdmin();
    }

    public function canCreateAuctions(): bool
    {
        return $this->isSeller() || $this->isAdmin();
    }

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = Hash::make($value);
    }
}