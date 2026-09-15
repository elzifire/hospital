<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Broadcasting\WhatsApp\MetaTemplateSync;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Sinkronisasi template pesan dari Meta WhatsApp (tombol di halaman
 * Pengaturan → Template, dan lewat command artisan meta-templates:sync).
 */
class TemplateMetaSyncController extends Controller
{
    public function sync(Request $request, MetaTemplateSync $sync)
    {
        $mulai = microtime(true);

        Log::channel('whatsapp')->info('Sinkronisasi template Meta dimulai.', [
            'oleh' => $request->user()?->name ?? 'artisan',
            'via' => $request->input('_source', 'web'),
        ]);

        $hasil = $sync->sync();

        Log::channel('whatsapp')->info('Sinkronisasi template Meta selesai.', [
            'jumlah' => $hasil['jumlah'],
            'dibuat' => $hasil['dibuat'],
            'diperbarui' => $hasil['diperbarui'],
            'error' => $hasil['error'],
            'durasi_detik' => round(microtime(true) - $mulai, 3),
        ]);

        if ($hasil['error'] !== null) {
            return redirect()
                ->route('admin.setting.index')
                ->with('error', 'Sinkronisasi Meta gagal: '.$hasil['error']);
        }

        return redirect()
            ->route('admin.setting.index')
            ->with(
                'success',
                "Sinkronisasi selesai: {$hasil['jumlah']} template dari Meta"
                ." (baru: {$hasil['dibuat']}, diperbarui: {$hasil['diperbarui']})."
            );
    }
}
