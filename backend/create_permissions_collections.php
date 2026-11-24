<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Role;
use App\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

$registrar = app(PermissionRegistrar::class);
$registrar->forgetCachedPermissions();

// Create roles collection
$rolesCollection = 'roles';
$permissionsCollection = 'permissions';
$modelHasRolesCollection = 'model_has_roles';
$modelHasPermissionsCollection = 'model_has_permissions';
$roleHasPermissionsCollection = 'role_has_permissions';

echo "Creating roles/permissions collections...\n";

// Check if collections exist and create if not
$collections = \DB::connection('mongodb')->getMongoDB()->listCollections();
$collectionNames = [];
foreach ($collections as $collection) {
    $collectionNames[] = $collection->getName();
}

$requiredCollections = [
    $rolesCollection,
    $permissionsCollection,
    $modelHasRolesCollection,
    $modelHasPermissionsCollection,
    $roleHasPermissionsCollection,
];

foreach ($requiredCollections as $coll) {
    if (!in_array($coll, $collectionNames)) {
        \DB::connection('mongodb')->getMongoDB()->createCollection($coll);
        echo "Created collection: $coll\n";
    } else {
        echo "Collection exists: $coll\n";
    }
}

// Create indexes
try {
    \DB::connection('mongodb')->getMongoDB()->$rolesCollection->createIndex(['name' => 1, 'guard_name' => 1], ['unique' => true, 'name' => 'idx_roles_name_guard']);
    \DB::connection('mongodb')->getMongoDB()->$permissionsCollection->createIndex(['name' => 1, 'guard_name' => 1], ['unique' => true, 'name' => 'idx_permissions_name_guard']);
    \DB::connection('mongodb')->getMongoDB()->$modelHasRolesCollection->createIndex(['model_id' => 1, 'model_type' => 1, 'role_id' => 1], ['unique' => true, 'name' => 'idx_model_has_roles']);
    \DB::connection('mongodb')->getMongoDB()->$modelHasPermissionsCollection->createIndex(['model_id' => 1, 'model_type' => 1, 'permission_id' => 1], ['unique' => true, 'name' => 'idx_model_has_permissions']);
    \DB::connection('mongodb')->getMongoDB()->$roleHasPermissionsCollection->createIndex(['permission_id' => 1, 'role_id' => 1], ['unique' => true, 'name' => 'idx_role_has_permissions']);
    echo "Indexes created\n";
} catch (\Exception $e) {
    echo "Index error: " . $e->getMessage() . "\n";
}

echo "Done!\n";