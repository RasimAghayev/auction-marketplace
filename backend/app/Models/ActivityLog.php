<?php

namespace App\Models;

use App\Models\Concerns\MongodbConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use MongoDB\Laravel\Eloquent\Model as MongoModel;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

class ActivityLog extends MongoModel implements ActivityContract
{
    use HasFactory, MongodbConnection;

    protected $collection = 'activity_logs';

    protected $connection = 'mongodb';

    protected $guarded = [];

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

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function getProperty(string $propertyName, mixed $defaultValue = null): mixed
    {
        return Arr::get($this->properties?->toArray() ?? [], $propertyName, $defaultValue);
    }
}
