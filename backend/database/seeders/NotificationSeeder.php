<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use App\Models\Auction;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        Notification::truncate();

        $users = User::all();
        $auctions = Auction::whereIn('status', ['active', 'completed'])->get();

        if ($users->isEmpty()) {
            $this->command?->error('No users found. Please run UserSeeder first.');
            return;
        }

        $notifications = [];

        foreach ($users as $user) {
            $notificationCount = rand(3, 8);
            
            for ($i = 0; $i < $notificationCount; $i++) {
                $type = $this->getRandomNotificationType();
                $auction = $auctions->random();
                
                $notifications[] = [
                    'user_id' => $user->id,
                    'type' => $type,
                    'title' => $this->getTitleForType($type),
                    'message' => $this->getMessageForType($type, $auction),
                    'data' => [
                        'auction_id' => $auction->id,
                        'product_title' => $auction->product?->title ?? 'Unknown Product',
                    ],
                    'read_at' => rand(0, 1) ? Carbon::now()->subHours(rand(1, 48)) : null,
                    'created_at' => Carbon::now()->subDays(rand(0, 7))->subHours(rand(0, 23)),
                ];
            }
        }

        foreach ($notifications as $notificationData) {
            Notification::create($notificationData);
        }

        $this->command?->info('Created ' . Notification::count() . ' notifications');
    }

    private function getRandomNotificationType(): string
    {
        $types = ['bid_placed', 'outbid', 'auction_won', 'auction_lost', 'auction_ended', 'auction_starting_soon'];
        return $types[array_rand($types)];
    }

    private function getTitleForType(string $type): string
    {
        return match ($type) {
            'bid_placed' => 'New Bid Placed',
            'outbid' => "You've Been Outbid",
            'auction_won' => 'Congratulations! You Won',
            'auction_lost' => 'Auction Ended - You Lost',
            'auction_ended' => 'Auction Has Ended',
            'auction_starting_soon' => 'Auction Starting Soon',
            default => 'Notification',
        };
    }

    private function getMessageForType(string $type, $auction): string
    {
        $productTitle = $auction->product?->title ?? 'the auction';
        
        return match ($type) {
            'bid_placed' => "A new bid of \${$auction->current_price} has been placed on {$productTitle}.",
            'outbid' => "Someone has placed a higher bid on {$productTitle}. Current price: \${$auction->current_price}.",
            'auction_won' => "You won the auction for {$productTitle} at \${$auction->current_price}!",
            'auction_lost' => "The auction for {$productTitle} has ended. The winning bid was \${$auction->current_price}.",
            'auction_ended' => "The auction for {$productTitle} has ended.",
            'auction_starting_soon' => "The auction for {$productTitle} starts in less than an hour!",
            default => "Update regarding {$productTitle}.",
        };
    }
}