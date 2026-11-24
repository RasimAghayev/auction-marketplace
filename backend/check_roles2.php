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
    echo "User model class: " . \App\Models\User::class . "\n";
    
    // Check model_has_roles with different model_type
    $roles = $connection->table('model_has_roles')
        ->where('model_id', $user->getKey())
        ->get();
    
    echo "Model has roles count (any model_type): " . count($roles) . "\n";
    foreach ($roles as $r) {
        echo "  model_type: " . ($r->model_type ?? 'N/A') . ", role_id: " . ($r->role_id ?? 'N/A') . "\n";
    }
    
    // Check roles table
    echo "\nRoles in DB:\n";
    $allRoles = $connection->table('roles')->get();
    foreach ($allRoles as $r) {
        echo "  _id: " . ($r->_id ?? 'N/A') . ", name: " . ($r->name ?? 'N/A') . "\n";
    }
    
    // Check model_has_roles with correct model_type
    $userClass = \App\Models\User::class;
    $roles2 = $connection->table('model_has_roles')
        ->where('model_id', $user->getKey())
        ->where('model_type', $userClass)
        ->get();
    
    echo "\nModel has roles with User class: " . count($roles2) . "\n";
}