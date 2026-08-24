<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MasterPermissionSeeder extends Seeder
{
    /**
     * Buat permission "manage master" dan berikan ke superadmin + admin.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::findOrCreate('manage master');

        foreach (['superadmin', 'admin'] as $roleName) {
            Role::findOrCreate($roleName)->givePermissionTo($permission);
        }
    }
}
