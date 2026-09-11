<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dedup unik (jenis, rule, reminder_id) hanya berlaku untuk pesan
     * otomatis dari aturan — pesan manual (rule "manual") boleh dibuat
     * berulang untuk penjadwalan yang sama. Pgsql/SQLite memakai
     * partial unique index; MySQL (tanpa partial index) tetap unique
     * penuh.
     */
    public function up(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->dropUnique('message_logs_jenis_rule_reminder_id_unique');
        });

        if (in_array(DB::connection()->getDriverName(), ['pgsql', 'sqlite'], true)) {
            DB::statement("CREATE UNIQUE INDEX message_logs_jenis_rule_reminder_id_unique ON message_logs (jenis, rule, reminder_id) WHERE rule <> 'manual'");

            return;
        }

        Schema::table('message_logs', function (Blueprint $table) {
            $table->unique(['jenis', 'rule', 'reminder_id']);
        });
    }

    public function down(): void
    {
        if (in_array(DB::connection()->getDriverName(), ['pgsql', 'sqlite'], true)) {
            DB::statement('DROP INDEX IF EXISTS message_logs_jenis_rule_reminder_id_unique');
        }

        Schema::table('message_logs', function (Blueprint $table) {
            $table->unique(['jenis', 'rule', 'reminder_id']);
        });
    }
};
