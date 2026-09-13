<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegisterPnpp;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegisterPnppController extends Controller
{
    /**
     * Daftar pendaftaran dengan filter status + pencarian.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $query = RegisterPnpp::query()
            ->with(['satker', 'polis', 'tujuanKunjungans'])
            ->when($request->query('search'), function (Builder $q, string $cari): void {
                $cari = '%'.trim($cari).'%';
                $q->where(fn (Builder $sub): Builder => $sub
                    ->where('nama', 'like', $cari)
                    ->orWhere('nik', 'like', $cari)
                    ->orWhere('nip', 'like', $cari)
                    ->orWhere('no_hp', 'like', $cari));
            })
            ->when(in_array($status, RegisterPnpp::STATUS, true), fn (Builder $q): Builder => $q->where('status', $status))
            ->latest();

        $registers = $query->paginate(15)->withQueryString();

        return view('admin.register-pnpp.index', [
            'registers' => $registers,
            'status' => $status,
            'counts' => [
                'semua' => RegisterPnpp::count(),
                RegisterPnpp::STATUS_BELUM_DISETUJUI => RegisterPnpp::where('status', RegisterPnpp::STATUS_BELUM_DISETUJUI)->count(),
                RegisterPnpp::STATUS_DISETUJUI => RegisterPnpp::where('status', RegisterPnpp::STATUS_DISETUJUI)->count(),
            ],
        ]);
    }

    /**
     * Detail pendaftaran (data + poli + tujuan, approval terpusat di sini).
     */
    public function show(RegisterPnpp $registerPnpp): View
    {
        $registerPnpp->load(['satker', 'polis', 'tujuanKunjungans']);

        return view('admin.register-pnpp.show', compact('registerPnpp'));
    }

    /**
     * Toggle status persetujuan: belum disetujui <-> disetujui.
     */
    public function approve(RegisterPnpp $registerPnpp): RedirectResponse
    {
        $registerPnpp->status = $registerPnpp->status === RegisterPnpp::STATUS_DISETUJUI
            ? RegisterPnpp::STATUS_BELUM_DISETUJUI
            : RegisterPnpp::STATUS_DISETUJUI;
        $registerPnpp->save();

        $label = $registerPnpp->status === RegisterPnpp::STATUS_DISETUJUI ? 'disetujui' : 'diubah menjadi belum disetujui';

        return back()->with('success', "Pendaftaran {$registerPnpp->nama} berhasil {$label}.");
    }

    /**
     * Hapus pendaftaran (termasuk pivot poli & tujuan).
     */
    public function destroy(RegisterPnpp $registerPnpp): RedirectResponse
    {
        $nama = $registerPnpp->nama;
        $registerPnpp->delete();

        return redirect()
            ->route('admin.register-pnpp.index')
            ->with('success', "Pendaftaran {$nama} berhasil dihapus.");
    }
}
