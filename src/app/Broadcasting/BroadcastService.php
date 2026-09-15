<?php

namespace App\Broadcasting;

use App\Models\BroadcastRule;
use App\Models\MessageLog;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Orkestrator pesan broadcast berbasis penjadwalan (reminders):
 *  - generate(jenis) → buat message_logs untuk reminder yang jatuh ke
 *    rule aktif jenis tsb. (saat ini hanya outreach: H-7 & H-1; rule
 *    follow up H-1/hari-H/tidak-datang dinonaktifkan sementara) memakai
 *    template yang dipilih per rule (Setting);
 *    jadwal yang punya template sendiri (form Digital Reminder) memakai
 *    template itu sebagai pengganti (override) default rule.
 *  - sweepStatus() → tandai reminder lewat tanpa kunjungan sebagai
 *    tidak_datang (bahan rule follow up no-show).
 *  - batalkan() → pesan yang belum terkirim.
 *
 * Pesan hasil generate berstatus "menunggu" lalu dikirim oleh worker
 * queue (job KirimPesanJob, diantrekan command broadcast:kirim tiap
 * menit) atau tombol "Kirim Sekarang" secara sinkron.
 */
class BroadcastService
{
    public function __construct(protected PesanFactory $pesan) {}

    /**
     * Buat pesan untuk seluruh reminder yang cocok dengan rule aktif jenis tsb.
     *
     * @param  string  $jenis  outreach | follow_up
     * @param  int|null  $poliId  batasi ke satu poli (null = semua poli)
     * @return array{dibuat: int, dilewati: int}
     */
    public function generate(string $jenis, ?User $oleh = null, ?int $poliId = null): array
    {
        $dibuat = 0;
        $dilewati = 0;

        foreach ($this->aturanAktif($jenis) as $aturan) {
            foreach ($this->reminderUntukRule($aturan->rule, $poliId) as $reminder) {
                if ($this->sudahDibuat($jenis, $aturan->rule, $reminder->id)) {
                    $dilewati++;

                    continue;
                }

                $this->buatPesan($aturan, $reminder, $oleh);
                $dibuat++;
            }
        }

        return ['dibuat' => $dibuat, 'dilewati' => $dilewati];
    }

    /**
     * Tandai reminder terjadwal yang tanggalnya sudah lewat tanpa
     * kunjungan sebagai tidak datang. Aman dipanggil berulang.
     *
     * @param  int|null  $poliId  batasi ke satu poli (null = semua poli)
     */
    public function sweepStatus(?int $poliId = null): int
    {
        return Reminder::query()
            ->terlambatTanpaKunjungan()
            ->when($poliId !== null, fn ($query) => $query->where('poli_id', $poliId))
            ->update(['status' => 'tidak_datang']);
    }

    /**
     * Batalkan pesan yang masih bisa dibatalkan (menunggu).
     *
     * @param  Collection<int>|array  $ids
     */
    public function batalkan(Collection|array $ids): int
    {
        return MessageLog::query()
            ->whereIn('id', $ids)
            ->where('status', 'menunggu')
            ->update(['status' => 'dibatalkan']);
    }

    /**
     * Rule aktif jenis tsb. yang sudah punya template — tanpa template
     * sebuah rule dilewati (atur lewat tab Aturan Pesan di Setting).
     *
     * @return Collection<BroadcastRule>
     */
    protected function aturanAktif(string $jenis): Collection
    {
        return BroadcastRule::query()
            ->jenis($jenis)
            ->where('is_active', true)
            ->whereNotNull('message_template_id')
            ->with('template')
            ->get();
    }

    /**
     * Reminder yang jatuh ke sebuah rule: H-N → jadwal tepat N hari ke
     * depan & masih terjadwal; tidak_datang → sudah ditandai tidak datang.
     *
     * @return Collection<Reminder>
     */
    protected function reminderUntukRule(string $rule, ?int $poliId = null): Collection
    {
        return Reminder::query()
            ->with('pnpp.satker', 'poli', 'dokter', 'messageTemplate')
            ->when($poliId !== null, fn ($query) => $query->where('poli_id', $poliId))
            ->when(
                $rule === 'tidak_datang',
                fn ($query) => $query->where('status', 'tidak_datang'),
                fn ($query) => $query->where('status', 'terjadwal')
                    ->when($rule === 'h-7', fn ($q) => $q->dueIn(7))
                    ->when($rule === 'h-1', fn ($q) => $q->dueIn(1))
                    ->when($rule === 'h', fn ($q) => $q->hariIni()),
            )
            ->get();
    }

    protected function sudahDibuat(string $jenis, string $rule, int $reminderId): bool
    {
        return MessageLog::query()
            ->where('jenis', $jenis)
            ->where('rule', $rule)
            ->where('reminder_id', $reminderId)
            ->exists();
    }

    protected function buatPesan(BroadcastRule $aturan, Reminder $reminder, ?User $oleh): MessageLog
    {
        // Template level penjadwalan (dipilih di form) menang atas
        // template default rule — jatuh ke default saat tidak dipilih.
        $template = $reminder->messageTemplate ?? $aturan->template;

        return MessageLog::create(
            $this->pesan->atribut($template, $reminder->pnpp, $reminder, $aturan->jenis, $aturan->rule, $oleh),
        );
    }
}
