<?php

namespace Database\Factories;

use App\Models\Bid;
use App\Models\Auction;
use App\Models\User;
use App\Enums\BidStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BidFactory extends Factory
{
    protected $model = Bid::class;

    public function definition(): array
    {
        return [
            'auction_id' => Auction::factory(),
            'user_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'status' => BidStatus::Pending->value,
            'request_id' => (string) Str::uuid(),
            'meta' => [],
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BidStatus::Accepted->value,
        ]);
    }

    public function winning(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BidStatus::Winning->value,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BidStatus::Rejected->value,
        ]);
    }

    public function outbid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BidStatus::Outbid->value,
        ]);
    }
}