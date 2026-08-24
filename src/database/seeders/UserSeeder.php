<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Super Admin
        $superadmin = User::create([
            'name'              => 'Super Admin',
            'email'             => 'superadmin@gmail.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('password'),
        ]);
        $superadmin->assignRole('superadmin');

        // Admin
        $admin = User::create([
            'name'              => 'Admin',
            'email'             => 'admin@gmail.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('password'),
        ]);
        $admin->assignRole('admin');

        // User
        $user = User::create([
            'name'              => 'User',
            'email'             => 'user@gmail.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('password'),
        ]);
        $user->assignRole('user');
    }
}

