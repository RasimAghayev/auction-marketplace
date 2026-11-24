<?php

use App\Models\User;
use App\Models\Role;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\DB;

uses(\Illuminate\Foundation\Testing\TestCase::class);

beforeEach(function () {
    echo "=== beforeEach started ===" . PHP_EOL;
    echo "APP_ENV: " . app()->environment() . PHP_EOL;
    echo "Role connection: " . (new Role())->getConnectionName() . PHP_EOL;
    
    $this->admin = User::factory()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => 'password123',
        'role' => 'admin',
        'is_active' => true,
    ]);
    
    echo "Admin user created: " . $this->admin->getKey() . PHP_EOL;
    
    $roleId = Role::where('name', 'admin')->value('id');
    echo "Admin role ID from query: " . ($roleId ?? 'NULL') . PHP_EOL;
    
    if ($roleId) {
        DB::connection('mongodb_testing')->table('model_has_roles')->insert([
            'model_id' => $this->admin->getKey(),
            'model_type' => User::class,
            'role_id' => $roleId,
        ]);
        echo "Role assigned" . PHP_EOL;
    } else {
        echo "ERROR: Role ID is null!" . PHP_EOL;
    }
    
    // Check model_has_roles
    $mhr = DB::connection('mongodb_testing')->table('model_has_roles')->where('model_id', $this->admin->getKey())->first();
    echo "Model has roles after insert: " . ($mhr ? 'EXISTS' : 'NOT FOUND') . PHP_EOL;
    if ($mhr) {
        echo "  role_id: " . $mhr->role_id . PHP_EOL;
    }
    
    echo "=== beforeEach ended ===" . PHP_EOL;
});

test('debug category creation', function () {
    Sanctum::actingAs($this->admin);
    
    $response = $this->postJson('/api/v1/admin/categories', [
        'name' => 'New Category',
        'slug' => 'new-category',
        'description' => 'Description',
        'is_active' => true,
    ]);
    
    echo "Status: " . $response->getStatusCode() . PHP_EOL;
    echo "Response: " . $response->getContent() . PHP_EOL;
    
    $response->assertStatus(201);
});