<?php

use App\Models\User;
use App\Models\Category;
use Laravel\Sanctum\Sanctum;

test('debug category creation with error', function () {
    // Create admin user
    $admin = User::factory()->create([
        'name' => 'Admin',
        'email' => 'admin@test.com',
        'password' => 'password123',
        'role' => 'admin',
    ]);
    
    // Assign admin role
    $admin->assignRole('admin');
    
    Sanctum::actingAs($admin);
    
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