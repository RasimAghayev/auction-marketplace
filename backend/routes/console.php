<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('auction:close-expired', function () {
    $this->info('Closing expired auctions...');
    // This will be implemented in Phase 5
})->purpose('Close expired auctions');

Artisan::command('auction:process-scheduled', function () {
    $this->info('Processing scheduled auctions...');
    // This will be implemented in Phase 5
})->purpose('Process scheduled auctions');