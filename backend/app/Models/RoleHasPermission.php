<?php

namespace App\Models;

use App\Models\Concerns\MongodbConnection;
use MongoDB\Laravel\Eloquent\Model;

class RoleHasPermission extends Model
{
    use MongodbConnection;

    protected $collection = 'role_has_permissions';

    protected $primaryKey = '_id';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;
}
