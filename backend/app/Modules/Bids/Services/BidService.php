<?php

namespace App\Modules\Bids\Services;

use App\Enums\AuctionStatus;
use App\Enums\BidStatus;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;
use App\Modules\Notifications\Services\NotificationService;
use DomainException;
use Illuminate\Support\Facades\Cache;

class BidService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function place(Auction $auction, User $user, float $amount): Bid
    {
        $lockKey = 'lock:auction:' . $auction->id . ':bid';

        return Cache::lock($lockKey, 10)->get(function () use ($auction, $user, $amount) {
            $auction->refresh();

            if ($auction->status !== AuctionStatus::Active) {
                throw new DomainException('Auction is not active.');
            }

            if ($auction->seller_id === $user->id) {
                throw new DomainException('You cannot bid on your own auction.');
            }

            if ($auction->end_date && $auction->end_date <= now()) {
                throw new DomainException('Auction has ended.');
            }

            $minimumBid = $auction->current_price + $auction->min_bid_increment;
            if ($amount < $minimumBid) {
                throw new DomainException("Bid must be at least {$minimumBid}.");
            }

            $previousBid = $auction->bids()->where('status', BidStatus::Accepted)->orderByDesc('amount')->first();

            $bid = Bid::create([
                'auction_id' => $auction->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'status' => BidStatus::Pending,
            ]);

            $auction->update([
                'current_price' => $amount,
                'total_bids' => ($auction->total_bids ?? 0) + 1,
                'version' => ($auction->version ?? 0) + 1,
            ]);

            $bid->update(['status' => BidStatus::Accepted]);

            // Notify auction seller of new bid
            $this->notificationService->createForUser(
                $auction->seller_id,
                'new_bid',
                'New bid placed',
                "Someone placed a bid of {$amount} on your auction '{$auction->product->title}'.",
                ['auction_id' => (string) $auction->id, 'bid_id' => (string) $bid->id, 'amount' => $amount]
            );

            // Notify previous bidder they were outbid
            if ($previousBid && $previousBid->user_id !== $user->id) {
                $this->notificationService->createForUser(
                    $previousBid->user_id,
                    'outbid',
                    'You have been outbid',
                    "Your bid on '{$auction->product->title}' has been outbid by {$amount}.",
                    ['auction_id' => (string) $auction->id, 'bid_id' => (string) $bid->id, 'amount' => $amount]
                );
            }

            return $bid;
        });
    }
}
