<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Disable collection validators in testing database
$connection = \DB::connection('mongodb_testing');
$db = $connection->getDatabase();

$collections = ['users', 'categories', 'products', 'auctions', 'bids', 'auction_participants', 'auction_history', 'notifications', 'activities', 'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions'];

foreach ($collections as $collection) {
    try {
        $db->dropCollection($collection);
        echo "Dropped collection: $collection\n";
    } catch (\Throwable $e) {
        echo "Could not drop $collection: " . $e->getMessage() . "\n";
    }
}

// Recreate collections without validators
foreach ($collections as $collection) {
    try {
        $db->createCollection($collection);
        echo "Created collection: $collection\n";
    } catch (\Throwable $e) {
        echo "Could not create $collection: " . $e->getMessage() . "\n";
    }
}

echo "Done!\n";