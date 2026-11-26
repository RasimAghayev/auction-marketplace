<?php

namespace App\Models;

use App\Models\Concerns\MongodbConnection;
use MongoDB\Laravel\Eloquent\Model;

class ModelHasRole extends Model
{
    use MongodbConnection;

    protected $collection = 'model_has_roles';

    protected $primaryKey = '_id';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;
}
