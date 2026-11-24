<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check production database role assignments
$connection = \DB::connection('mongodb');

echo "=== Checking Production Role Assignments ===\n";

// Get admin user
$user = \App\Models\User::on('mongodb')->where('email', 'admin@example.com')->first();
if ($user) {
    echo "Admin user ID: {$user->getKey()}\n";
    
    // Check roles table with full data
    $roles = $connection->table('roles')->get();
    foreach ($roles as $r) {
        echo "Role: ";
        var_dump($r);
    }
    
    // Check model_has_roles with full data
    $mhr = $connection->table('model_has_roles')->get();
    echo "\nAll model_has_roles:\n";
    foreach ($mhr as $r) {
        var_dump($r);
    }
}