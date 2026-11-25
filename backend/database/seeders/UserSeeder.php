<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::truncate();

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'password' => 'password123',
            'role' => UserRole::Admin->value,
            'avatar' => null,
            'phone' => '+1-555-000-0001',
            'address' => ['street' => '123 Admin St', 'city' => 'Admin City', 'country' => 'USA'],
            'is_active' => true,
            'last_login_at' => now(),
        ]);

        $seller1 = User::create([
            'name' => 'John Seller',
            'email' => 'seller1@example.com',
            'email_verified_at' => now(),
            'password' => 'password123',
            'role' => UserRole::Seller->value,
            'avatar' => null,
            'phone' => '+1-555-111-1111',
            'address' => ['street' => '456 Seller Ave', 'city' => 'Seller City', 'country' => 'USA'],
            'is_active' => true,
            'last_login_at' => now(),
        ]);

        $seller2 = User::create([
            'name' => 'Jane Seller',
            'email' => 'seller2@example.com',
            'email_verified_at' => now(),
            'password' => 'password123',
            'role' => UserRole::Seller->value,
            'avatar' => null,
            'phone' => '+1-555-222-2222',
            'address' => ['street' => '789 Vendor Blvd', 'city' => 'Vendor Town', 'country' => 'USA'],
            'is_active' => true,
            'last_login_at' => now(),
        ]);

        $buyer1 = User::create([
            'name' => 'Alice Buyer',
            'email' => 'buyer1@example.com',
            'email_verified_at' => now(),
            'password' => 'password123',
            'role' => UserRole::User->value,
            'avatar' => null,
            'phone' => '+1-555-333-3333',
            'address' => ['street' => '321 Buyer Ln', 'city' => 'Buyer City', 'country' => 'USA'],
            'is_active' => true,
            'last_login_at' => now(),
        ]);

        $buyer2 = User::create([
            'name' => 'Bob Buyer',
            'email' => 'buyer2@example.com',
            'email_verified_at' => now(),
            'password' => 'password123',
            'role' => UserRole::User->value,
            'avatar' => null,
            'phone' => '+1-555-444-4444',
            'address' => ['street' => '654 Shopper Dr', 'city' => 'Shopper Town', 'country' => 'USA'],
            'is_active' => true,
            'last_login_at' => now(),
        ]);

        $buyer3 = User::create([
            'name' => 'Carol Buyer',
            'email' => 'buyer3@example.com',
            'email_verified_at' => now(),
            'password' => 'password123',
            'role' => UserRole::User->value,
            'avatar' => null,
            'phone' => '+1-555-555-5555',
            'address' => ['street' => '987 Customer Ct', 'city' => 'Customer City', 'country' => 'USA'],
            'is_active' => true,
            'last_login_at' => now(),
        ]);

        $this->command?->info('Created admin: ' . $admin->email);
        $this->command?->info('Created sellers: ' . $seller1->email . ', ' . $seller2->email);
        $this->command?->info('Created buyers: ' . $buyer1->email . ', ' . $buyer2->email . ', ' . $buyer3->email);

        $adminRole = Role::firstOrCreate(['name' => UserRole::Admin->value, 'guard_name' => 'sanctum']);
        $sellerRole = Role::firstOrCreate(['name' => UserRole::Seller->value, 'guard_name' => 'sanctum']);
        $userRole = Role::firstOrCreate(['name' => UserRole::User->value, 'guard_name' => 'sanctum']);

        $admin->assignRole($adminRole);
        $seller1->assignRole($sellerRole);
        $seller2->assignRole($sellerRole);
        $buyer1->assignRole($userRole);
        $buyer2->assignRole($userRole);
        $buyer3->assignRole($userRole);
    }
}