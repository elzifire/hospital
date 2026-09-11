<?php

namespace App\Broadcasting\WhatsApp;

use App\Models\MessageLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Pengirim simulasi untuk dev/test (driver "log") — payload ditulis ke
 * log laravel dan pesan langsung dianggap terkirim.
 */
class LogSender implements WhatsAppSender
{
    public function kirim(MessageLog $log): HasilKirim
    {
        Log::info('Pesan WhatsApp (driver log)', [
            'to' => $log->penerima_no_hp,
            'template' => $log->meta_template_name,
            'language' => $log->meta_language,
            'params' => $log->template_params,
            'konten' => $log->konten,
        ]);

        return HasilKirim::sukses('log', 'log-'.Str::lower(Str::random(16)));
    }
}
