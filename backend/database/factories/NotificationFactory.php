<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['bid_placed', 'outbid', 'auction_won', 'auction_lost', 'auction_ended', 'new_message']),
            'title' => $this->faker->sentence(3),
            'message' => $this->faker->paragraph(),
            'data' => [],
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => Carbon::now(),
        ]);
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    public function bidPlaced(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'bid_placed',
            'title' => 'New Bid Placed',
            'message' => 'A new bid has been placed on your auction.',
        ]);
    }

    public function outbid(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'outbid',
            'title' => 'You\'ve Been Outbid',
            'message' => 'Someone has placed a higher bid.',
        ]);
    }

    public function auctionWon(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'auction_won',
            'title' => 'Congratulations! You Won',
            'message' => 'You are the winner of the auction.',
        ]);
    }
}