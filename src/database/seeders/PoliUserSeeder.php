<?php

namespace Database\Seeders;

use App\Models\Poli;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Buat satu akun login untuk tiap instalasi/poli sesuai PoliSeeder.
 *
 * Idempotent: aman dijalankan ulang tanpa migrate:fresh.
 *  - poli dipastikan ada (memanggil PoliSeeder)
 *  - role "poli" dibuat bila belum ada
 *  - akun dibuat bila belum ada, lalu role & detail poli disinkronkan
 */
class PoliUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(PoliSeeder::class);

        $role = Role::findOrCreate('poli');

        foreach (Poli::orderBy('kode')->get() as $poli) {
            $email = Str::slug($poli->nama).'@gmail.com';

            $user = User::whereHas('userDetail', fn ($q) => $q->where('poli_id', $poli->id))
                ->orWhere('email', $email)
                ->first();

            if (! $user) {
                $user = User::create([
                    'name' => $poli->nama,
                    'email' => $email,
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]);
            }

            $user->syncRoles([$role->name]);
            $user->userDetail()->updateOrCreate([], ['poli_id' => $poli->id]);
        }
    }
}
