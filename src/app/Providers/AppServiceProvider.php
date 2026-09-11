<?php

namespace App\Providers;

use App\Broadcasting\WhatsApp\LogSender;
use App\Broadcasting\WhatsApp\MetaSender;
use App\Broadcasting\WhatsApp\WhatsAppSender;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Driver pengirim WhatsApp dipilih dari config saat di-resolve:
        // meta = WhatsApp official, selain itu simulasi log (dev/test).
        $this->app->bind(WhatsAppSender::class, fn ($app) => config('whatsapp.driver') === 'meta'
            ? $app->make(MetaSender::class)
            : $app->make(LogSender::class));

        // Pacing pengiriman di queue (job KirimPesanJob): jeda antar
        // pesan + jatah per jam/hari dari config whatsapp.rate_limit.
        RateLimiter::for('whatsapp-kirim', function (object $job) {
            $jeda = max(1, (int) config('whatsapp.rate_limit.jeda_kirim', 30));

            return [
                Limit::perMinute(max(1, (int) floor(60 / $jeda)))->by('whatsapp-jeda'),
                Limit::perHour(max(1, (int) config('whatsapp.rate_limit.max_per_jam', 20)))->by('whatsapp-jam'),
                Limit::perDay(max(1, (int) config('whatsapp.rate_limit.max_per_hari', 200)))->by('whatsapp-hari'),
            ];
        });
    }
}
