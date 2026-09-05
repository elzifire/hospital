<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Models\Pnpp;
use App\Models\Poli;
use App\Models\Satker;

class DigitalReminderController extends Controller
{
    /**
     * Halaman Digital Reminder (referensi UI — belum ada logic).
     */
    public function index()
    {
        return view('admin.digital-reminder.index');
    }

    /**
     * Halaman Template Pesan (dipindahkan ke modul setting).
     */
    public function template()
    {
        return redirect()->route('admin.setting.index');
    }

    /**
     * Halaman tambah reminder.
     * Alur: pilih satker -> pilih database PNPP atau grouping -> jadwalkan ke satu/beberapa poli.
     */
    public function create()
    {
        return view('admin.digital-reminder.create', $this->masterData());
    }

    /**
     * Halaman edit reminder.
     */
    public function edit()
    {
        return view('admin.digital-reminder.edit', $this->masterData());
    }

    /**
     * Halaman import reminder (referensi UI — belum ada logic).
     */
    public function import()
    {
        return view('admin.digital-reminder.import');
    }

    /**
     * Data master untuk form Digital Reminder (read-only, tanpa insert/update).
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

        $polis = Poli::orderBy('nama')->get(['id', 'nama']);

        $templates = MessageTemplate::query()
            ->where('channel', 'WhatsApp')
            ->where('is_active', true)
            ->orderBy('judul')
            ->get(['id', 'judul', 'konten']);

        return compact('satkers', 'pnpps', 'polis', 'templates');
    }
}
