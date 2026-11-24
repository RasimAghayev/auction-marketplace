<?php

namespace Database\Seeders;

use App\Enums\ProductCondition;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductMassSeeder extends Seeder
{
    public function run(): void
    {
        $sellerIds = User::where('role', 'seller')->pluck('id')->toArray();
        $categoryIds = Category::pluck('id')->toArray();

        if (empty($sellerIds) || empty($categoryIds)) {
            $this->command?->error('No sellers or categories found. Run UserSeeder and CategorySeeder first.');
            return;
        }

        $targetCount = 100000; // 100k products
        $batchSize = 1000;
        $created = 0;

        $adjectives = ['Premium', 'Vintage', 'Modern', 'Classic', 'Luxury', 'Eco', 'Smart', 'Pro', 'Elite', 'Ultra'];
        $nouns = ['Widget', 'Gadget', 'Device', 'Tool', 'Accessory', 'Component', 'Module', 'System', 'Kit', 'Set'];
        $materials = ['Metal', 'Plastic', 'Wood', 'Glass', 'Ceramic', 'Carbon Fiber', 'Aluminum', 'Steel', 'Titanium', 'Bamboo'];
        $colors = ['Red', 'Blue', 'Green', 'Black', 'White', 'Silver', 'Gold', 'Rose Gold', 'Space Gray', 'Midnight Blue'];

        $this->command?->info("Creating {$targetCount} products in batches of {$batchSize}...");

        while ($created < $targetCount) {
            $remaining = $targetCount - $created;
            $currentBatch = min($batchSize, $remaining);

            $products = [];

            for ($i = 0; $i < $currentBatch; $i++) {
                $adjective = $adjectives[array_rand($adjectives)];
                $noun = $nouns[array_rand($nouns)];
                $material = $materials[array_rand($materials)];
                $color = $colors[array_rand($colors)];

                $title = "{$adjective} {$material} {$noun} {$color}";
                $slug = Str::slug($title) . '-' . Str::random(8);

                $basePrice = fake()->randomFloat(2, 10, 5000);
                $reservePrice = fake()->boolean(30) ? fake()->randomFloat(2, $basePrice * 1.1, $basePrice * 2) : null;

                $products[] = [
                    'seller_id' => $sellerIds[array_rand($sellerIds)],
                    'category_id' => $categoryIds[array_rand($categoryIds)],
                    'title' => $title,
                    'slug' => $slug,
                    'description' => fake()->paragraphs(3, true),
                    'images' => $this->generateImages(),
                    'condition' => ProductCondition::cases()[array_rand(ProductCondition::cases())]->value,
                    'starting_price' => $basePrice,
                    'reserve_price' => $reservePrice,
                    'currency' => 'USD',
                    'status' => ProductStatus::Published->value,
                    'meta' => [
                        'tags' => fake()->words(5),
                        'weight' => fake()->randomFloat(2, 0.1, 50),
                        'dimensions' => [
                            'width' => fake()->randomFloat(1, 10, 100),
                            'height' => fake()->randomFloat(1, 10, 100),
                            'depth' => fake()->randomFloat(1, 10, 100),
                        ],
                    ],
                    'created_at' => now()->subDays(fake()->numberBetween(0, 365)),
                    'updated_at' => now(),
                ];
            }

            Product::insert($products);
            $created += $currentBatch;

            if ($created % 10000 === 0 || $created >= $targetCount) {
                $this->command?->info("Created {$created}/{$targetCount} products...");
            }
        }

        $this->command?->info("Successfully created {$targetCount} products!");
    }

    private function generateImages(): array
    {
        $count = fake()->numberBetween(1, 5);
        $images = [];

        for ($i = 0; $i < $count; $i++) {
            $images[] = [
                'url' => 'https://picsum.photos/seed/' . Str::random(10) . '/800/600.jpg',
                'alt' => fake()->sentence(3),
                'is_primary' => $i === 0,
            ];
        }

        return $images;
    }
}