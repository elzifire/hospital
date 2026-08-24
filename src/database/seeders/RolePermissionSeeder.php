<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Buat permissions
        $permissions = [
            'view dashboard',
            'manage users',
            'manage roles',
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Buat roles dan assign permissions
        // Superadmin — semua permissions
        $superadmin = Role::create(['name' => 'superadmin']);
        $superadmin->givePermissionTo(Permission::all());

        // Admin — view dashboard + manage users
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'view dashboard',
            'manage users',
        ]);

        // User — view dashboard only
        $user = Role::create(['name' => 'user']);
        $user->givePermissionTo([
            'view dashboard',
        ]);
    }
}

