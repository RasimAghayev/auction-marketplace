<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('database.default');

        Schema::connection($connection)->createIndex('products', ['status' => 1]);
        Schema::connection($connection)->createIndex('products', ['seller_id' => 1]);
        Schema::connection($connection)->createIndex('products', ['category_id' => 1]);
        Schema::connection($connection)->createIndex('products', ['slug' => 1]);

        Schema::connection($connection)->createIndex('auctions', ['status' => 1]);
        Schema::connection($connection)->createIndex('auctions', ['seller_id' => 1]);
        Schema::connection($connection)->createIndex('auctions', ['product_id' => 1]);

        Schema::connection($connection)->createIndex('bids', ['auction_id' => 1]);
        Schema::connection($connection)->createIndex('bids', ['user_id' => 1]);
        Schema::connection($connection)->createIndex('bids', ['status' => 1]);

        Schema::connection($connection)->createIndex('categories', ['slug' => 1]);
        Schema::connection($connection)->createIndex('notifications', ['user_id' => 1]);
    }

    public function down(): void
    {
        $connection = config('database.default');

        foreach (['products', 'auctions', 'bids', 'categories', 'notifications'] as $collection) {
            Schema::connection($connection)->dropIndexes($collection);
        }
    }
};
