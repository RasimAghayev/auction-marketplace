<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check model_has_roles data
$connection = \DB::connection('mongodb');

echo "=== Checking model_has_roles ===\n";
$mhr = $connection->table('model_has_roles')->get();
foreach ($mhr as $r) {
    var_dump($r);
}

echo "\n=== Checking users ===\n";
$users = $connection->table('users')->get();
foreach ($users as $u) {
    echo "User: {$u->email}, ID: ";
    var_dump($u->_id);
}