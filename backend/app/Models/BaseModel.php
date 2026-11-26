<?php

namespace App\Models;

use App\Models\Concerns\MongodbConnection;
use App\Models\Concerns\ObjectIdRouteBinding;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use MongoDB\Laravel\Eloquent\Model as MongoModel;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

abstract class BaseModel extends MongoModel
{
    use HasFactory, LogsActivity, MongodbConnection, ObjectIdRouteBinding, SoftDeletes;

    protected $guarded = [];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
