<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Category extends BaseModel
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $collection = 'categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'image',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'parent_id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'description', 'parent_id', 'is_active', 'sort_order'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function auctions()
    {
        return $this->hasManyThrough(Auction::class, Product::class, 'category_id', 'product_id');
    }

    public function activeChildren()
    {
        return $this->children()->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function getRouteKeyName(): string
    {
        return '_id';
    }
}
