<?php

namespace App\Broadcasting\WhatsApp;

use App\Models\MessageLog;

/**
 * Kontrak pengirim pesan WhatsApp — implementasi dipilih lewat config
 * whatsapp.driver (meta = WhatsApp official, selain itu simulasi log).
 */
interface WhatsAppSender
{
    public function kirim(MessageLog $log): HasilKirim;
}
