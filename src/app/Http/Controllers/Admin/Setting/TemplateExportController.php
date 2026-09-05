<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Support\SheetHelper;
use Illuminate\Http\Request;

class TemplateExportController extends Controller
{
    /**
     * Unduh seluruh data template pesan sebagai Excel (.xlsx) atau CSV.
     */
    public function download(Request $request)
    {
        $format = strtolower($request->query('format', 'xlsx')) === 'csv' ? 'csv' : 'xlsx';

        $headers = [
            'ID',
            'Kode',
            'Kategori',
            'Judul',
            'Channel',
            'Isi Pesan',
            'Deskripsi',
            'Status',
            'Jumlah Dipakai',
            'Terakhir Diperbarui',
        ];

        $templates = MessageTemplate::with('category')
            ->orderBy('id')
            ->get();

        $rows = $templates->map(function ($t) {
            return [
                'ID'                  => $t->id,
                'Kode'                => $t->kode ?? '-',
                'Kategori'            => $t->category?->nama ?? 'Tanpa Kategori',
                'Judul'               => $t->judul,
                'Channel'             => $t->channel,
                'Isi Pesan'           => $t->konten,
                'Deskripsi'           => $t->deskripsi ?? '',
                'Status'              => $t->is_active ? 'Aktif' : 'Nonaktif',
                'Jumlah Dipakai'      => (string) $t->dipakai_count,
                'Terakhir Diperbarui' => $t->updated_at?->format('d/m/Y H:i') ?? '-',
            ];
        })->all();

        $filename = 'template_pesan_' . date('Ymd_His');
        $content = SheetHelper::content($rows, $headers, $format);

        if ($format === 'xlsx') {
            return response($content, 200, [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.xlsx"',
            ]);
        }

        return response($content, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ]);
    }

    /**
     * Unduh template file (format kolom baku) untuk import data.
     */
    public function template(Request $request)
    {
        $format = strtolower($request->query('format', 'xlsx')) === 'csv' ? 'csv' : 'xlsx';

        $headers = ['Kategori', 'Judul', 'Channel', 'Isi Pesan', 'Deskripsi', 'Status'];

        $sample = [
            [
                'Kategori'   => 'Jadwal & Kontrol',
                'Judul'      => 'Pengingat Kontrol Rawat Jalan Contoh',
                'Channel'    => 'WhatsApp',
                'Isi Pesan'  => 'Halo {nama}, jadwal kontrol Anda di {poli} pada {tanggal} pukul {jam}. Salam RS Bhayangkara Bogor.',
                'Deskripsi'  => 'Contoh deskripsi template pengingat kontrol',
                'Status'     => 'Aktif',
            ],
        ];

        $filename = 'template_import_pesan';
        $content = SheetHelper::content($sample, $headers, $format);

        if ($format === 'xlsx') {
            return response($content, 200, [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.xlsx"',
            ]);
        }

        return response($content, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ]);
    }
}
