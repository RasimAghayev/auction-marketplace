<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Check collection validators
$connection = DB::connection('mongodb_testing');
$db = $connection->getDatabase();

$collections = ['users', 'categories', 'products', 'auctions', 'bids', 'auction_participants', 'auction_history', 'notifications', 'activities', 'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions'];

foreach ($collections as $collection) {
    try {
        $info = $db->listCollections(['filter' => ['name' => $collection]]);
        foreach ($info as $collInfo) {
            if (isset($collInfo['options']['validator'])) {
                echo "$collection HAS VALIDATOR: " . json_encode($collInfo['options']['validator']) . PHP_EOL;
            } else {
                echo "$collection NO VALIDATOR" . PHP_EOL;
            }
        }
    } catch (\Throwable $e) {
        echo "Error checking $collection: " . $e->getMessage() . PHP_EOL;
    }
}