<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model as MongoModel;

class ActivityLog extends MongoModel
{
    use HasFactory;

    protected $collection = 'activity_logs';

    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'batch_uuid',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getConnectionName(): string
    {
        if (app()->environment('testing') || ($_ENV['APP_ENV'] ?? '') === 'testing' || (getenv('APP_ENV') ?? '') === 'testing') {
            return 'mongodb_testing';
        }

        return 'mongodb';
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function causer()
    {
        return $this->morphTo();
    }
}
