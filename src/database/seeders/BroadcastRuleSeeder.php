<?php

namespace Database\Seeders;

use App\Models\BroadcastRule;
use Illuminate\Database\Seeder;

class BroadcastRuleSeeder extends Seeder
{
    /**
     * Aturan generate pesan per jenis — saat ini hanya outreach (H-7, H-1);
     * aturan follow up (H-1, hari-H, tidak datang) dinonaktifkan sementara
     * karena belum dipakai. Template tiap rule dipilih belakangan lewat
     * tab Aturan Pesan di modul Setting.
     */
    public function run(): void
    {
        $aturan = [
            ['jenis' => 'outreach',  'rule' => 'h-7'],
            ['jenis' => 'outreach',  'rule' => 'h-1'],
        ];

        foreach ($aturan as $a) {
            BroadcastRule::updateOrCreate(
                ['jenis' => $a['jenis'], 'rule' => $a['rule']],
                ['is_active' => true],
            );
        }
    }
}
