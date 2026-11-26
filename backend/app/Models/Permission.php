<?php

namespace App\Models;

use App\Models\Concerns\MongodbConnection;
use BackedEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;
use Spatie\Permission\Contracts\Permission as PermissionContract;
use Spatie\Permission\Exceptions\GuardDoesNotMatch;
use Spatie\Permission\Exceptions\PermissionAlreadyExists;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Guard;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Support\Config;
use Spatie\Permission\Traits\HasAssignedModels;
use Spatie\Permission\Traits\RefreshesPermissionCache;

use function Illuminate\Support\enum_value;

/**
 * @property string $id
 * @property string $name
 * @property string $guard_name
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Collection<int, Role> $roles
 * @property-read Collection<int, Model> $users
 */
class Permission extends Model implements PermissionContract
{
    use HasAssignedModels, MongodbConnection, RefreshesPermissionCache, SoftDeletes;

    protected $collection = 'permissions';

    protected $connection = 'mongodb';

    protected $primaryKey = '_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        $attributes['guard_name'] ??= Guard::getDefaultName(static::class);

        parent::__construct($attributes);

        $this->guarded[] = $this->primaryKey;
        $this->table = Config::permissionsTable() ?: parent::getTable();
    }

    /**
     * @return PermissionContract|Permission
     *
     * @throws PermissionAlreadyExists
     */
    public static function create(array $attributes = [])
    {
        $attributes['guard_name'] ??= Guard::getDefaultName(static::class);
        $attributes['name'] = enum_value($attributes['name']);

        $params = ['name' => $attributes['name'], 'guard_name' => $attributes['guard_name']];

        $registrar = app(PermissionRegistrar::class);

        if ($registrar->teams) {
            $teamsKey = $registrar->teamsKey;

            if (array_key_exists($teamsKey, $attributes)) {
                $params[$teamsKey] = $attributes[$teamsKey];
            } else {
                $attributes[$teamsKey] = getPermissionsTeamId();
            }
        }

        if (static::findByParam($params)) {
            throw PermissionAlreadyExists::create($attributes['name'], $attributes['guard_name']);
        }

        return static::query()->create($attributes);
    }

    /**
     * A permission may be given to various roles.
     */
    public function roles(): BelongsToMany
    {
        $registrar = app(PermissionRegistrar::class);

        return $this->belongsToMany(
            Config::roleModel(),
            Config::roleHasPermissionsTable(),
            $registrar->pivotPermission,
            $registrar->pivotRole
        );
    }

    /**
     * A permission belongs to some users of the model associated with its guard.
     */
    public function users(): BelongsToMany
    {
        return $this->morphedByMany(
            getModelForGuard($this->attributes['guard_name'] ?? config('auth.defaults.guard')),
            'model',
            Config::modelHasPermissionsTable(),
            app(PermissionRegistrar::class)->pivotPermission,
            Config::morphKey()
        );
    }

    /**
     * Find a permission by its name and guard name.
     *
     * @return PermissionContract|Permission
     *
     * @throws PermissionDoesNotExist
     */
    public static function findByName(BackedEnum|string $name, ?string $guardName = null): PermissionContract
    {
        $name = enum_value($name);
        $guardName ??= Guard::getDefaultName(static::class);

        $permission = static::findByParam(['name' => $name, 'guard_name' => $guardName]);

        if (! $permission) {
            throw PermissionDoesNotExist::create($name, $guardName);
        }

        return $permission;
    }

    /**
     * Find a permission by its id (and optionally guardName).
     *
     * @return PermissionContract|Permission
     */
    public static function findById(int|string $id, ?string $guardName = null): PermissionContract
    {
        $guardName ??= Guard::getDefaultName(static::class);

        $permission = static::findByParam([(new static)->getKeyName() => $id, 'guard_name' => $guardName]);

        if (! $permission) {
            throw PermissionDoesNotExist::withId($id, $guardName);
        }

        return $permission;
    }

    /**
     * Find or create permission by its name (and optionally guardName).
     *
     * @return PermissionContract|Permission
     */
    public static function findOrCreate(BackedEnum|string $name, ?string $guardName = null): PermissionContract
    {
        $name = enum_value($name);
        $guardName ??= Guard::getDefaultName(static::class);

        $attributes = ['name' => $name, 'guard_name' => $guardName];

        $permission = static::findByParam($attributes);

        if (! $permission) {
            $registrar = app(PermissionRegistrar::class);
            if ($registrar->teams) {
                $teamsKey = $registrar->teamsKey;
                $attributes[$teamsKey] = getPermissionsTeamId();
            }

            return static::query()->create($attributes);
        }

        return $permission;
    }

    /**
     * Finds a permission based on an array of parameters.
     *
     * @return PermissionContract|Permission|null
     */
    protected static function findByParam(array $params = []): ?PermissionContract
    {
        $query = static::query();

        $registrar = app(PermissionRegistrar::class);

        if ($registrar->teams) {
            $teamsKey = $registrar->teamsKey;

            $query->where(fn ($q) => $q->whereNull($teamsKey)
                ->orWhere($teamsKey, $params[$teamsKey] ?? getPermissionsTeamId())
            );
            unset($params[$teamsKey]);
        }

        foreach ($params as $key => $value) {
            $query->where($key, $value);
        }

        return $query->first();
    }

    /**
     * Determine if the permission is for the given guard.
     *
     * @throws GuardDoesNotMatch
     */
    public function forGuard(string $guardName): bool
    {
        if ($this->guard_name !== $guardName) {
            throw GuardDoesNotMatch::create($this->guard_name, $guardName);
        }

        return true;
    }
}
