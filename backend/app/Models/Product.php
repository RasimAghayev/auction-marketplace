<?php

namespace App\Models;

use App\Enums\ProductCondition;
use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Product extends BaseModel
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $collection = 'products';

    protected $fillable = [
        'seller_id',
        'title',
        'slug',
        'description',
        'category_id',
        'images',
        'condition',
        'starting_price',
        'reserve_price',
        'currency',
        'status',
        'meta',
    ];

    protected $casts = [
        'images' => 'array',
        'condition' => ProductCondition::class,
        'starting_price' => 'decimal:2',
        'reserve_price' => 'decimal:2',
        'currency' => 'string',
        'status' => ProductStatus::class,
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'slug', 'description', 'category_id', 'condition', 'starting_price', 'reserve_price', 'currency', 'status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function auction()
    {
        return $this->hasOne(Auction::class, 'product_id');
    }

    public function primaryImage(): ?array
    {
        $images = $this->images ?? [];
        foreach ($images as $image) {
            if (($image['is_primary'] ?? false) === true) {
                return $image;
            }
        }

        return $images[0] ?? null;
    }

    public function scopePublished($query)
    {
        return $query->where('status', ProductStatus::Published->value);
    }

    public function scopeInAuction($query)
    {
        return $query->where('status', ProductStatus::InAuction->value);
    }

    public function scopeAvailableForAuction($query)
    {
        return $query->whereIn('status', [ProductStatus::Published->value, ProductStatus::InAuction->value]);
    }

    public function canBeAuctioned(): bool
    {
        return in_array($this->status->value, [ProductStatus::Published->value, ProductStatus::Draft->value]);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
