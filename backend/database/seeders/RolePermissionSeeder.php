<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $connection = app()->environment('testing') ? 'mongodb_testing' : 'mongodb';

        DB::connection($connection)->table('roles')->delete();
        DB::connection($connection)->table('permissions')->delete();
        DB::connection($connection)->table('role_has_permissions')->delete();
        DB::connection($connection)->table('model_has_roles')->delete();

        $adminRole = Role::create([
            'name' => UserRole::Admin->value,
            'guard_name' => 'sanctum',
        ]);

        $sellerRole = Role::create([
            'name' => UserRole::Seller->value,
            'guard_name' => 'sanctum',
        ]);

        $userRole = Role::create([
            'name' => UserRole::User->value,
            'guard_name' => 'sanctum',
        ]);

        $permissions = [
            'view auctions' => ['view_auctions', 'auctions.view'],
            'create auctions' => ['create_auctions', 'auctions.create'],
            'update auctions' => ['update_auctions', 'auctions.update'],
            'delete auctions' => ['delete_auctions', 'auctions.delete'],
            'start auctions' => ['start_auctions', 'auctions.start'],
            'cancel auctions' => ['cancel_auctions', 'auctions.cancel'],
            'complete auctions' => ['complete_auctions', 'auctions.complete'],
            'manage auctions' => ['manage_auctions', 'auctions.manage'],
            'view products' => ['view_products', 'products.view'],
            'create products' => ['create_products', 'products.create'],
            'update products' => ['update_products', 'products.update'],
            'delete products' => ['delete_products', 'products.delete'],
            'moderate products' => ['moderate_products', 'products.moderate'],
            'view bids' => ['view_bids', 'bids.view'],
            'place bids' => ['place_bids', 'bids.place'],
            'view categories' => ['view_categories', 'categories.view'],
            'create categories' => ['create_categories', 'categories.create'],
            'update categories' => ['update_categories', 'categories.update'],
            'delete categories' => ['delete_categories', 'categories.delete'],
            'view users' => ['view_users', 'users.view'],
            'update users' => ['update_users', 'users.update'],
            'suspend users' => ['suspend_users', 'users.suspend'],
            'view notifications' => ['view_notifications', 'notifications.view'],
            'view dashboard' => ['view_dashboard', 'dashboard.view'],
            'view admin dashboard' => ['view_admin_dashboard', 'admin.dashboard'],
            'view reports' => ['view_reports', 'reports.view'],
        ];

        $createdPermissions = [];

        foreach ($permissions as $label => [$name, $slug]) {
            $createdPermissions[$slug] = Permission::create([
                'name' => $name,
                'guard_name' => 'sanctum',
            ]);
        }

        $adminPermissions = array_values($createdPermissions);
        $adminRole->givePermissionTo($adminPermissions);

        $sellerPermissions = [
            $createdPermissions['view_auctions'],
            $createdPermissions['create_auctions'],
            $createdPermissions['update_auctions'],
            $createdPermissions['cancel_auctions'],
            $createdPermissions['start_auctions'],
            $createdPermissions['view_products'],
            $createdPermissions['create_products'],
            $createdPermissions['update_products'],
            $createdPermissions['delete_products'],
            $createdPermissions['view_bids'],
            $createdPermissions['place_bids'],
            $createdPermissions['view_categories'],
            $createdPermissions['view_notifications'],
            $createdPermissions['view_dashboard'],
        ];
        $sellerRole->givePermissionTo($sellerPermissions);

        $userPermissions = [
            $createdPermissions['view_auctions'],
            $createdPermissions['place_bids'],
            $createdPermissions['view_bids'],
            $createdPermissions['view_categories'],
            $createdPermissions['view_notifications'],
            $createdPermissions['view_dashboard'],
            $createdPermissions['update_users'],
        ];
        $userRole->givePermissionTo($userPermissions);

        foreach (User::where('role', UserRole::Admin->value)->get() as $admin) {
            $admin->assignRole($adminRole);
        }

        foreach (User::where('role', UserRole::Seller->value)->get() as $seller) {
            $seller->assignRole($sellerRole);
        }

        foreach (User::where('role', UserRole::User->value)->get() as $regularUser) {
            $regularUser->assignRole($userRole);
        }
    }
}
