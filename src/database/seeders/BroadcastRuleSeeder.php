<?php

namespace Database\Seeders;

use App\Models\BroadcastRule;
use Illuminate\Database\Seeder;

class BroadcastRuleSeeder extends Seeder
{
    /**
     * Aturan generate pesan per jenis — outreach (H-7, H-1) dan
     * follow up (H-1, hari-H, tidak datang). Template tiap rule
     * dipilih belakangan lewat tab Aturan Pesan di modul Setting.
     */
    public function run(): void
    {
        $aturan = [
            ['jenis' => 'outreach',  'rule' => 'h-7'],
            ['jenis' => 'outreach',  'rule' => 'h-1'],
            ['jenis' => 'follow_up', 'rule' => 'h-1'],
            ['jenis' => 'follow_up', 'rule' => 'h'],
            ['jenis' => 'follow_up', 'rule' => 'tidak_datang'],
        ];

        foreach ($aturan as $a) {
            BroadcastRule::updateOrCreate(
                ['jenis' => $a['jenis'], 'rule' => $a['rule']],
                ['is_active' => true],
            );
        }
    }
}
