<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class AuctionParticipant extends BaseModel
{
    use HasFactory, LogsActivity;

    protected $collection = 'auction_participants';

    protected $fillable = [
        'auction_id',
        'user_id',
        'first_bid_at',
        'last_bid_at',
        'total_bids',
        'highest_bid',
        'is_watcher',
    ];

    protected $casts = [
        'first_bid_at' => 'datetime',
        'last_bid_at' => 'datetime',
        'total_bids' => 'integer',
        'highest_bid' => 'decimal:2',
        'is_watcher' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['auction_id', 'user_id', 'total_bids', 'highest_bid', 'is_watcher'])
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