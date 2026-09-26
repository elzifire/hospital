@extends('layouts.app')

@section('title', 'Edit Jadwal Registrasi PNPP')
@section('page-title', 'Edit Jadwal Registrasi PNPP')

@section('content')
@php
    $inputClass = 'block w-full rounded-xl border-0 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-sky-500';
    $labelClass = 'mb-1.5 block text-sm font-semibold text-slate-700';
    $errorClass = 'mt-1.5 text-xs font-medium text-rose-600';
    $hintClass = 'mt-1 text-[11px] text-slate-400';
    $checkboxItemClass = 'flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 transition hover:border-sky-300 hover:bg-sky-50/50';
    $checkboxInputClass = 'h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500';

    // Map poli -> jadwal utuh (hari + jam), untuk cek hidup tanggal & jam.
    $poliJadwal = $polis->mapWithKeys(fn ($p) => [$p->id => [
        'nama' => $p->nama,
        'buka' => $p->jam_buka?->format('H:i'),
        'tutup' => $p->jam_tutup?->format('H:i'),
        'hari' => $p->hariTercentang(),
        'bukaSetiapHari' => $p->bukaSetiapHari(),
        'jadwal' => $p->jadwalRingkas(),
    ]])->all();

    $hariLiburData = $hariLibur->map(fn ($h) => [
        'tanggal' => $h->tanggal->format('Y-m-d'),
        'nama' => $h->nama,
        'poli' => $h->poli_id,
    ])->values()->all();

    $poliDipilih = $registerPnpp->polis->pluck('id')->map(fn ($id) => (string) $id)->all();
@endphp

<div class="space-y-6" x-data="editJadwal({ poliJadwal: @js($poliJadwal), hariLibur: @js($hariLiburData), polis: @js($poliDipilih) })">
    {{-- ===== Header / aksi ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                <a href="{{ route('admin.register-pnpp.index') }}" class="rounded transition hover:text-sky-600">Registrasi PNPP</a>
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                <a href="{{ route('admin.register-pnpp.show', $registerPnpp) }}" class="rounded transition hover:text-sky-600">Detail</a>
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                <span>Edit Jadwal</span>
            </nav>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">{{ $registerPnpp->nama }}</h2>
            <p class="mt-1 text-sm text-slate-500">Ubah tanggal, jam, dan poli tujuan sebelum pendaftaran disetujui.</p>
        </div>
        <a href="{{ route('admin.register-pnpp.show', $registerPnpp) }}"
           class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
            Kembali ke Detail
        </a>
    </div>

    @if ($errors->any())
        <div class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
            <div class="text-sm">
                <p class="font-bold">Mohon perbaiki isian berikut:</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5">
                    @foreach ($errors->all() as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.register-pnpp.update', $registerPnpp) }}">
        @csrf
        @method('PATCH')

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-100 px-6 py-4">
                <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-slate-500">
                    <svg class="h-4 w-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                    Rencana Kunjungan
                </h3>
            </div>

            <div class="space-y-6 p-6">
                {{-- Poli tujuan --}}
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Poli Tujuan <span class="text-rose-500">*</span>
                        <span class="text-[11px] font-normal text-slate-400">(boleh pilih lebih dari satu)</span>
                    </label>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($polis as $poli)
                            <label class="{{ $checkboxItemClass }}">
                                <input type="checkbox" name="poli_dituju[]" value="{{ $poli->id }}"
                                       x-model="poliDituju" @change="cekJam(); cekHari();"
                                       class="{{ $checkboxInputClass }}">
                                <span class="flex min-w-0 flex-1 items-center justify-between gap-2">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium text-slate-700">{{ $poli->nama }}</span>
                                        <span class="mt-0.5 block truncate text-[11px] font-semibold text-slate-500">{{ $poli->jadwalRingkas() }}</span>
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('poli_dituju')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                {{-- Tanggal & jam --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="rencana_tanggal_kunjungan" class="{{ $labelClass }}">Tanggal Kunjungan <span class="text-rose-500">*</span></label>
                        <input type="date" id="rencana_tanggal_kunjungan" name="rencana_tanggal_kunjungan" required min="{{ date('Y-m-d') }}"
                               x-model="tanggal" @change="cekHari()"
                               class="{{ $inputClass }}">
                        <p class="{{ $hintClass }}" x-show="tanggal" x-cloak>
                            <span x-text="namaHari(tanggal)"></span>, <span x-text="formatTanggal(tanggal)"></span>
                        </p>
                        @error('rencana_tanggal_kunjungan')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="rencana_jam_kunjungan" class="{{ $labelClass }}">Jam Kunjungan <span class="text-rose-500">*</span></label>
                        <input type="time" id="rencana_jam_kunjungan" name="rencana_jam_kunjungan" required
                               x-model="jam" @change="cekJam()" class="{{ $inputClass }}">
                        @error('rencana_jam_kunjungan')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Peringatan live --}}
                <div x-show="pesanJam" x-cloak x-transition>
                    <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs font-medium leading-relaxed text-amber-800" x-text="pesanJam"></p>
                </div>
                <div x-show="pesanHari" x-cloak x-transition>
                    <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs font-medium leading-relaxed text-amber-800" x-text="pesanHari"></p>
                </div>
                <div x-show="pesanLibur" x-cloak x-transition>
                    <p class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-medium leading-relaxed text-rose-800" x-text="pesanLibur"></p>
                </div>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-100 p-6 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-400">Perubahan hanya berlaku selama belum ada poli yang disetujui.</p>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.register-pnpp.show', $registerPnpp) }}"
                       class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.86 7.86-8.02 8.02a2.25 2.25 0 0 1-1.023.57 4.5 4.5 0 0 1-1.646.19 2.25 2.25 0 0 1-1.594-1.594 4.5 4.5 0 0 1 .19-1.646 2.25 2.25 0 0 1 .57-1.023L13.14 4.14a2.25 2.25 0 0 1 3.182 0l.538.538a2.25 2.25 0 0 1 0 3.182ZM15 8.25 8.25 15m7.5-11.25-7.5 7.5M4.5 21h15" /></svg>
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('editJadwal', (payload) => ({
            poliDituju: payload.polis.map(String),
            jam: @js(old('rencana_jam_kunjungan', $registerPnpp->rencana_jam_kunjungan)),
            tanggal: @js(old('rencana_tanggal_kunjungan', $registerPnpp->rencana_tanggal_kunjungan?->format('Y-m-d'))),
            poliJadwal: payload.poliJadwal,
            hariLibur: payload.hariLibur,
            pesanJam: '',
            pesanHari: '',
            pesanLibur: '',

            dayNames: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],

            init() {
                this.cekJam();
                this.cekHari();
            },

            namaHari(t) {
                if (!t) return '';
                return this.dayNames[new Date(`${t}T00:00:00`).getDay()];
            },

            formatTanggal(t) {
                if (!t) return '';
                const [y, m, d] = t.split('-');
                return `${d}-${m}-${y}`;
            },

            cekJam() {
                const jam = this.jam;
                if (!jam) { this.pesanJam = ''; return; }

                const diluar = [];
                for (const id of this.poliDituju) {
                    const p = this.poliJadwal[id];
                    if (p && p.buka && p.tutup && (jam < p.buka || jam > p.tutup)) {
                        diluar.push(`${p.nama} (${p.buka}–${p.tutup})`);
                    }
                }

                this.pesanJam = diluar.length
                    ? `Jam ${jam} berada di luar jam layanan: ${diluar.join(', ')}. Silakan sesuaikan jam atau pilihan poli.`
                    : '';
            },

            cekHari() {
                const t = this.tanggal;
                this.pesanHari = '';
                this.pesanLibur = '';
                if (!t) return;

                const nama = this.namaHari(t);
                const idTerpilih = this.poliDituju.map(String);

                const tutup = [];
                for (const id of idTerpilih) {
                    const p = this.poliJadwal[id];
                    if (!p || p.bukaSetiapHari || p.hari.includes(nama)) continue;
                    tutup.push(`${p.nama} (${p.hari.join(', ')})`);
                }

                if (tutup.length) {
                    this.pesanHari = `${nama}, ${this.formatTanggal(t)} berada di luar hari layanan: ${tutup.join(', ')}. Silakan pilih tanggal lain.`;
                }

                const libur = this.hariLibur.filter(h =>
                    h.tanggal === t && (!h.poli || idTerpilih.includes(String(h.poli))));
                if (libur.length) {
                    this.pesanLibur = `${nama}, ${this.formatTanggal(t)} adalah hari libur (${libur.map(x => x.nama).join(', ')}). Silakan pilih tanggal lain.`;
                }
            },
        }));
    });
</script>
@endsection