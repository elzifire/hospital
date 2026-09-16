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

        // Daftar Variabel Dinamis untuk PNPP
        $variables = MessageTemplate::variables();

        return view('admin.setting.index', [
            'templates' => $templates,
            'categories' => $categories,
            'stats' => $stats,
            'variables' => $variables,
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
