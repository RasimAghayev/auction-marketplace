<?php

use App\Models\User;
use App\Models\Role;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\DB;

uses(\Illuminate\Foundation\Testing\TestCase::class);

test('debug role connection', function () {
    echo "APP_ENV: " . app()->environment() . PHP_EOL;
    echo "Role connection: " . (new Role())->getConnectionName() . PHP_EOL;
    echo "User connection: " . (new User())->getConnectionName() . PHP_EOL;
    
    // Check role ID
    $roleId = Role::where('name', 'admin')->value('id');
    echo "Admin role ID: " . ($roleId ?? 'NULL') . PHP_EOL;
    
    // Check roles in testing DB
    $roles = DB::connection('mongodb_testing')->table('roles')->get();
    echo "Roles in testing DB:" . PHP_EOL;
    foreach ($roles as $role) {
        echo "  - " . $role->name . " (id: " . $role->id . ")" . PHP_EOL;
    }
    
    // Create admin user
    $admin = User::factory()->create([
        'name' => 'Admin',
        'email' => 'admin@test.com',
        'password' => 'password123',
        'role' => 'admin',
    ]);
    
    echo "Admin user ID: " . $admin->getKey() . PHP_EOL;
    
    // Assign role
    $roleId = Role::where('name', 'admin')->value('id');
    echo "Role ID for assignment: " . ($roleId ?? 'NULL') . PHP_EOL;
    
    DB::connection('mongodb_testing')->table('model_has_roles')->insert([
        'model_id' => $admin->getKey(),
        'model_type' => User::class,
        'role_id' => $roleId,
    ]);
    
    // Check model_has_roles
    $mhr = DB::connection('mongodb_testing')->table('model_has_roles')->where('model_id', $admin->getKey())->first();
    echo "Model has roles: " . ($mhr ? 'EXISTS' : 'NOT FOUND') . PHP_EOL;
    if ($mhr) {
        echo "  role_id: " . $mhr->role_id . PHP_EOL;
    }
    
    Sanctum::actingAs($admin);
    
    $response = $this->postJson('/api/v1/admin/categories', [
        'name' => 'New Category',
        'slug' => 'new-category',
        'description' => 'Description',
        'is_active' => true,
    ]);
    
    echo "Status: " . $response->getStatusCode() . PHP_EOL;
    echo "Response: " . $response->getContent() . PHP_EOL;
});