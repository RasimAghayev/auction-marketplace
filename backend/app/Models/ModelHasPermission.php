<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ModelHasPermission extends Model
{
    protected $collection = 'model_has_permissions';
    protected $primaryKey = '_id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    public function getConnectionName(): string
    {
        if (app()->environment('testing') || ($_ENV['APP_ENV'] ?? '') === 'testing' || (getenv('APP_ENV') ?? '') === 'testing') {
            return 'mongodb_testing';
        }

        return 'mongodb';
    }
}