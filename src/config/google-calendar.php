<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Google Calendar (sinkronisasi hari libur)
|--------------------------------------------------------------------------
|
| Menarik (pull) event hari libur dari satu kalender Google publik ke tabel
| hari_liburs. Kalender dibaca READ-ONLY lewat API key (tanpa OAuth/service
| account) — karenanya kalender sumber harus diset menjadi "publik" (lihat
| dan temukan detail event) dan ID-nya berupa alamat e-mail kalender,
| mis. "id.indonesia#holiday@group.v.calendar.google.com".
|
*/

return [

    'base_url' => env('GOOGLE_CALENDAR_BASE_URL', 'https://www.googleapis.com/calendar/v3'),

    // API key aplikasi Google (dibuat di Google Cloud Console, paket
    // "Google Calendar API" diaktifkan). Hanya untuk baca kalender publik.
    'api_key' => env('GOOGLE_CALENDAR_API_KEY'),

    // ID kalender sumber (alamat e-mail kalender), mis. kalender hari libur.
    'calendar_id' => env('GOOGLE_CALENDAR_ID'),

    // Rentang waktu event yang ditarik (dari hari ini ke depan). Event lama
    // hasil sinkron yang tidak muncul lagi otomatis dihapus.
    'lookahead_months' => (int) env('GOOGLE_CALENDAR_LOOKAHEAD_MONTHS', 12),

    'timeout' => (int) env('GOOGLE_CALENDAR_TIMEOUT', 15),

];