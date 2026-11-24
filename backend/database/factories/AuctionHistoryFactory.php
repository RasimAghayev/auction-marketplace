<?php

namespace Database\Factories;

use App\Models\AuctionHistory;
use App\Models\Auction;
use App\Models\User;
use App\Enums\AuctionEventType;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuctionHistoryFactory extends Factory
{
    protected $model = AuctionHistory::class;

    public function definition(): array
    {
        return [
            'auction_id' => Auction::factory(),
            'user_id' => User::factory(),
            'event_type' => $this->faker->randomElement(array_column(AuctionEventType::cases(), 'value')),
            'data' => [],
        ];
    }

    public function bidPlaced(float $amount = 100): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => AuctionEventType::BidPlaced->value,
            'data' => ['amount' => $amount],
        ]);
    }

    public function bidAccepted(float $amount = 100): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => AuctionEventType::BidAccepted->value,
            'data' => ['amount' => $amount],
        ]);
    }

    public function auctionStarted(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => AuctionEventType::AuctionStarted->value,
        ]);
    }

    public function auctionEnded(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => AuctionEventType::AuctionEnded->value,
        ]);
    }

    public function auctionExtended(int $extensionMinutes = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => AuctionEventType::AuctionExtended->value,
            'data' => ['extension_minutes' => $extensionMinutes],
        ]);
    }
}