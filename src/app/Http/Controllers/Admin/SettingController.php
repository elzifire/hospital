<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Models\TemplateCategory;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Hub utama pengaturan template pesan & kategori.
     * Mendukung server-side searching, filtering, dan pagination.
     */
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'template');
        $search = $request->query('q', '');
        $categoryId = $request->query('category_id', '');
        $channel = $request->query('channel', '');
        $status = $request->query('status', '');

        // Query Template Pesan (Server-side search, filter & pagination)
        $templatesQuery = MessageTemplate::with('category')
            ->search($search)
            ->filterCategory($categoryId)
            ->filterChannel($channel)
            ->filterStatus($status)
            ->orderBy('id', 'desc');

        $templates = $templatesQuery->paginate(6)->withQueryString();

        // Data Kategori untuk dropdown & tab kategori
        $categories = TemplateCategory::withCount('templates')
            ->orderBy('nama')
            ->get();

        // Statistik Cepat
        $stats = [
            'total_template' => MessageTemplate::count(),
            'total_kategori' => TemplateCategory::count(),
            'template_aktif' => MessageTemplate::where('is_active', true)->count(),
            'total_dipakai'  => MessageTemplate::sum('dipakai_count'),
        ];

        // Daftar Variabel Dinamis untuk PNPP
        $variables = [
            ['var' => '{nama}',            'desc' => 'Nama lengkap PNPP',                 'contoh' => 'Bripka Joko Susanto'],
            ['var' => '{nip}',             'desc' => 'NIP / NRP anggota',                 'contoh' => '85031234'],
            ['var' => '{satker}',          'desc' => 'Satuan kerja / Polda / Polres',     'contoh' => 'Polresta Bogor Kota'],
            ['var' => '{poli}',            'desc' => 'Nama poliklinik / instalasi',       'contoh' => 'Poli Penyakit Dalam'],
            ['var' => '{dokter}',          'desc' => 'Nama dokter pemeriksa',             'contoh' => 'dr. Hendra Pratama, Sp.PD'],
            ['var' => '{tanggal}',         'desc' => 'Tanggal jadwal kontrol',            'contoh' => 'Senin, 08 Sep 2026'],
            ['var' => '{jam}',             'desc' => 'Waktu pemeriksaan',                'contoh' => '09:00 WIB'],
            ['var' => '{obat}',            'desc' => 'Nama obat / resep berkala',         'contoh' => 'Amlodipine 10mg'],
            ['var' => '{no_antrian}',      'desc' => 'Nomor tiket antrean',               'contoh' => 'A-024'],
            ['var' => '{link_konfirmasi}', 'desc' => 'Tautan konfirmasi WhatsApp Web',   'contoh' => 'https://rs-bhayangkara.id/c/8f2a'],
        ];

        return view('admin.setting.index', [
            'tab'        => $tab,
            'templates'  => $templates,
            'categories' => $categories,
            'stats'      => $stats,
            'variables'  => $variables,
            'filters'    => [
                'q'           => $search,
                'category_id' => $categoryId,
                'channel'     => $channel,
                'status'      => $status,
            ],
        ]);
    }

    /**
     * Alias route menuju halaman template pesan.
     */
    public function template(Request $request)
    {
        return $this->index($request);
    }
}