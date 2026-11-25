<?php

namespace App\Models;

use App\Enums\BidStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\BSON\ObjectId;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Bid extends BaseModel
{
    use HasFactory, LogsActivity;

    protected $collection = 'bids';

    protected $fillable = [
        'auction_id',
        'user_id',
        'amount',
        'status',
        'request_id',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => BidStatus::class,
        'request_id' => 'string',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['auction_id', 'user_id', 'amount', 'status', 'request_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function getRouteKeyName(): string
    {
        return '_id';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if (is_string($value) && preg_match('/^[0-9a-fA-F]{24}$/', $value)) {
            $bid = static::where('_id', new ObjectId($value))->first();

            if ($bid) {
                return $bid;
            }
        }

        return parent::resolveRouteBinding($value, $field);
    }

    public function auction()
    {
        return $this->belongsTo(Auction::class, 'auction_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isWinning(): bool
    {
        return $this->status === BidStatus::Winning;
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    public function scopeForAuction($query, $auctionId)
    {
        return $query->where('auction_id', $auctionId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeWinning($query)
    {
        return $query->where('status', BidStatus::Winning->value);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [BidStatus::Pending->value, BidStatus::Accepted->value, BidStatus::Winning->value]);
    }
}