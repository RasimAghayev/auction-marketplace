<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Assign Spatie roles to production users
$connection = \DB::connection('mongodb');

echo "Assigning Spatie roles in production database...\n";

// Get role IDs
$roleIds = [];
$roles = ['admin', 'seller', 'user'];
foreach ($roles as $roleName) {
    $roleId = $connection->table('roles')->where('name', $roleName)->where('guard_name', 'web')->value('_id');
    if ($roleId) {
        $roleIds[$roleName] = $roleId;
        echo "Role $roleName: $roleId\n";
    } else {
        echo "Role $roleName not found\n";
    }
}

// Assign roles to users
$users = [
    ['email' => 'admin@example.com', 'role' => 'admin'],
    ['email' => 'seller1@example.com', 'role' => 'seller'],
    ['email' => 'seller2@example.com', 'role' => 'seller'],
    ['email' => 'test@example.com', 'role' => 'user'],
];

foreach ($users as $userData) {
    $userId = $connection->table('users')->where('email', $userData['email'])->value('_id');
    if ($userId && isset($roleIds[$userData['role']])) {
        // Check if already assigned
        $existing = $connection->table('model_has_roles')
            ->where('model_id', $userId)
            ->where('model_type', \App\Models\User::class)
            ->where('role_id', $roleIds[$userData['role']])
            ->first();
        
        if (!$existing) {
            $connection->table('model_has_roles')->insert([
                'model_id' => $userId,
                'model_type' => \App\Models\User::class,
                'role_id' => $roleIds[$userData['role']],
            ]);
            echo "Assigned {$userData['role']} role to {$userData['email']}\n";
        } else {
            echo "Role already assigned to {$userData['email']}\n";
        }
    }
}

echo "Done!\n";