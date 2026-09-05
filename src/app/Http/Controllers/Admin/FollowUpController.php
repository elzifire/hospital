<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Models\Pnpp;
use App\Models\Satker;

class FollowUpController extends Controller
{
    /**
     * Halaman Follow Up (referensi UI — belum ada logic).
     */
    public function index()
    {
        return view('admin.follow-up.index');
    }

    /**
     * Halaman tambah follow up.
     * Alur: pilih satker -> pilih PNPP -> atur pesan & jadwal kirim ulang.
     */
    public function create()
    {
        return view('admin.follow-up.create', $this->masterData());
    }

    /**
     * Halaman edit follow up.
     */
    public function edit()
    {
        return view('admin.follow-up.edit', $this->masterData());
    }

    /**
     * Halaman import follow up (referensi UI — belum ada logic).
     */
    public function import()
    {
        return view('admin.follow-up.import');
    }

    /**
     * Data master untuk form Follow Up (read-only, tanpa insert/update).
     */
    private function masterData(): array
    {
        $satkers = Satker::orderBy('nama')->get(['id', 'kode', 'nama']);

        $pnpps = Pnpp::orderBy('nama')
            ->get()
            ->map(fn ($p) => [
                'id'       => $p->id,
                'nama'     => $p->nama,
                'nip'      => $p->nip,
                'noHp'     => $p->no_hp,
                'satkerId' => $p->satker_id,
            ]);

        $templates = MessageTemplate::query()
            ->where('channel', 'WhatsApp')
            ->where('is_active', true)
            ->orderBy('judul')
            ->get(['id', 'judul', 'konten']);

        return compact('satkers', 'pnpps', 'templates');
    }
}
