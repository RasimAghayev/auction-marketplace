<?php

namespace Database\Factories;

use App\Models\Auction;
use App\Models\Product;
use App\Models\User;
use App\Enums\AuctionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class AuctionFactory extends Factory
{
    protected $model = Auction::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory()->inAuction(),
            'seller_id' => User::factory()->seller(),
            'starting_price' => $this->faker->randomFloat(2, 10, 500),
            'current_price' => $this->faker->randomFloat(2, 10, 500),
            'min_bid_increment' => $this->faker->randomFloat(2, 1, 50),
            'reserve_price' => $this->faker->randomFloat(2, 50, 1000),
            'start_date' => Carbon::now()->subHour(),
            'end_date' => Carbon::now()->addHours(24),
            'status' => AuctionStatus::Active->value,
            'winner_id' => null,
            'total_bids' => $this->faker->numberBetween(0, 50),
            'version' => 1,
            'meta' => [],
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AuctionStatus::Scheduled->value,
            'start_date' => Carbon::now()->addHour(),
            'end_date' => Carbon::now()->addHours(25),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AuctionStatus::Completed->value,
            'end_date' => Carbon::now()->subHour(),
            'completed_at' => Carbon::now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AuctionStatus::Cancelled->value,
            'cancelled_at' => Carbon::now(),
        ]);
    }

    public function unsold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AuctionStatus::Unsold->value,
            'end_date' => Carbon::now()->subHour(),
        ]);
    }

    public function endingSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_date' => Carbon::now()->addMinutes(30),
        ]);
    }
}