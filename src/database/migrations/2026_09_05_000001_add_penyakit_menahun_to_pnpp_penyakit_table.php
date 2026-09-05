<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pnpp_penyakit', function (Blueprint $table) {
            $table->dropPrimary();
            $table->unsignedBigInteger('penyakit_kronis_id')->nullable()->change();
            $table->foreignId('penyakit_menahun_id')->nullable()->after('penyakit_kronis_id')->constrained('penyakit_menahuns')->cascadeOnDelete();
            $table->bigIncrements('id');
            $table->unique(['pnpp_id', 'penyakit_kronis_id'], 'pnpp_penyakit_kronis_unique');
            $table->unique(['pnpp_id', 'penyakit_menahun_id'], 'pnpp_penyakit_menahun_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pnpp_penyakit', function (Blueprint $table) {
            $table->dropUnique('pnpp_penyakit_kronis_unique');
            $table->dropUnique('pnpp_penyakit_menahun_unique');
            $table->dropForeign(['penyakit_menahun_id']);
            $table->dropColumn(['penyakit_menahun_id', 'id']);
            $table->unsignedBigInteger('penyakit_kronis_id')->nullable(false)->change();
            $table->primary(['pnpp_id', 'penyakit_kronis_id']);
        });
    }
};