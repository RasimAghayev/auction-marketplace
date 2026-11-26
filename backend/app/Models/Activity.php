<?php

namespace App\Models;

use App\Models\Concerns\MongodbConnection;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;
use Spatie\Activitylog\Enums\ActivityEvent;

class Activity extends Model implements ActivityContract
{
    use MongodbConnection, SoftDeletes;

    public $guarded = [];

    protected $collection = 'activity_log';

    protected function casts(): array
    {
        return [
            'attribute_changes' => 'collection',
            'properties' => 'collection',
        ];
    }

    public function subject(): MorphTo
    {
        if (config('activitylog.include_soft_deleted_subjects')) {
            return $this->morphTo()->withoutGlobalScope(SoftDeletingScope::class);
        }

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

    public function scopeInLog(Builder $query, BackedEnum|string|array ...$logNames): Builder
    {
        if (is_array($logNames[0])) {
            $logNames = $logNames[0];
        }

        return $query->whereIn('log_name', array_map(
            fn (BackedEnum|string $logName) => $logName instanceof BackedEnum ? $logName->value : $logName,
            $logNames,
        ));
    }

    public function scopeCausedBy(Builder $query, Model $causer): Builder
    {
        return $query
            ->where('causer_type', $causer->getMorphClass())
            ->where('causer_id', (string) $causer->getKey());
    }

    public function scopeForSubject(Builder $query, Model $subject): Builder
    {
        return $query
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', (string) $subject->getKey());
    }

    public function scopeForEvent(Builder $query, string|ActivityEvent $event): Builder
    {
        return $query->where('event', $event instanceof ActivityEvent ? $event->value : $event);
    }
}
