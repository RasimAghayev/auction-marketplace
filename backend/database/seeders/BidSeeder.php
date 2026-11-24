<?php

namespace Database\Seeders;

use App\Enums\AuctionStatus;
use App\Enums\BidStatus;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BidSeeder extends Seeder
{
    public function run(): void
    {
        Bid::truncate();

        $activeAuctions = Auction::where('status', AuctionStatus::Active->value)->get();
        $completedAuctions = Auction::where('status', AuctionStatus::Completed->value)->get();
        $buyers = User::where('role', 'user')->get();

        if ($activeAuctions->isEmpty() && $completedAuctions->isEmpty()) {
            $this->command?->error('No active or completed auctions found. Please run AuctionSeeder first.');
            return;
        }

        if ($buyers->isEmpty()) {
            $this->command?->error('No buyers found. Please run UserSeeder first.');
            return;
        }

        $bids = [];

        foreach ($activeAuctions as $auction) {
            $bidCount = min($auction->total_bids, 10);
            $currentPrice = $auction->starting_price;
            
            for ($i = 0; $i < $bidCount; $i++) {
                $currentPrice += $auction->min_bid_increment + rand(0, 50);
                $status = $i === $bidCount - 1 ? BidStatus::Winning->value : BidStatus::Outbid->value;
                
                $bids[] = [
                    'auction_id' => $auction->id,
                    'user_id' => $buyers->random()->id,
                    'amount' => round($currentPrice, 2),
                    'status' => $status,
                    'request_id' => Str::uuid()->toString(),
                    'meta' => [],
                    'created_at' => Carbon::now()->subHours(rand(1, $auction->getRemainingTimeAttribute() / 3600))->subMinutes(rand(0, 59)),
                ];
            }
        }

        foreach ($completedAuctions as $auction) {
            $bidCount = min($auction->total_bids, 10);
            $currentPrice = $auction->starting_price;
            
            for ($i = 0; $i < $bidCount; $i++) {
                $currentPrice += $auction->min_bid_increment + rand(0, 100);
                
                if ($i === $bidCount - 1 && $auction->winner_id) {
                    $status = BidStatus::Won->value;
                } elseif ($i === $bidCount - 1) {
                    $status = BidStatus::Lost->value;
                } else {
                    $status = BidStatus::Outbid->value;
                }
                
                $bids[] = [
                    'auction_id' => $auction->id,
                    'user_id' => $i === $bidCount - 1 && $auction->winner_id 
                        ? $auction->winner_id 
                        : $buyers->random()->id,
                    'amount' => round($currentPrice, 2),
                    'status' => $status,
                    'request_id' => Str::uuid()->toString(),
                    'meta' => [],
                    'created_at' => Carbon::now()->subDays(rand(1, 3))->subHours(rand(0, 23))->subMinutes(rand(0, 59)),
                ];
            }
        }

        foreach ($bids as $bidData) {
            Bid::create($bidData);
        }

        $this->command?->info('Created ' . Bid::count() . ' bids');
    }
}