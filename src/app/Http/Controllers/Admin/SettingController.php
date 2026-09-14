<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BroadcastRule;
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
            'total_dipakai' => MessageTemplate::sum('dipakai_count'),
        ];

        // Aturan generate pesan (tab Aturan Pesan) + kandidat template aktif
        $aturan = BroadcastRule::with('template:id,judul')
            ->orderByRaw("jenis = 'follow_up'")
            ->orderByRaw("array_position(ARRAY['h-7','h-1','h','tidak_datang'], rule)")
            ->get();

        $templateAktif = MessageTemplate::where('is_active', true)
            ->orderBy('judul')
            ->get(['id', 'judul']);

        // Daftar Variabel Dinamis untuk PNPP
        $variables = MessageTemplate::variables();

        return view('admin.setting.index', [
            'tab' => $tab,
            'templates' => $templates,
            'categories' => $categories,
            'stats' => $stats,
            'variables' => $variables,
            'aturan' => $aturan,
            'templateAktif' => $templateAktif,
            'filters' => [
                'q' => $search,
                'category_id' => $categoryId,
                'channel' => $channel,
                'status' => $status,
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
