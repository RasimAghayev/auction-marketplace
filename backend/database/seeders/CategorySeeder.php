<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use MongoDB\BSON\ObjectId;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::truncate();

        // Create root categories first
        $electronics = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'description' => 'Electronic devices and gadgets',
            'image' => 'https://via.placeholder.com/300x300?text=Electronics',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $fashion = Category::create([
            'name' => 'Fashion',
            'slug' => 'fashion',
            'description' => 'Clothing and fashion items',
            'image' => 'https://via.placeholder.com/300x300?text=Fashion',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $home = Category::create([
            'name' => 'Home & Garden',
            'slug' => 'home-garden',
            'description' => 'Home and garden products',
            'image' => 'https://via.placeholder.com/300x300?text=Home+Garden',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $sports = Category::create([
            'name' => 'Sports & Outdoors',
            'slug' => 'sports-outdoors',
            'description' => 'Sports equipment and outdoor gear',
            'image' => 'https://via.placeholder.com/300x300?text=Sports',
            'is_active' => true,
            'sort_order' => 4,
        ]);

        $collectibles = Category::create([
            'name' => 'Collectibles',
            'slug' => 'collectibles',
            'description' => 'Rare and collectible items',
            'image' => 'https://via.placeholder.com/300x300?text=Collectibles',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        // Create child categories with proper ObjectId for parent_id
        $phones = Category::create([
            'name' => 'Smartphones',
            'slug' => 'smartphones',
            'description' => 'Mobile phones and accessories',
            'parent_id' => new ObjectId($electronics->id),
            'image' => 'https://via.placeholder.com/300x300?text=Smartphones',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $laptops = Category::create([
            'name' => 'Laptops',
            'slug' => 'laptops',
            'description' => 'Laptops and notebooks',
            'parent_id' => new ObjectId($electronics->id),
            'image' => 'https://via.placeholder.com/300x300?text=Laptops',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $accessories = Category::create([
            'name' => 'Accessories',
            'slug' => 'accessories',
            'description' => 'Electronic accessories',
            'parent_id' => new ObjectId($electronics->id),
            'image' => 'https://via.placeholder.com/300x300?text=Accessories',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $mensClothing = Category::create([
            'name' => "Men's Clothing",
            'slug' => 'mens-clothing',
            'description' => 'Clothing for men',
            'parent_id' => new ObjectId($fashion->id),
            'image' => 'https://via.placeholder.com/300x300?text=Mens+Clothing',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $womensClothing = Category::create([
            'name' => "Women's Clothing",
            'slug' => 'womens-clothing',
            'description' => 'Clothing for women',
            'parent_id' => new ObjectId($fashion->id),
            'image' => 'https://via.placeholder.com/300x300?text=Womens+Clothing',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $shoes = Category::create([
            'name' => 'Shoes',
            'slug' => 'shoes',
            'description' => 'Footwear for all',
            'parent_id' => new ObjectId($fashion->id),
            'image' => 'https://via.placeholder.com/300x300?text=Shoes',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $furniture = Category::create([
            'name' => 'Furniture',
            'slug' => 'furniture',
            'description' => 'Home furniture',
            'parent_id' => new ObjectId($home->id),
            'image' => 'https://via.placeholder.com/300x300?text=Furniture',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $decor = Category::create([
            'name' => 'Decor',
            'slug' => 'decor',
            'description' => 'Home decor items',
            'parent_id' => new ObjectId($home->id),
            'image' => 'https://via.placeholder.com/300x300?text=Decor',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $fitness = Category::create([
            'name' => 'Fitness Equipment',
            'slug' => 'fitness-equipment',
            'description' => 'Exercise and fitness equipment',
            'parent_id' => new ObjectId($sports->id),
            'image' => 'https://via.placeholder.com/300x300?text=Fitness',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $outdoor = Category::create([
            'name' => 'Outdoor Gear',
            'slug' => 'outdoor-gear',
            'description' => 'Camping and outdoor equipment',
            'parent_id' => new ObjectId($sports->id),
            'image' => 'https://via.placeholder.com/300x300?text=Outdoor',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $art = Category::create([
            'name' => 'Art',
            'slug' => 'art',
            'description' => 'Artwork and paintings',
            'parent_id' => new ObjectId($collectibles->id),
            'image' => 'https://via.placeholder.com/300x300?text=Art',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $coins = Category::create([
            'name' => 'Coins & Currency',
            'slug' => 'coins-currency',
            'description' => 'Rare coins and currency',
            'parent_id' => new ObjectId($collectibles->id),
            'image' => 'https://via.placeholder.com/300x300?text=Coins',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->command?->info('Created ' . Category::count() . ' categories');
    }
}