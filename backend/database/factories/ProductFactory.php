<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use App\Enums\ProductStatus;
use App\Enums\ProductCondition;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $title = $this->faker->words(3, true);
        return [
            'seller_id' => User::factory()->seller(),
            'title' => ucfirst($title),
            'slug' => Str::slug($title) . '-' . $this->faker->unique()->numberBetween(1000, 9999),
            'description' => $this->faker->paragraph(),
            'category_id' => Category::factory(),
            'images' => [
                ['url' => 'https://via.placeholder.com/300', 'is_primary' => true],
                ['url' => 'https://via.placeholder.com/300', 'is_primary' => false],
            ],
            'condition' => $this->faker->randomElement(array_column(ProductCondition::cases(), 'value')),
            'starting_price' => $this->faker->randomFloat(2, 10, 1000),
            'reserve_price' => $this->faker->randomFloat(2, 100, 2000),
            'currency' => 'USD',
            'status' => ProductStatus::Published->value,
            'meta' => [],
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Draft->value,
        ]);
    }

    public function inAuction(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::InAuction->value,
        ]);
    }

    public function sold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Sold->value,
        ]);
    }

    public function newCondition(): static
    {
        return $this->state(fn (array $attributes) => [
            'condition' => ProductCondition::New->value,
        ]);
    }

    public function usedCondition(): static
    {
        return $this->state(fn (array $attributes) => [
            'condition' => ProductCondition::Used->value,
        ]);
    }
}