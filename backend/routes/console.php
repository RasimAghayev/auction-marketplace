<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Modules\Auctions\Services\AuctionService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('auction:process-scheduled', function () {
    $service = app(AuctionService::class);
    $count = 0;

    \App\Models\Auction::where('status', 'scheduled')
        ->where('start_date', '<=', now())
        ->each(function ($auction) use ($service, &$count) {
            try {
                $service->start($auction);
                $count++;
            } catch (\DomainException $e) {
                $this->warn("Auction {$auction->id}: " . $e->getMessage());
            }
        });

    $this->info("Started {$count} scheduled auction(s).");
})->purpose('Process scheduled auctions');

Artisan::command('auction:auto-extend', function () {
    $service = app(AuctionService::class);
    $count = 0;

    \App\Models\Auction::where('status', 'active')
        ->each(function ($auction) use ($service, &$count) {
            if ($service->autoExtend($auction)) {
                $count++;
            }
        });

    $this->info("Extended {$count} auction(s).");
})->purpose('Auto-extend auctions near closing');

Artisan::command('auction:close-expired', function () {
    $service = app(AuctionService::class);
    $count = 0;

    \App\Models\Auction::where('status', 'active')
        ->where('end_date', '<=', now())
        ->each(function ($auction) use ($service, &$count) {
            try {
                $service->complete($auction);
                $count++;
            } catch (\DomainException $e) {
                $this->warn("Auction {$auction->id}: " . $e->getMessage());
            }
        });

    $this->info("Completed {$count} expired auction(s).");
})->purpose('Close expired auctions');
