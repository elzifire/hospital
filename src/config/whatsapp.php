<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi WhatsApp (Meta WhatsApp Cloud API — WhatsApp Official)
|--------------------------------------------------------------------------
|
| Pengiriman pesan outreach/follow up berjalan lewat driver yang dipilih
| env WA_DRIVER: "meta" (WhatsApp official — graph.facebook.com) atau
| "log" (simulasi ke logfile untuk dev/test).
|
| Format pengiriman mengikuti contoh curl resmi Meta:
|   POST {base_url}/{version}/{phone_number_id}/messages
|   { "messaging_product": "whatsapp", "to": "...", "type": "template",
|     "template": { "name": "...", "language": { "code": "..." } } }
|
*/

return [

    'driver' => env('WA_DRIVER', 'log'),

    'meta' => [
        'base_url' => env('WA_META_BASE_URL', 'https://graph.facebook.com'),
        'version' => env('WA_META_VERSION', 'v25.0'),
        'business_account_id' => env('WA_META_BUSINESS_ACCOUNT_ID'),
        'phone_number_id' => env('WA_META_PHONE_NUMBER_ID'),
        'token' => env('WA_META_TOKEN'),
        'timeout' => (int) env('WA_META_TIMEOUT', 15),
        'default_language' => env('WA_META_DEFAULT_LANGUAGE', 'en_US'),
        'fallback_template' => env('WA_META_FALLBACK_TEMPLATE'),
        'fallback_language' => env('WA_META_FALLBACK_LANGUAGE', 'en_US'),
    ],

    'rate_limit' => [
        'max_per_jam' => (int) env('WA_MAX_PER_JAM', 20),
        'max_per_hari' => (int) env('WA_MAX_PER_HARI', 200),
        'jeda_kirim' => (int) env('WA_JEDA_KIRIM', 30),
    ],

    'webhook' => [
        'verify_token' => env('WA_WEBHOOK_VERIFY_TOKEN'),
        'app_secret' => env('WA_APP_SECRET'),
    ],

];
