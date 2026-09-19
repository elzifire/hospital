<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahkan kolom soft delete (deleted_at) ke SEMUA tabel aplikasi
 * yang belum memilikinya. Tabel kerangka kerja (framework) dan tabel
 * join bawaan paket dikecualikan karena tidak relevan untuk soft delete.
 */
return new class extends Migration
{
    /**
     * Tabel yang tidak perlu kolom soft delete: tabel internal Laravel
     * dan tabel pivot permission Spatie (join tanpa primary id sendiri).
     *
     * @var list<string>
     */
    private const KECUALI = [
        'migrations',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'sessions',
        'password_reset_tokens',
        'personal_access_tokens',
        'model_has_permissions',
        'model_has_roles',
        'role_has_permissions',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $target = $this->tabelTanpaSoftDelete();

        foreach ($target as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (Schema::getTables() as $baris) {
            $tabel = $baris['name'];

            if (Schema::hasColumn($tabel, 'deleted_at')) {
                Schema::table($tabel, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }

    /**
     * Nama semua skema tabel aplikasi (yang dikecualikan) yang belum punya deleted_at.
     *
     * @return list<string>
     */
    private function tabelTanpaSoftDelete(): array
    {
        $target = [];

        foreach (Schema::getTables() as $baris) {
            $tabel = $baris['name'];

            if (in_array($tabel, self::KECUALI, true)) {
                continue;
            }

            if (! Schema::hasColumn($tabel, 'deleted_at')) {
                $target[] = $tabel;
            }
        }

        return $target;
    }
};
