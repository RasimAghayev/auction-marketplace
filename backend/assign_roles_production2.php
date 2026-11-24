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
    $role = $connection->table('roles')->where('name', $roleName)->where('guard_name', 'web')->first();
    if ($role) {
        $roleId = $role->id ?? $role->_id;
        $roleIds[$roleName] = $roleId;
        echo "Role $roleName: " . ($roleId->__toString() ?? 'unknown') . "\n";
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

$userClass = \App\Models\User::class;

foreach ($users as $userData) {
    $user = \App\Models\User::on('mongodb')->where('email', $userData['email'])->first();
    if ($user && isset($roleIds[$userData['role']])) {
        $userId = $user->getKey();
        
        // Check if already assigned
        $existing = $connection->table('model_has_roles')
            ->where('model_id', $userId)
            ->where('model_type', $userClass)
            ->where('role_id', $roleIds[$userData['role']])
            ->first();
        
        if (!$existing) {
            $connection->table('model_has_roles')->insert([
                'model_id' => $userId,
                'model_type' => $userClass,
                'role_id' => $roleIds[$userData['role']],
            ]);
            echo "Assigned {$userData['role']} role to {$userData['email']}\n";
        } else {
            echo "Role already assigned to {$userData['email']}\n";
        }
    }
}

echo "Done!\n";