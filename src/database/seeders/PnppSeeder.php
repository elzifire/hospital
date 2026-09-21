<?php

namespace Database\Seeders;

use App\Models\Dokter;
use App\Models\Pnpp;
use App\Models\Poli;
use App\Models\Reminder;
use App\Models\Satker;
use Illuminate\Database\Seeder;

/**
 * Seed opsional: 1000 data PNPP fake via PnppFactory.
 *
 * Dijalankan eksplisit, tidak masuk DatabaseSeeder:
 *   php artisan db:seed --class=PnppSeeder
 *
 * Idempotent per NIP/no_bpjs (skip yang sudah ada).
 */
class PnppSeeder extends Seeder
{
    public function run(): void
    {
        $satkers = [
            ['kode' => 'DINKES', 'nama' => 'Dinas Kesehatan'],
            ['kode' => 'BPJS', 'nama' => 'BPJS Kesehatan'],
            ['kode' => 'KEMKES', 'nama' => 'Kementerian Kesehatan'],
            ['kode' => 'DINDIK', 'nama' => 'Dinas Pendidikan'],
        ];
        foreach ($satkers as $data) {
            Satker::firstOrCreate(['kode' => $data['kode']], $data);
        }

        Pnpp::factory()->count(1000)->create();

        // Beberapa poli & dokter agar reminder (dan form outreach) tersedia.
        $polis = [
            ['kode' => 'UMUM', 'nama' => 'Poli Umum'],
            ['kode' => 'GIGI', 'nama' => 'Poli Gigi'],
            ['kode' => 'PDLM', 'nama' => 'Poli Penyakit Dalam'],
            ['kode' => 'ANAK', 'nama' => 'Poli Anak'],
        ];
        $dokters = [
            'dr. Rina Pratiwi' => 'Dokter Umum',
            'drg. Andi Saputra' => 'Dokter Gigi',
            'dr. Bambang Haryanto' => 'Spesialis Penyakit Dalam',
            'dr. Sari Wulandari' => 'Spesialis Anak',
        ];

        $poliUmum = Poli::firstOrCreate(['kode' => 'UMUM'], $polis[0]);
        $poliGigi = Poli::firstOrCreate(['kode' => 'GIGI'], $polis[1]);
        $poliDalam = Poli::firstOrCreate(['kode' => 'PDLM'], $polis[2]);
        $poliAnak = Poli::firstOrCreate(['kode' => 'ANAK'], $polis[3]);

        $dokterBase = [
            $poliUmum?->id => 'dr. Rina Pratiwi',
            $poliGigi?->id => 'drg. Andi Saputra',
            $poliDalam?->id => 'dr. Bambang Haryanto',
            $poliAnak?->id => 'dr. Sari Wulandari',
        ];
        $poliIds = array_keys($dokterBase);
        foreach ($dokterBase as $poliId => $nama) {
            Dokter::firstOrCreate(['nama' => $nama], [
                'poli_id' => $poliId,
                'spesialisasi' => $dokters[$nama],
            ]);
        }

        // Reminder terjadwal untuk sebagian PNPP agar form outreach terisi.
        $pnpps = Pnpp::query()
            ->where('status_aktif', 'aktif')
            ->whereNotNull('no_hp')
            ->inRandomOrder()
            ->limit(600)
            ->get();

        foreach ($pnpps as $pnpp) {
            if ($pnpp->reminders()->where('status', 'terjadwal')->exists()) {
                continue;
            }
            $poliId = $poliIds[array_rand($poliIds)];
            $dokterId = Dokter::where('poli_id', $poliId)->value('id');
            Reminder::create([
                'pnpp_id' => $pnpp->id,
                'poli_id' => $poliId,
                'dokter_id' => $dokterId,
                'tanggal' => now()->addDays(random_int(0, 30))->toDateString(),
                'jam' => sprintf('%02d:%02d', random_int(8, 16), random_int(0, 59)),
                'status' => 'terjadwal',
                'home_visit' => (bool) random_int(0, 1),
            ]);
        }
    }
}