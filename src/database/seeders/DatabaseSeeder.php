<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            MasterPermissionSeeder::class,
            BroadcastPermissionSeeder::class,
            UserSeeder::class,
            FeaturePermissionSeeder::class,
            TujuanKunjunganSeeder::class,
            PoliUserSeeder::class,
            // MessageTemplateSeeder::class,
            TemplateCategorySeeder::class,
            BroadcastRuleSeeder::class,
            // MasterDataSeeder::class,
        ]);
    }
}
