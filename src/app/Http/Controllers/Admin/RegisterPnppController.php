<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Models\Pnpp;
use App\Models\Poli;
use App\Models\RegisterPnpp;
use App\Models\Reminder;
use App\Services\PencocokPnpp;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisterPnppController extends Controller
{
    /**
     * Daftar pendaftaran dengan filter status + pencarian. Tiap baris
     * ditandai apakah pendaftarnya sudah ada di database PNPP (tanda).
     *
     * Akun poli hanya melihat pendaftaran yang ditujukan ke polinya dan
     * hanya bisa menyetujui/membatalkan bagian polinya sendiri.
     */
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $poliId = (string) $request->query('poli', '');
        $poliAktif = $this->poliAktif();
        $batasiPoli = $poliAktif !== null;

        $dasar = fn (): Builder => $this->scopedQuery($poliAktif, $batasiPoli, $poliId);

        $registers = $dasar()
            ->with(['satker', 'polis', 'tujuanKunjungans'])
            ->when($request->query('search'), function (Builder $q, string $cari): void {
                $cariAtas = strtoupper(trim($cari));
                $cariLike = '%'.$cariAtas.'%';
                $cariRaw = '%'.trim($cari).'%';
                $q->where(fn (Builder $sub): Builder => $sub
                    ->whereRaw('UPPER(nama) LIKE ?', [$cariLike])
                    ->orWhere('nik', 'like', $cariRaw)
                    ->orWhere('nip', 'like', $cariRaw)
                    ->orWhere('no_hp', 'like', $cariRaw));
            })
            ->when($status !== '' && in_array($status, RegisterPnpp::STATUS, true), fn (Builder $q): Builder => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.register-pnpp.index', [
            'registers' => $registers,
            'status' => $status,
            'poliId' => $poliId,
            'poliAktif' => $poliAktif,
            'batasiPoli' => $batasiPoli,
            'polis' => Poli::orderBy('nama')->get(['id', 'nama']),
            'tanda' => PencocokPnpp::tandaiKoleksi($registers->getCollection()),
            'counts' => [
                'semua' => $dasar()->count(),
                RegisterPnpp::STATUS_BELUM_DISETUJUI => $dasar()->where('status', RegisterPnpp::STATUS_BELUM_DISETUJUI)->count(),
                RegisterPnpp::STATUS_DISETUJUI => $dasar()->where('status', RegisterPnpp::STATUS_DISETUJUI)->count(),
            ],
        ]);
    }

    /**
     * Detail pendaftaran — menampilkan sinkronisasi PNPP & persetujuan
     * per poli tujuan.
     */
    public function show(RegisterPnpp $registerPnpp): View
    {
        $registerPnpp->load(['satker', 'polis', 'tujuanKunjungans']);

        $this->pastikanPoliDituju($registerPnpp);

        return view('admin.register-pnpp.show', [
            'registerPnpp' => $registerPnpp,
            'poliAktif' => $this->poliAktif(),
            'batasiPoli' => $this->batasiPoli(),
            'pnppCocok' => PencocokPnpp::cari($registerPnpp),
        ]);
    }

    /**
     * Form edit jadwal kunjungan & poli tujuan — hanya admin/superadmin,
     * dan hanya selama belum ada poli yang disetujui.
     */
    public function edit(RegisterPnpp $registerPnpp): View
    {
        $this->pastikanJadwalBisaDiedit($registerPnpp);
        $registerPnpp->load(['satker', 'polis', 'tujuanKunjungans']);

        return view('admin.register-pnpp.edit', [
            'registerPnpp' => $registerPnpp,
            'poliAktif' => $this->poliAktif(),
            'batasiPoli' => $this->batasiPoli(),
            'polis' => Poli::orderBy('nama')->get(),
            'hariLibur' => HariLibur::orderBy('tanggal')->get(),
        ]);
    }

    /**
     * Simpan perubahan jadwal kunjungan & poli tujuan. Validasi ulang jam
     * terhadap jam layanan, tanggal terhadap hari layanan & hari libur
     * dari poli yang baru dipilih, lalu sinkronkan pivot poli.
     */
    public function update(Request $request, RegisterPnpp $registerPnpp): RedirectResponse
    {
        $this->pastikanJadwalBisaDiedit($registerPnpp);

        $data = $request->validate([
            'poli_dituju' => ['required', 'array', 'min:1'],
            'poli_dituju.*' => ['integer', 'exists:polis,id'],
            'rencana_tanggal_kunjungan' => ['required', 'date', 'after_or_equal:today'],
            'rencana_jam_kunjungan' => ['required', 'date_format:H:i'],
        ]);

        $this->validasiJadwalBaru($data['poli_dituju'], $data['rencana_tanggal_kunjungan'], $data['rencana_jam_kunjungan']);

        DB::transaction(function () use ($registerPnpp, $data): void {
            $registerPnpp->update([
                'rencana_tanggal_kunjungan' => $data['rencana_tanggal_kunjungan'],
                'rencana_jam_kunjungan' => $data['rencana_jam_kunjungan'],
            ]);
            $registerPnpp->polis()->sync($data['poli_dituju']);
        });

        return redirect()
            ->route('admin.register-pnpp.show', $registerPnpp)
            ->with('success', "Jadwal & poli tujuan {$registerPnpp->nama} diperbarui.");
    }

    /**
     * Guard edit: hanya admin/superadmin (bukan akun poli), dan hanya
     * selama belum ada poli yang disetujui.
     */
    protected function pastikanJadwalBisaDiedit(RegisterPnpp $register): void
    {
        abort_if($this->batasiPoli(), 403, 'Akun poli tidak dapat mengubah jadwal pendaftaran.');

        if ($register->approvedPolis()->exists()) {
            abort(403, 'Jadwal pendaftaran tidak dapat diubah setelah ada poli yang disetujui.');
        }
    }

    /**
     * Validasi ulang jadwal baru terhadap poli yang dipilih: jam harus
     * dalam jam layanan, tanggal dalam hari layanan, dan bukan hari libur.
     */
    protected function validasiJadwalBaru(array $poliIds, string $tanggal, string $jam): void
    {
        $poliTujuan = Poli::whereIn('id', $poliIds)->get();

        $diLuarJam = [];

        foreach ($poliTujuan as $poli) {
            if ($poli->buka24Jam()) {
                continue;
            }

            $buka = $poli->jam_buka->format('H:i');
            $tutup = $poli->jam_tutup->format('H:i');

            if ($jam < $buka || $jam > $tutup) {
                $diLuarJam[] = "{$poli->nama} ({$buka}–{$tutup})";
            }
        }

        if ($diLuarJam !== []) {
            throw ValidationException::withMessages([
                'rencana_jam_kunjungan' => "Jam {$jam} berada di luar jam layanan: ".implode(', ', $diLuarJam).'.',
            ]);
        }

        $tanggalKunjungan = Carbon::parse($tanggal);
        $diLuarHari = [];

        foreach ($poliTujuan as $poli) {
            if (! $poli->hariBuka($tanggalKunjungan)) {
                $diLuarHari[] = "{$poli->nama} ({$poli->hariLayanan()})";
            }
        }

        if ($diLuarHari !== []) {
            throw ValidationException::withMessages([
                'rencana_tanggal_kunjungan' => "Tanggal {$tanggal} berada di luar hari layanan: ".implode(', ', $diLuarHari).'.',
            ]);
        }

        $libur = HariLibur::where('tanggal', $tanggal)
            ->where(fn ($q) => $q->whereNull('poli_id')->orWhereIn('poli_id', $poliIds))
            ->first();

        if ($libur !== null) {
            $cakupan = $libur->poli_id !== null ? ' khusus poli tujuan' : '';

            throw ValidationException::withMessages([
                'rencana_tanggal_kunjungan' => "Tanggal {$tanggal} adalah hari libur: {$libur->nama}{$cakupan}. Silakan pilih tanggal lain.",
            ]);
        }
    }

    /**
     * Setujui pendaftaran — menyetujui per poli tujuan:
     *  - akun poli  : menyetujui polinya sendiri (wajib menjadi tujuan).
     *  - admin/superadmin : dengan ?poli_id=menyetujui satu poli;
     *    tanpa poli_id menyetujui semua poli sekaligus.
     *
     * Saat disetujui: data diri disinkronkan ke tabel pnpps (buat/update)
     * lalu dibuatkan penjadwalan Digital Reminder untuk poli tersebut
     * (tanggal/jam dari rencana kunjungan), jika belum ada.
     */
    public function approve(Request $request, RegisterPnpp $registerPnpp): RedirectResponse
    {
        $registerPnpp->load('polis');

        $poliIds = $this->polisTarget($request, $registerPnpp);

        if ($poliIds === []) {
            return back()->with('error', 'Tidak ada poli tujuan untuk disetujui.');
        }

        DB::transaction(function () use ($request, $registerPnpp, $poliIds): void {
            $pnpp = PencocokPnpp::sinkronkan($registerPnpp);

            foreach ($poliIds as $poliId) {
                $registerPnpp->polis()->updateExistingPivot($poliId, [
                    'approved_at' => now(),
                    'approved_by' => $request->user()?->id,
                ]);

                $this->buatReminderOtomatis($registerPnpp, $pnpp, $poliId, $request);
            }
        });

        $this->perbaruiStatus($registerPnpp);

        $jumlah = count($poliIds);
        $kata = $jumlah > 1 ? "{$jumlah} poli" : $this->namaPoli($registerPnpp, $poliIds[0]);

        return back()->with('success', "Pendaftaran {$registerPnpp->nama} disetujui untuk {$kata} — penjadwalan Digital Reminder dibuat.");
    }

    /**
     * Batalkan persetujuan — kebalikan dari approve: menghapus tanda
     * persetujuan poli target beserta reminder otomatisnya (soft delete).
     */
    public function unapprove(Request $request, RegisterPnpp $registerPnpp): RedirectResponse
    {
        $registerPnpp->load('polis');

        $poliIds = $this->polisTarget($request, $registerPnpp);

        if ($poliIds === []) {
            return back()->with('error', 'Tidak ada poli tujuan untuk dibatalkan persetujuannya.');
        }

        DB::transaction(function () use ($registerPnpp, $poliIds): void {
            foreach ($poliIds as $poliId) {
                $registerPnpp->polis()->updateExistingPivot($poliId, [
                    'approved_at' => null,
                    'approved_by' => null,
                ]);

                $this->hapusReminderOtomatis($registerPnpp, $poliId);
            }
        });

        $this->perbaruiStatus($registerPnpp);

        $jumlah = count($poliIds);
        $kata = $jumlah > 1 ? "{$jumlah} poli" : $this->namaPoli($registerPnpp, $poliIds[0]);

        return back()->with('success', "Persetujuan {$registerPnpp->nama} untuk {$kata} dibatalkan — penjadwalan otomatis turut dihapus.");
    }

    /**
     * Hapus pendaftaran (termasuk pivot poli & tujuan) beserta reminder
     * otomatis yang diturunkan darinya.
     */
    public function destroy(RegisterPnpp $registerPnpp): RedirectResponse
    {
        $nama = $registerPnpp->nama;

        DB::transaction(function () use ($registerPnpp): void {
            Reminder::where('register_pnpp_id', $registerPnpp->id)
                ->get()
                ->each
                ->delete();

            $registerPnpp->delete();
        });

        return redirect()
            ->route('admin.register-pnpp.index')
            ->with('success', "Pendaftaran {$nama} berhasil dihapus.");
    }

    /**
     * Query pendaftaran yang dibatasi poli:
     *  - akun poli → hanya pendaftaran yang menargetkan polinya.
     *  - admin/superadmin → filter ?poli= bila diisi.
     */
    protected function scopedQuery(?int $poliAktif, bool $batasiPoli, string $poliId): Builder
    {
        return RegisterPnpp::query()
            ->when($batasiPoli, fn (Builder $q): Builder => $q->whereHas('polis', fn ($p): Builder => $p->where('polis.id', $poliAktif)))
            ->when(! $batasiPoli && $poliId !== '', fn (Builder $q): Builder => $q->whereHas('polis', fn ($p): Builder => $p->where('polis.id', (int) $poliId)));
    }

    /**
     * ID poli pemilik akun login — null untuk admin/superadmin.
     */
    protected function poliAktif(): ?int
    {
        return auth()->user()?->poliId();
    }

    /**
     * Apakah user login adalah akun poli.
     */
    protected function batasiPoli(): bool
    {
        return $this->poliAktif() !== null;
    }

    /**
     * Pastikan pendaftaran ditujukan ke poli user (403 bila bukan).
     * Untuk admin/superadmin hanya diverifikasi saat poli_id eksplisit.
     */
    protected function pastikanPoliDituju(RegisterPnpp $register, ?int $poliId = null): void
    {
        $poliId ??= $this->poliAktif();

        if ($poliId === null) {
            return;
        }

        abort_unless(
            $register->polis->contains(fn ($p) => (int) $p->id === (int) $poliId),
            403,
            'Pendaftaran ini tidak ditujukan untuk poli Anda.',
        );
    }

    /**
     * Daftar poli yang menjadi target aksi approve/unapprove:
     * akun poli → polinya; admin/superadmin → ?poli_id atau semua poli.
     *
     * @return array<int>
     */
    protected function polisTarget(Request $request, RegisterPnpp $register): array
    {
        $poliAktif = $this->poliAktif();

        if ($poliAktif !== null) {
            $this->pastikanPoliDituju($register);

            return [$poliAktif];
        }

        $poliId = (int) $request->query('poli_id', 0);

        if ($poliId > 0) {
            $this->pastikanPoliDituju($register, $poliId);

            return [$poliId];
        }

        return $register->polis->map(fn ($p) => (int) $p->id)->all();
    }

    /**
     * Buat penjadwalan Digital Reminder dari pendaftaran yang disetujui.
     * Idempoten: hanya dicek berbedaan register_pnpp_id + poli_id.
     */
    protected function buatReminderOtomatis(RegisterPnpp $register, Pnpp $pnpp, int $poliId, Request $request): Reminder
    {
        return Reminder::firstOrCreate(
            ['register_pnpp_id' => $register->id, 'poli_id' => $poliId],
            [
                'pnpp_id' => $pnpp->id,
                'tanggal' => $register->rencana_tanggal_kunjungan,
                'jam' => $register->rencana_jam_kunjungan,
                'home_visit' => false,
                'status' => 'terjadwal',
                'created_by' => $request->user()?->id,
                'catatan' => 'Dibuat otomatis dari pendaftaran PNPP #'.$register->id.' ('.$register->nama.').',
                'register_pnpp_id' => $register->id,
            ],
        );
    }

    /**
     * Hapus (soft delete) reminder otomatis milik pendaftaran + poli.
     */
    protected function hapusReminderOtomatis(RegisterPnpp $register, int $poliId): void
    {
        Reminder::where('register_pnpp_id', $register->id)
            ->where('poli_id', $poliId)
            ->get()
            ->each
            ->delete();
    }

    /**
     * Perbarui status keseluruhan: "disetujui" hanya bila semua poli
     * tujuan sudah disetujui, selebihnya "belum disetujui".
     */
    protected function perbaruiStatus(RegisterPnpp $register): void
    {
        $belum = $register->polis()->wherePivotNull('approved_at')->count();

        $register->status = $belum === 0
            ? RegisterPnpp::STATUS_DISETUJUI
            : RegisterPnpp::STATUS_BELUM_DISETUJUI;

        $register->save();
    }

    protected function namaPoli(RegisterPnpp $register, int $poliId): string
    {
        return $register->polis->firstWhere('id', $poliId)?->nama ?? "poli #{$poliId}";
    }
}
