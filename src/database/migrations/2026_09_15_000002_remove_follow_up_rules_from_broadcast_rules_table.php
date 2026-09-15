<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Aturan generate follow up (H-1, hari-H, tidak-datang) dihapus sementara
 * karena belum dipakai. Aturan pada lingkungan baru dibuat via
 * BroadcastRuleSeeder yang kini hanya berisi outreach.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('broadcast_rules')->where('jenis', 'follow_up')->delete();
    }

    public function down(): void
    {
        $aturan = [
            ['jenis' => 'follow_up', 'rule' => 'h-1'],
            ['jenis' => 'follow_up', 'rule' => 'h'],
            ['jenis' => 'follow_up', 'rule' => 'tidak_datang'],
        ];

        foreach ($aturan as $a) {
            DB::table('broadcast_rules')->updateOrInsert(
                ['jenis' => $a['jenis'], 'rule' => $a['rule']],
                ['is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }
};
