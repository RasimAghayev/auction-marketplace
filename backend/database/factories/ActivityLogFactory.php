<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Product;
use App\Models\Auction;
use App\Models\Bid;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return [
            'log_name' => $this->faker->randomElement(['default', 'system', 'auction', 'product', 'user']),
            'description' => $this->faker->sentence(),
            'subject_type' => $this->faker->randomElement([Product::class, Auction::class, User::class, Bid::class]),
            'subject_id' => null,
            'causer_type' => User::class,
            'causer_id' => User::factory(),
            'properties' => [],
            'batch_uuid' => Str::uuid(),
        ];
    }
}