<?php

namespace App\Services;

use App\Models\Reminder;
use Illuminate\Support\Facades\DB;

/**
 * Logika pencatatan kunjungan nyata dari sebuah penjadwalan Digital
 * Reminder — dipakai bersama oleh DigitalReminderController (realisasi
 * lewat halaman edit jadwal) dan KunjunganController (form "Dari Jadwal"
 * di halaman tambah kunjungan).
 */
class PencatatKunjungan
{
    /**
     * Catat kunjungan nyata dari sebuah penjadwalan → status selesai.
     * Poli terjadwal selalu tercatat; poli lain yang dicentang menjadi
     * baris mandiri (tidak terhubung reminder). Tiap poli = satu baris.
     * Home visit tanpa poli: cukup centang poli yang dikunjungi, atau
     * kosongkan → satu baris kunjungan tanpa poli agar tetap tercatat.
     *
     * @return int jumlah baris kunjungan yang tercatat
     */
    public function dariReminder(Reminder $reminder, array $data): int
    {
        $detail = (array) ($data['polis'] ?? []);
        $utama = $reminder->poli_id !== null ? (int) $reminder->poli_id : null;

        // Poli terjadwal diutamakan; poli tercentang lain menyusul.
        $terpilih = array_values(array_unique(array_map('intval', array_merge(
            $utama !== null ? [(string) $utama] : [],
            array_map('strval', $data['poli_pilih'] ?? []),
        ))));

        DB::transaction(function () use ($data, $detail, $utama, $terpilih, $reminder): void {
            if ($terpilih === []) {
                $reminder->kunjungan()->create([
                    'pnpp_id' => $reminder->pnpp_id,
                    'poli_id' => null,
                    'tanggal_kunjungan' => $data['tanggal_kunjungan'],
                    'keluhan' => $data['keluhan'] ?? null,
                    'diagnosa' => $data['diagnosa'] ?? null,
                ]);
            } else {
                // Home visit tanpa poli: baris pertama yang dicentang menjadi
                // realisasi (terhubung reminder, unique); sisanya mandiri.
                $hubungkanPertama = $utama === null;

                foreach ($terpilih as $poliId) {
                    $keluhan = $detail[$poliId]['keluhan'] ?? null;
                    $diagnosa = $detail[$poliId]['diagnosa'] ?? null;

                    if ($utama !== null && $poliId === $utama) {
                        $keluhan = $keluhan ?? ($data['keluhan'] ?? null);
                        $diagnosa = $diagnosa ?? ($data['diagnosa'] ?? null);
                    }

                    // Hanya baris poli terjadwal yang terhubung ke reminder
                    // (reminder_id unik); poli lain menjadi baris mandiri.
                    $baris = [
                        'poli_id' => $poliId,
                        'tanggal_kunjungan' => $data['tanggal_kunjungan'],
                        'keluhan' => $keluhan,
                        'diagnosa' => $diagnosa,
                    ];

                    if ($utama !== null && $poliId === $utama) {
                        $reminder->kunjungan()->create($baris + ['pnpp_id' => $reminder->pnpp_id]);
                    } elseif ($hubungkanPertama) {
                        $reminder->kunjungan()->create($baris + ['pnpp_id' => $reminder->pnpp_id]);
                        $hubungkanPertama = false;
                    } else {
                        $reminder->pnpp->kunjungans()->create($baris);
                    }
                }
            }

            $reminder->update(['status' => 'selesai']);
        });

        return $terpilih === [] ? 1 : count($terpilih);
    }
}
