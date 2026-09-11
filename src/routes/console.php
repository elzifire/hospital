<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Alur kirim WhatsApp dari penjadwalan: generate pesan tiap pagi,
// lalu antrekan pesan menunggu tiap menit — diproses worker queue
// dengan rate limit (jalankan `php artisan queue:work`).
Schedule::command('broadcast:generate')->dailyAt('07:00')->withoutOverlapping();
Schedule::command('broadcast:kirim')->everyMinute()->withoutOverlapping();
