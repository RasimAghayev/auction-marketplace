<?php

namespace Database\Seeders;

use App\Models\Auction;
use App\Models\AuctionParticipant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AuctionParticipantSeeder extends Seeder
{
    public function run(): void
    {
        AuctionParticipant::truncate();

        $auctions = Auction::whereIn('status', ['active', 'completed'])->get();
        $buyers = User::where('role', 'user')->get();

        if ($auctions->isEmpty() || $buyers->isEmpty()) {
            $this->command?->error('No auctions or buyers found. Please run AuctionSeeder and UserSeeder first.');
            return;
        }

        $participants = [];

        foreach ($auctions as $auction) {
            $participantCount = rand(2, 6);
            $selectedBuyers = $buyers->random(min($participantCount, $buyers->count()));
            
            foreach ($selectedBuyers as $index => $buyer) {
                $isBidder = $index < min(3, $participantCount);
                
                if ($isBidder) {
                    $totalBids = rand(1, 5);
                    $highestBid = $auction->starting_price + ($totalBids * $auction->min_bid_increment) + rand(0, 200);
                    
                    $participants[] = [
                        'auction_id' => $auction->id,
                        'user_id' => $buyer->id,
                        'first_bid_at' => Carbon::now()->subHours(rand(1, $auction->getRemainingTimeAttribute() / 3600 + 1)),
                        'last_bid_at' => Carbon::now()->subMinutes(rand(5, 120)),
                        'total_bids' => $totalBids,
                        'highest_bid' => round($highestBid, 2),
                        'is_watcher' => false,
                    ];
                } else {
                    $participants[] = [
                        'auction_id' => $auction->id,
                        'user_id' => $buyer->id,
                        'first_bid_at' => null,
                        'last_bid_at' => null,
                        'total_bids' => 0,
                        'highest_bid' => 0,
                        'is_watcher' => true,
                    ];
                }
            }
        }

        foreach ($participants as $participantData) {
            AuctionParticipant::create($participantData);
        }

        $this->command?->info('Created ' . AuctionParticipant::count() . ' auction participants');
    }
}