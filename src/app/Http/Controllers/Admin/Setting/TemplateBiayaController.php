<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Models\MessageLog;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateBiayaController extends Controller
{
    /**
     * Harga satuan (IDR) per kategori template Meta WhatsApp.
     *
     * Untuk sementara di-hardcode di controller sebagai estimasi tarif;
     * nantinya bisa dipindah ke konfigurasi pengaturan. Harga kategori
     * yang belum terdaftar dianggap Rp 0.
     */
    private const HARGA_SATUAN = [
        'MARKETING' => 650,
        'UTILITY' => 548,
        'SERVICE' => 548,
        'AUTHENTICATION' => 350,
        'OTP' => 350,
    ];

    private const HARGA_DEFAULT = 0;

    /**
     * Estimasi biaya template pesan: jumlah pesan terpakai dihitung dari
     * database (message_logs berstatus "terkirim" per template) dikali
     * harga satuan kategori template.
     */
    public function index(Request $request): View
    {
        $terpakai = MessageLog::query()
            ->where('status', 'terkirim')
            ->whereNotNull('message_template_id')
            ->selectRaw('message_template_id, count(*) as total')
            ->groupBy('message_template_id')
            ->pluck('total', 'message_template_id');

        $templates = MessageTemplate::with('category')->orderBy('judul')->get();

        $baris = $templates
            ->map(fn (MessageTemplate $template) => [
                'template' => $template,
                'terpakai' => (int) ($terpakai[$template->id] ?? 0),
                'harga' => $this->harga((string) $template->meta_category),
            ])
            ->map(fn (array $b) => $b + ['subtotal' => $b['terpakai'] * $b['harga']])
            ->sortByDesc('terpakai')
            ->values();

        $perKategori = $baris
            ->groupBy(fn (array $b) => (string) $b['template']->meta_category)
            ->map(fn ($items, string $kategori) => [
                'kategori' => $kategori === '' ? 'Tanpa Kategori' : $kategori,
                'terpakai' => $items->sum('terpakai'),
                'harga' => $this->harga($kategori),
            ])
            ->map(fn (array $g) => $g + ['subtotal' => $g['terpakai'] * $g['harga']])
            ->sortByDesc('terpakai')
            ->values();

        return view('admin.setting.biaya', [
            'baris' => $baris,
            'perKategori' => $perKategori,
            'totalTemplate' => $templates->count(),
            'templateTerpakai' => $baris->filter(fn (array $b) => $b['terpakai'] > 0)->count(),
            'totalPesan' => $baris->sum('terpakai'),
            'totalBiaya' => $baris->sum('subtotal'),
            'tarif' => self::HARGA_SATUAN,
        ]);
    }

    private function harga(string $kategori): int
    {
        return self::HARGA_SATUAN[$kategori] ?? self::HARGA_DEFAULT;
    }
}
