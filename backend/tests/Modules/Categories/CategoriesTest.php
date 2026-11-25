<?php

use App\Models\Category;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;

uses(LaravelTestCase::class);

describe('Categories Module', function () {
    test('anyone can list active categories', function () {
        Category::factory()->count(3)->create(['is_active' => true]);
        Category::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200);
    });

    test('anyone can view a category', function () {
        $category = Category::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(200);
    });

    test('admin can create a category', function () {
        $admin = \App\Models\User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $roleId = \Illuminate\Support\Facades\DB::connection('mongodb_testing')
            ->table('roles')->where('name', 'admin')->value('id');

        \Illuminate\Support\Facades\DB::connection('mongodb_testing')
            ->table('model_has_roles')->insert([
                'model_id' => $admin->getKey(),
                'model_type' => \App\Models\User::class,
                'role_id' => $roleId,
            ]);

        Laravel\Sanctum\Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/admin/categories', [
            'name' => 'Books',
            'slug' => 'books',
        ]);

        $response->assertStatus(201);
    });

    test('admin can update a category', function () {
        $category = Category::factory()->create(['name' => 'Old Name']);

        $admin = \App\Models\User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $roleId = \Illuminate\Support\Facades\DB::connection('mongodb_testing')
            ->table('roles')->where('name', 'admin')->value('id');

        \Illuminate\Support\Facades\DB::connection('mongodb_testing')
            ->table('model_has_roles')->insert([
                'model_id' => $admin->getKey(),
                'model_type' => \App\Models\User::class,
                'role_id' => $roleId,
            ]);

        Laravel\Sanctum\Sanctum::actingAs($admin);

        $response = $this->putJson("/api/v1/admin/categories/{$category->id}", [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200);
    });

    test('admin can delete a category without children', function () {
        $category = Category::factory()->create();

        $admin = \App\Models\User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $roleId = \Illuminate\Support\Facades\DB::connection('mongodb_testing')
            ->table('roles')->where('name', 'admin')->value('id');

        \Illuminate\Support\Facades\DB::connection('mongodb_testing')
            ->table('model_has_roles')->insert([
                'model_id' => $admin->getKey(),
                'model_type' => \App\Models\User::class,
                'role_id' => $roleId,
            ]);

        Laravel\Sanctum\Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/v1/admin/categories/{$category->id}");

        $response->assertStatus(200);
    });

    test('admin cannot delete a category with children', function () {
        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);

        $admin = \App\Models\User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $roleId = \Illuminate\Support\Facades\DB::connection('mongodb_testing')
            ->table('roles')->where('name', 'admin')->value('id');

        \Illuminate\Support\Facades\DB::connection('mongodb_testing')
            ->table('model_has_roles')->insert([
                'model_id' => $admin->getKey(),
                'model_type' => \App\Models\User::class,
                'role_id' => $roleId,
            ]);

        Laravel\Sanctum\Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/v1/admin/categories/{$parent->id}");

        $response->assertStatus(422);
    });
});
