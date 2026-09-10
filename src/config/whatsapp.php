<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Webhook WhatsApp
|--------------------------------------------------------------------------
|
| Endpoint /webhook/whatsapp menerima balasan pasien (modul Respon)
| yang dikirim WAHA dari luar.
|
| Pengiriman pesan ke WhatsApp sementara tidak aktif (menunggu pihak
| ketiga): pesan outreach/follow up hanya digenerate ke message_logs
| (status "menunggu") oleh BroadcastService. Infrastruktur kirim akan
| dibangun kembali memakai tabel & kolom yang sudah disiapkan.
|
*/

return [

    'webhook' => [
        'verify_token' => env('WA_WEBHOOK_VERIFY_TOKEN'),
        'secret' => env('WA_WEBHOOK_SECRET'),
    ],

];
