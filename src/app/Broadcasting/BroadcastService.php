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
 *    rule aktif jenis tsb. (outreach: H-7 & H-1; follow up: H-1, hari-H,
 *    dan tidak-datang) memakai template yang dipilih per rule (Setting).
 *  - sweepStatus() → tandai reminder lewat tanpa kunjungan sebagai
 *    tidak_datang (bahan rule follow up no-show).
 *  - batalkan() → pesan yang belum terkirim.
 *
 * Pengiriman nyata ke WhatsApp sengaja belum aktif (menunggu pihak
 * ketiga): pesan hasil generate berstatus "menunggu" dan siap dikirim
 * saat infrastruktur kirim dibangun kembali.
 */
class BroadcastService
{
    public function __construct(protected MessageRenderer $renderer) {}

    /**
     * Buat pesan untuk seluruh reminder yang cocok dengan rule aktif jenis tsb.
     *
     * @param  string  $jenis  outreach | follow_up
     * @return array{dibuat: int, dilewati: int}
     */
    public function generate(string $jenis, ?User $oleh = null): array
    {
        $dibuat = 0;
        $dilewati = 0;

        foreach ($this->aturanAktif($jenis) as $aturan) {
            foreach ($this->reminderUntukRule($aturan->rule) as $reminder) {
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
     */
    public function sweepStatus(): int
    {
        return Reminder::query()->terlambatTanpaKunjungan()->update(['status' => 'tidak_datang']);
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
    protected function reminderUntukRule(string $rule): Collection
    {
        return Reminder::query()
            ->with('pnpp.satker', 'poli', 'dokter')
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
        $pnpp = $reminder->pnpp;
        $noHp = PhoneFormat::toWa($pnpp?->no_hp);

        return MessageLog::create([
            'jenis' => $aturan->jenis,
            'rule' => $aturan->rule,
            'reminder_id' => $reminder->id,
            'message_template_id' => $aturan->message_template_id,
            'pnpp_id' => $pnpp?->id,
            'created_by' => $oleh?->id,
            'penerima_nama' => (string) ($pnpp?->nama ?? '—'),
            'penerima_no_hp' => $noHp ?? (string) ($pnpp?->no_hp ?? ''),
            'konten' => $this->renderer->render(
                (string) $aturan->template?->konten,
                $pnpp,
                $this->meta($reminder),
            ),
            'status' => $noHp === null ? 'gagal' : 'menunggu',
            'error' => $noHp === null ? 'Pasien tidak memiliki nomor WhatsApp yang valid.' : null,
        ]);
    }

    /**
     * Konteks render ({poli} {dokter} {tanggal} {jam}) derivasi dari
     * penjadwalan — bukan lagi meta bebas dari form.
     *
     * @return array<string, ?string>
     */
    protected function meta(Reminder $reminder): array
    {
        return [
            'poli' => $reminder->poli?->nama,
            'dokter' => $reminder->dokter?->nama,
            'tanggal' => $reminder->tanggal?->format('Y-m-d'),
            'jam' => $reminder->jam?->format('H:i'),
        ];
    }
}
