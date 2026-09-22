<?php

namespace App\Jobs;

use App\Broadcasting\WhatsApp\WhatsAppSender;
use App\Models\MessageLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Kirim satu pesan WhatsApp dari outbox message_logs. Status diklaim
 * atomik menunggu → mengirim supaya tidak terkirim ganda; kegagalan
 * jaringan melempar exception agar job dicoba ulang (status dikembalikan
 * ke menunggu), sedangkan penolakan API (hasil gagal permanen) langsung
 * menandai pesan gagal.
 */
class KirimPesanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public int $timeout = 60;

    public function __construct(public int $logId) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RateLimited('whatsapp-kirim')];
    }

    public function handle(WhatsAppSender $sender): void
    {
        Log::channel('whatsapp')->info('KirimPesanJob::handle mulai', [
            'log_id' => $this->logId,
            'attempt' => $this->attempts(),
        ]);

        $log = MessageLog::find($this->logId);

        if (! $log || $log->status !== 'menunggu') {
            Log::channel('whatsapp')->warning('KirimPesanJob::handle skip — pesan tidak menunggu', [
                'log_id' => $this->logId,
                'status' => $log?->status,
            ]);

            return;
        }

        $diklaim = MessageLog::query()
            ->whereKey($log->id)
            ->where('status', 'menunggu')
            ->update(['status' => 'mengirim']);

        if ($diklaim === 0) {
            Log::channel('whatsapp')->warning('KirimPesanJob::handle skip — status sudah diklaim', [
                'log_id' => $this->logId,
            ]);

            return;
        }

        $log->refresh();

        Log::channel('whatsapp')->info('KirimPesanJob::handle mengirim', [
            'log_id' => $log->id,
            'to' => $log->penerima_no_hp,
            'driver' => config('whatsapp.driver'),
            'template' => $log->meta_template_name,
            'language' => $log->meta_language,
            'params' => $log->template_params,
        ]);

        try {
            $hasil = $sender->kirim($log);
        } catch (\Throwable $e) {
            MessageLog::query()
                ->whereKey($log->id)
                ->where('status', 'mengirim')
                ->update(['status' => 'menunggu']);

            Log::channel('whatsapp')->error('KirimPesanJob::handle gagal jaringan', [
                'log_id' => $log->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        if ($hasil->ok) {
            $log->update([
                'status' => 'terkirim',
                'sent_at' => now(),
                'provider' => $hasil->provider,
                'provider_message_id' => $hasil->messageId,
                'error' => null,
            ]);

            Log::channel('whatsapp')->info('KirimPesanJob::handle sukses', [
                'log_id' => $log->id,
                'to' => $log->penerima_no_hp,
                'provider' => $hasil->provider,
                'provider_message_id' => $hasil->messageId,
            ]);

            return;
        }

        $log->update([
            'status' => 'gagal',
            'provider' => $hasil->provider,
            'error' => $hasil->error,
        ]);

        Log::channel('whatsapp')->error('KirimPesanJob::handle gagal', [
            'log_id' => $log->id,
            'to' => $log->penerima_no_hp,
            'provider' => $hasil->provider,
            'error' => $hasil->error,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::channel('whatsapp')->error('KirimPesanJob::failed', [
            'log_id' => $this->logId,
            'error' => $e->getMessage(),
        ]);

        MessageLog::query()
            ->whereKey($this->logId)
            ->whereIn('status', ['menunggu', 'mengirim'])
            ->update(['status' => 'gagal', 'error' => 'Pesan tidak terkirim setelah beberapa kali percobaan: '.$e->getMessage()]);
    }
}
