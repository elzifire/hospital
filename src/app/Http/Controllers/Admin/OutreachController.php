<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Models\Pnpp;
use App\Models\Satker;

class OutreachController extends Controller
{
    /**
     * Halaman Outreach — data pesan yang telah dikirim ke pasien
     * melalui nomor WhatsApp (status: terkirim / menunggu dikirim).
     */
    public function index()
    {
        return view('admin.outreach.index');
    }

    /**
     * Halaman kirim pesan outreach baru (referensi UI — belum ada logic).
     */
    public function create()
    {
        return view('admin.outreach.create', $this->masterData());
    }

    /**
     * Halaman edit pesan outreach (referensi UI — belum ada logic).
     */
    public function edit()
    {
        return view('admin.outreach.edit', $this->masterData());
    }

    /**
     * Halaman import daftar kirim outreach (referensi UI — belum ada logic).
     */
    public function import()
    {
        return view('admin.outreach.import');
    }

    /**
     * Data master untuk form Outreach (read-only, tanpa insert/update).
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
