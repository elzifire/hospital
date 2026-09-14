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
     *
     * @return int jumlah baris poli yang tercatat
     */
    public function dariReminder(Reminder $reminder, array $data): int
    {
        $detail = (array) ($data['polis'] ?? []);
        $utama = (int) $reminder->poli_id;

        // Poli terjadwal diutamakan; poli tercentang lain menyusul.
        $terpilih = array_values(array_unique(array_merge([(string) $utama], array_map('strval', $data['poli_pilih'] ?? []))));
        $terpilih = array_map('intval', $terpilih);

        DB::transaction(function () use ($data, $detail, $utama, $terpilih, $reminder): void {
            foreach ($terpilih as $poliId) {
                $keluhan = $detail[$poliId]['keluhan'] ?? null;
                $diagnosa = $detail[$poliId]['diagnosa'] ?? null;

                if ($poliId === $utama) {
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

                if ($poliId === $utama) {
                    $reminder->kunjungan()->create($baris + ['pnpp_id' => $reminder->pnpp_id]);
                } else {
                    $reminder->pnpp->kunjungans()->create($baris);
                }
            }

            $reminder->update(['status' => 'selesai']);
        });

        return count($terpilih);
    }
}
