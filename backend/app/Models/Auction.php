<?php

namespace App\Models;

use App\Enums\AuctionStatus;
use App\Enums\BidStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Auction extends BaseModel
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $collection = 'auctions';

    protected $fillable = [
        'product_id',
        'seller_id',
        'starting_price',
        'current_price',
        'min_bid_increment',
        'reserve_price',
        'start_date',
        'end_date',
        'status',
        'winner_id',
        'total_bids',
        'version',
        'meta',
    ];

    protected $casts = [
        'starting_price' => 'decimal:2',
        'current_price' => 'decimal:2',
        'min_bid_increment' => 'decimal:2',
        'reserve_price' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'status' => AuctionStatus::class,
        'winner_id' => 'string',
        'total_bids' => 'integer',
        'version' => 'integer',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'product_id',
                'starting_price',
                'current_price',
                'min_bid_increment',
                'reserve_price',
                'start_date',
                'end_date',
                'status',
                'winner_id',
                'total_bids',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function bids()
    {
        return $this->hasMany(Bid::class, 'auction_id')->orderBy('created_at', 'desc');
    }

    public function winningBid()
    {
        return $this->hasOne(Bid::class, 'auction_id')->where('status', BidStatus::Won->value);
    }

    public function participants()
    {
        return $this->hasMany(AuctionParticipant::class, 'auction_id');
    }

    public function history()
    {
        return $this->hasMany(AuctionHistory::class, 'auction_id')->orderBy('created_at', 'desc');
    }

    public function getRouteKeyName(): string
    {
        return '_id';
    }

    public function isActive(): bool
    {
        return $this->status === AuctionStatus::Active;
    }

    public function isScheduled(): bool
    {
        return $this->status === AuctionStatus::Scheduled;
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    public function hasEnded(): bool
    {
        return now()->greaterThan($this->end_date);
    }

    public function hasStarted(): bool
    {
        return now()->greaterThanOrEqualTo($this->start_date);
    }

    public function isWithinAutoExtendThreshold(): bool
    {
        $threshold = config('auction.auto_extend_threshold_seconds', 60);

        return $this->end_date->diffInSeconds(now()) <= $threshold;
    }

    public function getRemainingTimeAttribute(): int
    {
        return max(0, now()->diffInSeconds($this->end_date, false));
    }

    public function getNextMinimumBidAttribute(): float
    {
        return $this->current_price + $this->min_bid_increment;
    }

    public function scopeActive($query)
    {
        return $query->where('status', AuctionStatus::Active->value);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', AuctionStatus::Scheduled->value);
    }

    public function scopeEnded($query)
    {
        return $query->where('status', AuctionStatus::Completed->value)
            ->orWhere('status', AuctionStatus::Cancelled->value)
            ->orWhere('status', AuctionStatus::Unsold->value);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', AuctionStatus::Scheduled->value)
            ->where('start_date', '>', now());
    }

    public function scopeLive($query)
    {
        return $query->where('status', AuctionStatus::Active->value)
            ->where('end_date', '>', now());
    }
}
