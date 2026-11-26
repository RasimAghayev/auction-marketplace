<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

if (! function_exists('cleanTestDatabase')) {
    function cleanTestDatabase(): void
    {
        $collections = [
            'users',
            'products',
            'categories',
            'auctions',
            'bids',
            'notifications',
            'model_has_roles',
            'model_has_permissions',
            'personal_access_tokens',
            'auction_participants',
            'auction_history',
            'activity_log',
        ];

        foreach ($collections as $collection) {
            DB::connection('mongodb_testing')->table($collection)->delete();
        }
    }
}

if (! function_exists('seedTestRoles')) {
    function seedTestRoles(): void
    {
        foreach (['user', 'seller', 'admin'] as $name) {
            $id = 'role_'.$name;
            DB::connection('mongodb_testing')->table('roles')->updateOrInsert(
                ['_id' => $id],
                [
                    'id' => $id,
                    'name' => $name,
                    'guard_name' => 'sanctum',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}

if (! function_exists('assignRole')) {
    function assignRole(User $user, string $roleName): void
    {
        $roleId = DB::connection('mongodb_testing')->table('roles')->where('name', $roleName)->value('id');

        DB::connection('mongodb_testing')->table('model_has_roles')->insert([
            'model_id' => $user->getKey(),
            'model_type' => User::class,
            'role_id' => $roleId,
        ]);
    }
}
