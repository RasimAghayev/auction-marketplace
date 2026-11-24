<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Create Spatie roles and permissions in production database
$connection = \DB::connection('mongodb');

echo "Creating Spatie roles and permissions in production database...\n";

// Create roles
$roles = [
    ['name' => 'admin', 'guard_name' => 'web'],
    ['name' => 'seller', 'guard_name' => 'web'],
    ['name' => 'user', 'guard_name' => 'web'],
];

$roleIds = [];
foreach ($roles as $roleData) {
    $existing = $connection->table('roles')->where('name', $roleData['name'])->where('guard_name', $roleData['guard_name'])->first();
    if (!$existing) {
        $roleId = $connection->table('roles')->insertGetId($roleData);
        echo "Created role: {$roleData['name']} with ID: $roleId\n";
        $roleIds[$roleData['name']] = $roleId;
    } else {
        echo "Role {$roleData['name']} already exists\n";
        $roleIds[$roleData['name']] = $existing->id ?? $existing->_id;
    }
}

// Create permissions
$permissions = [
    'view categories',
    'create categories',
    'edit categories',
    'delete categories',
    'view auctions',
    'create auctions',
    'edit auctions',
    'delete auctions',
    'view products',
    'create products',
    'edit products',
    'delete products',
    'view users',
    'manage users',
];

$permissionIds = [];
foreach ($permissions as $permName) {
    $existing = $connection->table('permissions')->where('name', $permName)->where('guard_name', 'web')->first();
    if (!$existing) {
        $permId = $connection->table('permissions')->insertGetId([
            'name' => $permName,
            'guard_name' => 'web',
        ]);
        echo "Created permission: $permName with ID: $permId\n";
        $permissionIds[$permName] = $permId;
    } else {
        echo "Permission $permName already exists\n";
        $permissionIds[$permName] = $existing->id ?? $existing->_id;
    }
}

// Assign all permissions to admin role
if (isset($roleIds['admin'])) {
    foreach ($permissionIds as $permId) {
        $existing = $connection->table('role_has_permissions')
            ->where('permission_id', $permId)
            ->where('role_id', $roleIds['admin'])
            ->first();
        if (!$existing) {
            $connection->table('role_has_permissions')->insert([
                'permission_id' => $permId,
                'role_id' => $roleIds['admin'],
            ]);
        }
    }
    echo "Assigned all permissions to admin role\n";
}

// Assign basic permissions to seller
if (isset($roleIds['seller'])) {
    $sellerPerms = ['view categories', 'view auctions', 'create auctions', 'edit auctions', 'view products', 'create products', 'edit products'];
    foreach ($sellerPerms as $permName) {
        if (isset($permissionIds[$permName])) {
            $existing = $connection->table('role_has_permissions')
                ->where('permission_id', $permissionIds[$permName])
                ->where('role_id', $roleIds['seller'])
                ->first();
            if (!$existing) {
                $connection->table('role_has_permissions')->insert([
                    'permission_id' => $permissionIds[$permName],
                    'role_id' => $roleIds['seller'],
                ]);
            }
        }
    }
    echo "Assigned seller permissions\n";
}

// Assign user permissions
if (isset($roleIds['user'])) {
    $userPerms = ['view categories', 'view auctions', 'view products'];
    foreach ($userPerms as $permName) {
        if (isset($permissionIds[$permName])) {
            $existing = $connection->table('role_has_permissions')
                ->where('permission_id', $permissionIds[$permName])
                ->where('role_id', $roleIds['user'])
                ->first();
            if (!$existing) {
                $connection->table('role_has_permissions')->insert([
                    'permission_id' => $permissionIds[$permName],
                    'role_id' => $roleIds['user'],
                ]);
            }
        }
    }
    echo "Assigned user permissions\n";
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