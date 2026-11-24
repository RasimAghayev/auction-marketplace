<?php

namespace Database\Factories;

use App\Models\AuctionParticipant;
use App\Models\Auction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class AuctionParticipantFactory extends Factory
{
    protected $model = AuctionParticipant::class;

    public function definition(): array
    {
        return [
            'auction_id' => Auction::factory(),
            'user_id' => User::factory(),
            'first_bid_at' => null,
            'last_bid_at' => null,
            'total_bids' => 0,
            'highest_bid' => 0,
            'is_watcher' => false,
        ];
    }

    public function bidder(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_bids' => $this->faker->numberBetween(1, 10),
            'highest_bid' => $this->faker->randomFloat(2, 50, 1000),
            'first_bid_at' => Carbon::now()->subHours(2),
            'last_bid_at' => Carbon::now(),
            'is_watcher' => false,
        ]);
    }

    public function watcher(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_watcher' => true,
        ]);
    }
}