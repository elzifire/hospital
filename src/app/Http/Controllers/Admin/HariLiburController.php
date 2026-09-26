<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Models\Poli;
use App\Support\GoogleCalendarSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HariLiburController extends Controller
{
    public function index(): View
    {
        $hariLiburs = HariLibur::with('poli')->orderByDesc('tanggal')->get();
        $polis = Poli::orderBy('nama')->get();

        $googleTerpasang = config('google-calendar.api_key') !== null && config('google-calendar.calendar_id') !== null;

        return view('admin.hari-libur.index', compact('hariLiburs', 'polis', 'googleTerpasang'));
    }

    public function create(): View
    {
        return view('admin.hari-libur.create', ['polis' => Poli::orderBy('nama')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->aturanValidasi($request));

        $hariLibur = DB::transaction(fn () => HariLibur::create($data + ['sumber' => HariLibur::SUMBER_MANUAL]));

        return redirect()->route('admin.hari-libur.index')
            ->with('success', "Hari libur \"{$hariLibur->nama}\" (".$hariLibur->tanggal->format('d-m-Y').') berhasil ditambahkan.');
    }

    public function edit(HariLibur $hariLibur): View
    {
        return view('admin.hari-libur.edit', [
            'hariLibur' => $hariLibur,
            'polis' => Poli::orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, HariLibur $hariLibur): RedirectResponse
    {
        $data = $request->validate($this->aturanValidasi($request, $hariLibur));

        DB::transaction(fn () => $hariLibur->update($data));

        return redirect()->route('admin.hari-libur.index')
            ->with('success', "Hari libur \"{$hariLibur->nama}\" (".$hariLibur->tanggal->format('d-m-Y').') berhasil diperbarui.');
    }

    public function destroy(HariLibur $hariLibur): RedirectResponse
    {
        $nama = $hariLibur->nama;
        $tanggal = $hariLibur->tanggal->format('d-m-Y');
        $hariLibur->delete();

        return redirect()->route('admin.hari-libur.index')
            ->with('success', "Hari libur \"{$nama}\" ({$tanggal}) berhasil dihapus.");
    }

    /**
     * Tarik event hari libur dari Google Calendar (read-only) ke tabel.
     */
    public function sync(GoogleCalendarSync $sync): RedirectResponse
    {
        $hasil = $sync->sync();

        if ($hasil['error'] !== null) {
            return back()->with('error', 'Sinkronisasi Google Calendar gagal: '.$hasil['error']);
        }

        return back()->with(
            'success',
            "Sinkronisasi Google Calendar selesai: {$hasil['jumlah']} hari libur"
            ." (baru: {$hasil['dibuat']}, diperbarui: {$hasil['diperbarui']}, dihapus: {$hasil['dihapus']}).",
        );
    }

    /**
     * Aturan validasi manual; tidak ada kolom sumber/event_id yang bisa
     * diubah lewat form (sumber selalu manual saat dibuat di sini).
     */
    protected function aturanValidasi(Request $request, ?HariLibur $hariLibur = null): array
    {
        return [
            'tanggal' => [
                'required',
                'date',
                Rule::unique('hari_liburs', 'tanggal')
                    ->where(fn ($q) => $q
                        ->where('nama', $request->input('nama'))
                        ->where('poli_id', $request->input('poli_id')))
                    ->ignore($hariLibur?->id),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'poli_id' => ['nullable', 'exists:polis,id'],
        ];
    }
}