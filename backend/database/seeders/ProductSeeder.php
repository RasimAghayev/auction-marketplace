<?php

namespace Database\Seeders;

use App\Enums\ProductCondition;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::truncate();

        $sellers = User::where('role', UserRole::Seller->value)->get();
        $categories = Category::where('is_active', true)->get();

        if ($sellers->isEmpty() || $categories->isEmpty()) {
            $this->command?->error('No sellers or categories found. Please run UserSeeder and CategorySeeder first.');
            return;
        }

        $products = [
            [
                'seller_id' => $sellers->first()->id,
                'title' => 'iPhone 15 Pro Max 256GB',
                'slug' => Str::slug('iPhone 15 Pro Max 256GB') . '-001',
                'description' => 'Brand new iPhone 15 Pro Max in Natural Titanium. 256GB storage, A17 Pro chip, 48MP camera system. Still sealed in original box.',
                'category_id' => $categories->where('slug', 'smartphones')->first()?->id,
                'images' => [
                    ['url' => 'https://via.placeholder.com/600x600?text=iPhone+15+Pro+Max', 'alt' => 'iPhone 15 Pro Max', 'is_primary' => true, 'sort_order' => 0],
                    ['url' => 'https://via.placeholder.com/600x600?text=iPhone+Box', 'alt' => 'iPhone Box', 'is_primary' => false, 'sort_order' => 1],
                ],
                'condition' => ProductCondition::New->value,
                'starting_price' => 800.00,
                'reserve_price' => 1000.00,
                'currency' => 'USD',
                'status' => ProductStatus::Published->value,
                'meta' => ['storage' => '256GB', 'color' => 'Natural Titanium'],
            ],
            [
                'seller_id' => $sellers->first()->id,
                'title' => 'MacBook Pro 16" M3 Max',
                'slug' => Str::slug('MacBook Pro 16 M3 Max') . '-002',
                'description' => 'MacBook Pro 16-inch with M3 Max chip, 36GB RAM, 1TB SSD. Space Black. Excellent condition, barely used. Includes original charger and box.',
                'category_id' => $categories->where('slug', 'laptops')->first()?->id,
                'images' => [
                    ['url' => 'https://via.placeholder.com/600x600?text=MacBook+Pro', 'alt' => 'MacBook Pro', 'is_primary' => true, 'sort_order' => 0],
                ],
                'condition' => ProductCondition::Used->value,
                'starting_price' => 1500.00,
                'reserve_price' => 2000.00,
                'currency' => 'USD',
                'status' => ProductStatus::Published->value,
                'meta' => ['chip' => 'M3 Max', 'ram' => '36GB', 'storage' => '1TB'],
            ],
            [
                'seller_id' => $sellers->last()->id,
                'title' => 'Sony WH-1000XM5 Headphones',
                'slug' => Str::slug('Sony WH-1000XM5 Headphones') . '-003',
                'description' => 'Industry-leading noise canceling wireless headphones. Black color. 30-hour battery life. Includes carrying case and cables.',
                'category_id' => $categories->where('slug', 'accessories')->first()?->id,
                'images' => [
                    ['url' => 'https://via.placeholder.com/600x600?text=Sony+Headphones', 'alt' => 'Sony Headphones', 'is_primary' => true, 'sort_order' => 0],
                ],
                'condition' => ProductCondition::New->value,
                'starting_price' => 200.00,
                'reserve_price' => 280.00,
                'currency' => 'USD',
                'status' => ProductStatus::Published->value,
                'meta' => ['color' => 'Black', 'type' => 'Over-ear'],
            ],
            [
                'seller_id' => $sellers->last()->id,
                'title' => 'Designer Leather Jacket',
                'slug' => Str::slug('Designer Leather Jacket') . '-004',
                'description' => 'Premium genuine leather jacket, size Medium. Classic biker style with asymmetric zipper. Worn only a few times, excellent condition.',
                'category_id' => $categories->where('slug', 'mens-clothing')->first()?->id,
                'images' => [
                    ['url' => 'https://via.placeholder.com/600x600?text=Leather+Jacket', 'alt' => 'Leather Jacket', 'is_primary' => true, 'sort_order' => 0],
                ],
                'condition' => ProductCondition::Used->value,
                'starting_price' => 150.00,
                'reserve_price' => 250.00,
                'currency' => 'USD',
                'status' => ProductStatus::Published->value,
                'meta' => ['size' => 'Medium', 'material' => 'Genuine Leather', 'color' => 'Black'],
            ],
            [
                'seller_id' => $sellers->first()->id,
                'title' => 'Vintage Rolex Submariner',
                'slug' => Str::slug('Vintage Rolex Submariner') . '-005',
                'description' => 'Vintage Rolex Submariner Date 16800 from 1980s. Stainless steel, black dial, original bracelet. Recently serviced, keeps excellent time. Comes with service papers.',
                'category_id' => $categories->where('slug', 'collectibles')->first()?->id,
                'images' => [
                    ['url' => 'https://via.placeholder.com/600x600?text=Rolex+Submariner', 'alt' => 'Rolex Submariner', 'is_primary' => true, 'sort_order' => 0],
                    ['url' => 'https://via.placeholder.com/600x600?text=Rolex+Case', 'alt' => 'Rolex Case', 'is_primary' => false, 'sort_order' => 1],
                ],
                'condition' => ProductCondition::Used->value,
                'starting_price' => 5000.00,
                'reserve_price' => 7000.00,
                'currency' => 'USD',
                'status' => ProductStatus::Published->value,
                'meta' => ['model' => '16800', 'year' => '1985', 'material' => 'Stainless Steel'],
            ],
            [
                'seller_id' => $sellers->last()->id,
                'title' => 'Ergonomic Office Chair',
                'slug' => Str::slug('Ergonomic Office Chair') . '-006',
                'description' => 'High-end ergonomic office chair with lumbar support, adjustable armrests, and breathable mesh back. Black. Used for 6 months, like new.',
                'category_id' => $categories->where('slug', 'furniture')->first()?->id,
                'images' => [
                    ['url' => 'https://via.placeholder.com/600x600?text=Office+Chair', 'alt' => 'Office Chair', 'is_primary' => true, 'sort_order' => 0],
                ],
                'condition' => ProductCondition::Used->value,
                'starting_price' => 200.00,
                'reserve_price' => 350.00,
                'currency' => 'USD',
                'status' => ProductStatus::Published->value,
                'meta' => ['color' => 'Black', 'material' => 'Mesh/Frame'],
            ],
            [
                'seller_id' => $sellers->first()->id,
                'title' => 'Nike Air Jordan 1 Retro High',
                'slug' => Str::slug('Nike Air Jordan 1 Retro High') . '-007',
                'description' => 'Nike Air Jordan 1 Retro High OG "Chicago" 2022 release. Size 10.5 US. Deadstock, never worn. Original box included.',
                'category_id' => $categories->where('slug', 'shoes')->first()?->id,
                'images' => [
                    ['url' => 'https://via.placeholder.com/600x600?text=Jordan+1', 'alt' => 'Jordan 1', 'is_primary' => true, 'sort_order' => 0],
                ],
                'condition' => ProductCondition::New->value,
                'starting_price' => 300.00,
                'reserve_price' => 450.00,
                'currency' => 'USD',
                'status' => ProductStatus::Published->value,
                'meta' => ['size' => '10.5', 'colorway' => 'Chicago', 'year' => '2022'],
            ],
            [
                'seller_id' => $sellers->last()->id,
                'title' => 'Camping Tent 4-Person',
                'slug' => Str::slug('Camping Tent 4-Person') . '-008',
                'description' => '4-person waterproof camping tent with rainfly, vestibule, and gear loft. Easy setup, used twice. Includes carrying bag and stakes.',
                'category_id' => $categories->where('slug', 'outdoor-gear')->first()?->id,
                'images' => [
                    ['url' => 'https://via.placeholder.com/600x600?text=Camping+Tent', 'alt' => 'Camping Tent', 'is_primary' => true, 'sort_order' => 0],
                ],
                'condition' => ProductCondition::Used->value,
                'starting_price' => 80.00,
                'reserve_price' => 120.00,
                'currency' => 'USD',
                'status' => ProductStatus::Published->value,
                'meta' => ['capacity' => '4-person', 'season' => '3-season', 'weight' => '5.2kg'],
            ],
            [
                'seller_id' => $sellers->first()->id,
                'title' => 'Abstract Oil Painting',
                'slug' => Str::slug('Abstract Oil Painting') . '-009',
                'description' => 'Original abstract oil painting on canvas, 24x36 inches. Signed by artist. Vibrant colors, textured brushstrokes. Ready to hang.',
                'category_id' => $categories->where('slug', 'art')->first()?->id,
                'images' => [
                    ['url' => 'https://via.placeholder.com/600x600?text=Oil+Painting', 'alt' => 'Oil Painting', 'is_primary' => true, 'sort_order' => 0],
                ],
                'condition' => ProductCondition::New->value,
                'starting_price' => 500.00,
                'reserve_price' => 800.00,
                'currency' => 'USD',
                'status' => ProductStatus::Published->value,
                'meta' => ['dimensions' => '24x36in', 'medium' => 'Oil on Canvas', 'signed' => true],
            ],
            [
                'seller_id' => $sellers->last()->id,
                'title' => 'Silver Morgan Dollar 1921',
                'slug' => Str::slug('Silver Morgan Dollar 1921') . '-010',
                'description' => '1921 Morgan Silver Dollar, MS-63 grade. Beautiful luster, minimal contact marks. Certified by NGC. Great addition to any collection.',
                'category_id' => $categories->where('slug', 'coins-currency')->first()?->id,
                'images' => [
                    ['url' => 'https://via.placeholder.com/600x600?text=Morgan+Dollar', 'alt' => 'Morgan Dollar', 'is_primary' => true, 'sort_order' => 0],
                ],
                'condition' => ProductCondition::Used->value,
                'starting_price' => 50.00,
                'reserve_price' => 80.00,
                'currency' => 'USD',
                'status' => ProductStatus::Published->value,
                'meta' => ['year' => '1921', 'grade' => 'MS-63', 'composition' => '90% Silver'],
            ],
        ];

        foreach ($products as $productData) {
            Product::factory()->create($productData);
        }

        $this->command?->info('Created ' . Product::count() . ' products');
    }
}