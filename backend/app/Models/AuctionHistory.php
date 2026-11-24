<?php

namespace App\Models;

use App\Enums\AuctionEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class AuctionHistory extends BaseModel
{
    use HasFactory, LogsActivity;

    protected $collection = 'auction_history';

    protected $fillable = [
        'auction_id',
        'user_id',
        'event_type',
        'data',
    ];

    protected $casts = [
        'event_type' => AuctionEventType::class,
        'data' => 'array',
        'created_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['auction_id', 'user_id', 'event_type', 'data'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function auction()
    {
        return $this->belongsTo(Auction::class, 'auction_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}