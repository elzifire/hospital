<?php

namespace App\Broadcasting;

use App\Models\MessageTemplate;
use App\Models\Pnpp;
use App\Models\Reminder;
use App\Models\User;

/**
 * Pabrik atribut message_logs untuk satu penerima — dipakai generate
 * otomatis (BroadcastService) dan form kirim manual. Selain konten
 * ter-render, snapshot pemetaan template Meta (nama & bahasa) plus
 * parameter positional ({nama} → {{1}}, dst.) ikut disimpan supaya
 * pengiriman tidak bergantung template yang berubah setelah pesan
 * dibuat.
 */
class PesanFactory
{
    public function __construct(protected MessageRenderer $renderer) {}

    /**
     * @return array<string, mixed>
     */
    public function atribut(
        MessageTemplate $template,
        Pnpp $pnpp,
        ?Reminder $reminder,
        string $jenis,
        string $rule,
        ?User $oleh = null,
    ): array {
        $noHp = PhoneFormat::toWa($pnpp->no_hp);
        $meta = $this->meta($reminder);

        return [
            'jenis' => $jenis,
            'rule' => $rule,
            'reminder_id' => $reminder?->id,
            'message_template_id' => $template->id,
            'pnpp_id' => $pnpp->id,
            'created_by' => $oleh?->id,
            'penerima_nama' => (string) ($pnpp->nama ?? '—'),
            'penerima_no_hp' => $noHp ?? (string) ($pnpp->no_hp ?? ''),
            'konten' => $this->renderer->render((string) $template->konten, $pnpp, $meta),
            'status' => $noHp === null ? 'gagal' : 'menunggu',
            'error' => $noHp === null ? 'Pasien tidak memiliki nomor WhatsApp yang valid.' : null,
            'provider' => (string) config('whatsapp.driver'),
            'meta_template_name' => $this->namaTemplateMeta($template),
            'meta_language' => $this->bahasaTemplateMeta($template),
            'template_params' => $this->params($template, $pnpp, $meta),
        ];
    }

    /**
     * Konteks render ({poli} {dokter} {tanggal} {jam}) derivasi dari
     * penjadwalan yang dikaitkan — data target (nama/nip) dari PNPP.
     *
     * @return array<string, ?string>
     */
    protected function meta(?Reminder $reminder): array
    {
        if ($reminder === null) {
            return [];
        }

        return [
            'poli' => $reminder->poli?->nama,
            'dokter' => $reminder->dokter?->nama,
            'tanggal' => $reminder->tanggal?->format('Y-m-d'),
            'jam' => $reminder->jam?->format('H:i'),
        ];
    }

    /**
     * Nama template Meta: pemetaan eksplisit per template, selain itu
     * template fallback dari config (belum ada template produksi
     * yang approved).
     */
    protected function namaTemplateMeta(MessageTemplate $template): string
    {
        return (string) ($template->meta_template_name ?: config('whatsapp.meta.fallback_template'));
    }

    protected function bahasaTemplateMeta(MessageTemplate $template): string
    {
        if ($template->meta_template_name) {
            return (string) ($template->meta_language ?: config('whatsapp.meta.default_language', 'id'));
        }

        return (string) config('whatsapp.meta.fallback_language', 'en_US');
    }

    /**
     * Parameter positional template Meta — nilai tiap token dirender
     * dengan konteks yang sama seperti konten (termasuk format tanggal
     * Indonesia) sehingga pratinjau selalu sama dengan yang terkirim.
     *
     * @return array<int, string>
     */
    protected function params(MessageTemplate $template, Pnpp $pnpp, array $meta): array
    {
        return array_map(
            fn (string $token) => $this->renderer->render('{'.$token.'}', $pnpp, $meta),
            $template->tokenParam(),
        );
    }
}
