<?php

namespace Database\Factories;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => 'Test User ' . $this->faker->unique()->numberBetween(1, 10000),
            'email' => 'test' . $this->faker->unique()->numberBetween(1, 10000) . '@example.com',
            'email_verified_at' => Carbon::now(),
            'password' => 'password123',
            'role' => UserRole::User->value,
            'avatar' => null,
            'phone' => '+1-555-' . $this->faker->numberBetween(100, 999) . '-' . $this->faker->numberBetween(1000, 9999),
            'address' => '123 Test St, Test City',
            'is_active' => true,
            'last_login_at' => Carbon::now(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin->value,
        ]);
    }

    public function seller(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Seller->value,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}