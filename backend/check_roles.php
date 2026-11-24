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
    
    // Check model_has_roles
    $roles = $connection->table('model_has_roles')
        ->where('model_id', $user->getKey())
        ->where('model_type', \App\Models\User::class)
        ->get();
    
    echo "Model has roles count: " . count($roles) . "\n";
    foreach ($roles as $r) {
        echo "  role_id: " . ($r->role_id ?? 'N/A') . "\n";
    }
    
    // Check roles
    if (count($roles) > 0) {
        $roleId = $roles[0]->role_id;
        $role = $connection->table('roles')->where('_id', $roleId)->first();
        echo "Role: ";
        print_r($role);
    }
    
    // Test hasRole directly
    echo "\nTesting hasRole('admin'): ";
    try {
        $result = $user->hasRole('admin');
        echo $result ? "true\n" : "false\n";
    } catch (\Throwable $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}