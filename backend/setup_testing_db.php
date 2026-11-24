<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Switch to testing connection
config(['database.default' => 'mongodb_testing']);

use App\Models\Role;
use App\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

$registrar = app(PermissionRegistrar::class);
$registrar->forgetCachedPermissions();

echo "Setting up testing database...\n";

// Create roles/permissions collections if they don't exist
$collections = [
    'roles',
    'permissions',
    'model_has_roles',
    'model_has_permissions',
    'role_has_permissions',
];

foreach ($collections as $coll) {
    try {
        \DB::connection('mongodb_testing')->getMongoDB()->createCollection($coll);
        echo "Created collection: $coll\n";
    } catch (\Exception $e) {
        echo "Collection exists: $coll\n";
    }
}

// Create indexes
try {
    \DB::connection('mongodb_testing')->getMongoDB()->roles->createIndex(['name' => 1, 'guard_name' => 1], ['unique' => true, 'name' => 'idx_roles_name_guard']);
    \DB::connection('mongodb_testing')->getMongoDB()->permissions->createIndex(['name' => 1, 'guard_name' => 1], ['unique' => true, 'name' => 'idx_permissions_name_guard']);
    \DB::connection('mongodb_testing')->getMongoDB()->model_has_roles->createIndex(['model_id' => 1, 'model_type' => 1, 'role_id' => 1], ['unique' => true, 'name' => 'idx_model_has_roles']);
    \DB::connection('mongodb_testing')->getMongoDB()->model_has_permissions->createIndex(['model_id' => 1, 'model_type' => 1, 'permission_id' => 1], ['unique' => true, 'name' => 'idx_model_has_permissions']);
    \DB::connection('mongodb_testing')->getMongoDB()->role_has_permissions->createIndex(['permission_id' => 1, 'role_id' => 1], ['unique' => true, 'name' => 'idx_role_has_permissions']);
    echo "Indexes created\n";
} catch (\Exception $e) {
    echo "Index error: " . $e->getMessage() . "\n";
}

// Create default roles
$roles = ['admin', 'seller', 'user'];
$roleIds = [];
foreach ($roles as $roleName) {
    $role = Role::firstOrCreate([
        'name' => $roleName,
        'guard_name' => 'web'
    ]);
    $roleIds[$roleName] = $role->getKey();
    echo "Created role: $roleName (ID: {$role->getKey()})\n";
}

// Create default permissions
$permissions = [
    'view categories', 'create categories', 'update categories', 'delete categories',
    'view products', 'create products', 'update products', 'delete products',
    'view auctions', 'create auctions', 'update auctions', 'delete auctions',
    'place bids', 'view bids',
    'view notifications', 'manage notifications',
];
$permIds = [];
foreach ($permissions as $permName) {
    $perm = Permission::firstOrCreate([
        'name' => $permName,
        'guard_name' => 'web'
    ]);
    $permIds[$permName] = $perm->getKey();
    echo "Created permission: $permName (ID: {$perm->getKey()})\n";
}

// Assign permissions to roles directly in the pivot table
$connection = \DB::connection('mongodb_testing');

echo "Assigning permissions to roles...\n";

// Admin gets all permissions
foreach ($permIds as $permId) {
    $connection->table('role_has_permissions')->insert([
        'permission_id' => $permId,
        'role_id' => $roleIds['admin'],
    ]);
}

// Seller gets specific permissions
$sellerPerms = ['view products', 'create products', 'update products', 'delete products',
    'view auctions', 'create auctions', 'update auctions',
    'place bids', 'view bids', 'view notifications'];
foreach ($sellerPerms as $permName) {
    $connection->table('role_has_permissions')->insert([
        'permission_id' => $permIds[$permName],
        'role_id' => $roleIds['seller'],
    ]);
}

// User gets basic permissions
$userPerms = ['view products', 'view auctions', 'place bids', 'view bids', 'view notifications'];
foreach ($userPerms as $permName) {
    $connection->table('role_has_permissions')->insert([
        'permission_id' => $permIds[$permName],
        'role_id' => $roleIds['user'],
    ]);
}

echo "Done setting up testing database!\n";