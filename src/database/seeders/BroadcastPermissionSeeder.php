<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class BroadcastPermissionSeeder extends Seeder
{
    /**
     * Buat permission "manage broadcasting" dan berikan ke superadmin + admin.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::findOrCreate('manage broadcasting');

        foreach (['superadmin', 'admin'] as $roleName) {
            Role::findOrCreate($roleName)->givePermissionTo($permission);
        }
    }
}
