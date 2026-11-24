<?php

namespace Database\Seeders;

use App\Enums\AuctionStatus;
use App\Models\Auction;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AuctionSeeder extends Seeder
{
    public function run(): void
    {
        Auction::truncate();

        $products = Product::where('status', 'published')->get();
        $sellers = User::where('role', 'seller')->get();

        if ($products->isEmpty() || $sellers->isEmpty()) {
            $this->command?->error('No published products or sellers found. Please run ProductSeeder and UserSeeder first.');
            return;
        }

        $auctions = [];

        $product1 = $products->where('title', 'iPhone 15 Pro Max 256GB')->first();
        if ($product1) {
            $auctions[] = [
                'product_id' => $product1->id,
                'seller_id' => $sellers->first()->id,
                'starting_price' => $product1->starting_price,
                'current_price' => 950.00,
                'min_bid_increment' => 25.00,
                'reserve_price' => $product1->reserve_price,
                'start_date' => Carbon::now()->subHours(2),
                'end_date' => Carbon::now()->addHours(22),
                'status' => AuctionStatus::Active->value,
                'winner_id' => null,
                'total_bids' => 12,
                'version' => 1,
                'meta' => [],
            ];
        }

        $product2 = $products->where('title', 'MacBook Pro 16" M3 Max')->first();
        if ($product2) {
            $auctions[] = [
                'product_id' => $product2->id,
                'seller_id' => $sellers->first()->id,
                'starting_price' => $product2->starting_price,
                'current_price' => 1800.00,
                'min_bid_increment' => 50.00,
                'reserve_price' => $product2->reserve_price,
                'start_date' => Carbon::now()->subHours(5),
                'end_date' => Carbon::now()->addHours(19),
                'status' => AuctionStatus::Active->value,
                'winner_id' => null,
                'total_bids' => 8,
                'version' => 1,
                'meta' => [],
            ];
        }

        $product3 = $products->where('title', 'Sony WH-1000XM5 Headphones')->first();
        if ($product3) {
            $auctions[] = [
                'product_id' => $product3->id,
                'seller_id' => $sellers->last()->id,
                'starting_price' => $product3->starting_price,
                'current_price' => 245.00,
                'min_bid_increment' => 10.00,
                'reserve_price' => $product3->reserve_price,
                'start_date' => Carbon::now()->subHours(1),
                'end_date' => Carbon::now()->addHours(23),
                'status' => AuctionStatus::Active->value,
                'winner_id' => null,
                'total_bids' => 15,
                'version' => 1,
                'meta' => [],
            ];
        }

        $product4 = $products->where('title', 'Designer Leather Jacket')->first();
        if ($product4) {
            $auctions[] = [
                'product_id' => $product4->id,
                'seller_id' => $sellers->last()->id,
                'starting_price' => $product4->starting_price,
                'current_price' => 180.00,
                'min_bid_increment' => 10.00,
                'reserve_price' => $product4->reserve_price,
                'start_date' => Carbon::now()->addHours(2),
                'end_date' => Carbon::now()->addHours(26),
                'status' => AuctionStatus::Scheduled->value,
                'winner_id' => null,
                'total_bids' => 0,
                'version' => 1,
                'meta' => [],
            ];
        }

        $product5 = $products->where('title', 'Vintage Rolex Submariner')->first();
        if ($product5) {
            $auctions[] = [
                'product_id' => $product5->id,
                'seller_id' => $sellers->first()->id,
                'starting_price' => $product5->starting_price,
                'current_price' => 6200.00,
                'min_bid_increment' => 100.00,
                'reserve_price' => $product5->reserve_price,
                'start_date' => Carbon::now()->subDays(2),
                'end_date' => Carbon::now()->subHours(1),
                'status' => AuctionStatus::Completed->value,
                'winner_id' => User::where('role', 'user')->first()?->id,
                'total_bids' => 23,
                'version' => 1,
                'meta' => [],
                'completed_at' => Carbon::now()->subHours(1),
            ];
        }

        $product6 = $products->where('title', 'Ergonomic Office Chair')->first();
        if ($product6) {
            $auctions[] = [
                'product_id' => $product6->id,
                'seller_id' => $sellers->first()->id,
                'starting_price' => $product6->starting_price,
                'current_price' => 280.00,
                'min_bid_increment' => 15.00,
                'reserve_price' => $product6->reserve_price,
                'start_date' => Carbon::now()->subDays(3),
                'end_date' => Carbon::now()->subHours(2),
                'status' => AuctionStatus::Completed->value,
                'winner_id' => User::where('role', 'user')->skip(1)->first()?->id,
                'total_bids' => 7,
                'version' => 1,
                'meta' => [],
                'completed_at' => Carbon::now()->subHours(2),
            ];
        }

        $product7 = $products->where('title', 'Nike Air Jordan 1 Retro High')->first();
        if ($product7) {
            $auctions[] = [
                'product_id' => $product7->id,
                'seller_id' => $sellers->last()->id,
                'starting_price' => $product7->starting_price,
                'current_price' => 380.00,
                'min_bid_increment' => 20.00,
                'reserve_price' => $product7->reserve_price,
                'start_date' => Carbon::now()->subDays(1),
                'end_date' => Carbon::now()->subMinutes(30),
                'status' => AuctionStatus::Completed->value,
                'winner_id' => User::where('role', 'user')->skip(2)->first()?->id,
                'total_bids' => 19,
                'version' => 1,
                'meta' => [],
                'completed_at' => Carbon::now()->subMinutes(30),
            ];
        }

        $product8 = $products->where('title', 'Camping Tent 4-Person')->first();
        if ($product8) {
            $auctions[] = [
                'product_id' => $product8->id,
                'seller_id' => $sellers->last()->id,
                'starting_price' => $product8->starting_price,
                'current_price' => $product8->starting_price,
                'min_bid_increment' => 5.00,
                'reserve_price' => $product8->reserve_price,
                'start_date' => Carbon::now()->subDays(4),
                'end_date' => Carbon::now()->subDays(1),
                'status' => AuctionStatus::Unsold->value,
                'winner_id' => null,
                'total_bids' => 0,
                'version' => 1,
                'meta' => [],
            ];
        }

        $product9 = $products->where('title', 'Abstract Oil Painting')->first();
        if ($product9) {
            $auctions[] = [
                'product_id' => $product9->id,
                'seller_id' => $sellers->first()->id,
                'starting_price' => $product9->starting_price,
                'current_price' => 650.00,
                'min_bid_increment' => 25.00,
                'reserve_price' => $product9->reserve_price,
                'start_date' => Carbon::now()->addHours(24),
                'end_date' => Carbon::now()->addHours(48),
                'status' => AuctionStatus::Scheduled->value,
                'winner_id' => null,
                'total_bids' => 0,
                'version' => 1,
                'meta' => [],
            ];
        }

        $product10 = $products->where('title', 'Silver Morgan Dollar 1921')->first();
        if ($product10) {
            $auctions[] = [
                'product_id' => $product10->id,
                'seller_id' => $sellers->last()->id,
                'starting_price' => $product10->starting_price,
                'current_price' => 65.00,
                'min_bid_increment' => 5.00,
                'reserve_price' => $product10->reserve_price,
                'start_date' => Carbon::now()->subHours(3),
                'end_date' => Carbon::now()->addHours(21),
                'status' => AuctionStatus::Active->value,
                'winner_id' => null,
                'total_bids' => 4,
                'version' => 1,
                'meta' => [],
            ];
        }

        foreach ($auctions as $auctionData) {
            Auction::create($auctionData);
        }

        $this->command?->info('Created ' . Auction::count() . ' auctions');
    }
}