<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Broadcasting\WhatsApp\MetaTemplateSync;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Sinkronisasi template pesan dari Meta WhatsApp (tombol di halaman
 * Pengaturan → Template, dan lewat command artisan meta-templates:sync).
 */
class TemplateMetaSyncController extends Controller
{
    public function sync(Request $request, MetaTemplateSync $sync)
    {
        $hasil = $sync->sync();

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
