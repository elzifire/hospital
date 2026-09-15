<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Restrukturisasi permission menjadi per fitur (menggantikan permission
 * agregat "manage master", "manage broadcasting", dan "manage settings").
 *
 * Aman dijalankan berulang pada database berisi data (tanpa migrate:fresh):
 *  - permission dibuat dengan findOrCreate (idempoten)
 *  - role disinkronkan dengan syncPermissions (menghapus pemberian lama)
 *  - permission agregat lama dihapus beserta relasinya
 *
 * Jalankan manual: php artisan db:seed --class=FeaturePermissionSeeder
 */
class FeaturePermissionSeeder extends Seeder
{
    /**
     * Permission per fitur — satu kunci akses per modul menu.
     */
    public static function fiturPermissions(): array
    {
        return [
            // Data master
            'manage pnpp',
            'manage satker',
            'manage penyakit',
            'manage poli',
            'manage dokter',
            'manage jadwal',

            // Layanan & broadcasting
            'manage kunjungan',
            'manage register-pnpp',
            'manage outreach',
            'manage digital-reminder',
            'manage respon',
            'manage follow-up',
            'manage template',
        ];
    }

    /**
     * Permission agregat lama yang sudah tidak dipakai kode.
     */
    public static function permissionUsang(): array
    {
        return ['manage master', 'manage broadcasting', 'manage settings'];
    }

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $semua = array_merge(
            ['view dashboard', 'manage users', 'manage roles'],
            self::fiturPermissions(),
            // Khusus superadmin (tidak diberikan ke admin) — lihat komentar di atas.
            ['manage auto-reply'],
        );

        foreach ($semua as $nama) {
            Permission::findOrCreate($nama);
        }

        // Superadmin: semua fitur + manajemen akun + auto-reply.
        Role::findOrCreate('superadmin')->syncPermissions($semua);

        // Admin: seluruh fitur operasional + manajemen pengguna
        // (tanpa "manage roles" — khusus superadmin).
        Role::findOrCreate('admin')->syncPermissions(
            array_merge(['view dashboard', 'manage users'], self::fiturPermissions()),
        );

        // User biasa: hanya dashboard (fitur dibuka per permintaan via menu Role).
        Role::findOrCreate('user')->syncPermissions(['view dashboard']);

        // Poli: akun petugas/penanggung jawab tiap instalasi — dashboard +
        // digital reminder + follow up + respon (lihat & balas balasan
        // pasien polinya sendiri). Data dibatasi ke polinya sendiri
        // (lihat DigitalReminderController, FollowUpController & ResponController).
        Role::findOrCreate('poli')->syncPermissions([
            'view dashboard',
            'manage digital-reminder',
            'manage kunjungan',
            'manage follow-up',
            'manage respon',
        ]);

        // Bersihkan permission agregat lama beserta relasi role/model-nya.
        foreach (self::permissionUsang() as $nama) {
            Permission::where('name', $nama)->get()->each->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
