@extends('layouts.app')

@section('title', 'Edit Poli')
@section('page-title', 'Edit Poli')

@section('content')
@php
    // Jadwal awal: dari input lama (saat validasi gagal) atau jadwal efektif poli.
    $jadwalAwal = old('jadwal_hari', $poli->jadwalPerHari());
@endphp
<div x-data="jadwalPoli(@js($jadwalAwal))" class="mx-auto max-w-3xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.poli.index') }}" class="rounded transition hover:text-sky-600">Data Poli</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">{{ $poli->nama }}</span>
        </nav>
        <h2 class="text-xl font-bold tracking-tight text-slate-900">Edit Poli</h2>
        <p class="mt-0.5 text-sm text-slate-500">Perbarui informasi dan jadwal layanan poliklinik.</p>
    </div>

    <form action="{{ route('admin.poli.update', $poli->id) }}" method="POST" @submit="saving = true" x-cloak>
        @csrf
        @method('PUT')
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-100 p-6">
                <h3 class="text-base font-bold text-slate-900">Informasi Poli</h3>
                <p class="mt-0.5 text-sm text-slate-500">Kode bersifat opsional, nama wajib diisi.</p>
            </div>

            <div class="space-y-6 p-6">
                <div>
                    <label for="kode" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Kode Poli</label>
                    <input type="text" name="kode" id="kode" value="{{ old('kode', $poli->kode) }}" placeholder="cth. GIGI"
                           class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('kode') ring-rose-300 focus:ring-rose-500 @enderror">
                    @error('kode')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="nama" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Nama Poli <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama" id="nama" value="{{ old('nama', $poli->nama) }}" required placeholder="cth. Poli Gigi"
                           class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('nama') ring-rose-300 focus:ring-rose-500 @enderror">
                    @error('nama')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ===== Jadwal Layanan Per Hari ===== --}}
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-100 p-6">
                <h3 class="text-base font-bold text-slate-900">Jadwal Layanan Per Hari</h3>
                <p class="mt-0.5 text-sm text-slate-500">Tiap hari bisa buka dengan jam berbeda. Hari tanpa ceklis = poli tutup, jam kosong = buka 24 jam.</p>
            </div>

            {{-- Ringkasan live --}}
            <div class="border-b border-slate-100 bg-sky-50/50 px-6 py-4">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-4.5 w-4.5 flex-shrink-0 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-sky-700">Ringkasan Jadwal</p>
                        <p class="mt-0.5 break-words text-sm font-semibold leading-relaxed text-slate-800" x-text="ringkasan()"></p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                {{-- Aksi bantuan --}}
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    <button type="button" @click="bukaSemua()"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-sky-50 px-3 py-1.5 text-xs font-bold text-sky-700 ring-1 ring-sky-200 transition hover:bg-sky-100">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Semua Buka
                    </button>
                    <button type="button" @click="isiWaktu('08:00', '17:00')"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-200">
                        Isi 08:00–17:00 semua hari buka
                    </button>
                    <button type="button" @click="salinDariSenin()"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-200">
                        Salin jam Senin ke hari lain
                    </button>
                </div>

                {{-- Tabel jadwal per hari --}}
                <div class="overflow-hidden rounded-xl ring-1 ring-slate-200">
                    <div class="grid grid-cols-[1fr_auto_auto] items-center gap-x-3 bg-slate-50 px-4 py-2.5 text-[11px] font-bold uppercase tracking-wide text-slate-400 sm:grid-cols-[1fr_auto_auto_auto]">
                        <span>Hari</span>
                        <span class="w-28 text-right sm:w-32">Jam Buka</span>
                        <span class="hidden w-28 text-right sm:block sm:w-32">Jam Tutup</span>
                        <span class="w-16 text-right">Status</span>
                    </div>

                    @error('jadwal_hari')<p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror

                    <div class="divide-y divide-slate-100">
                        @foreach (\App\Models\Poli::DAFTAR_HARI as $i => $nama)
                            <div class="grid grid-cols-[1fr_auto_auto] items-center gap-x-3 px-4 py-3 transition sm:grid-cols-[1fr_auto_auto_auto]"
                                 :class="rows[{{ $i }}].buka ? 'bg-white' : 'bg-slate-50/40'">
                                <div class="flex items-center gap-2.5">
                                    <input type="checkbox" name="jadwal_hari[{{ $nama }}][buka]" value="1"
                                           x-model="rows[{{ $i }}].buka" :id="'buka-{{ $i }}'"
                                           class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                    <label :for="'buka-{{ $i }}'"
                                           class="cursor-pointer select-none text-sm font-semibold text-slate-800"
                                           :class="{ 'text-slate-400 line-through': !rows[{{ $i }}].buka }">
                                        {{ $nama }}
                                    </label>
                                </div>

                                <div class="w-28 sm:w-32">
                                    <input type="time" name="jadwal_hari[{{ $nama }}][jam_buka]"
                                           x-model="rows[{{ $i }}].jam_buka" :disabled="!rows[{{ $i }}].buka"
                                           class="block w-full rounded-lg border-0 bg-slate-50 px-2.5 py-1.5 text-center text-xs font-semibold tabular-nums text-slate-800 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 disabled:cursor-not-allowed disabled:opacity-40 transition"
                                           :class="!rows[{{ $i }}].buka ? 'bg-slate-100' : ''">
                                    @error('jadwal_hari.'.$nama.'.jam_buka')<p class="mt-1 text-[11px] font-medium text-rose-600">{{ $message }}</p>@enderror
                                </div>

                                <div class="hidden w-28 sm:block sm:w-32">
                                    <input type="time" name="jadwal_hari[{{ $nama }}][jam_tutup]"
                                           x-model="rows[{{ $i }}].jam_tutup" :disabled="!rows[{{ $i }}].buka"
                                           class="block w-full rounded-lg border-0 bg-slate-50 px-2.5 py-1.5 text-center text-xs font-semibold tabular-nums text-slate-800 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 disabled:cursor-not-allowed disabled:opacity-40 transition"
                                           :class="!rows[{{ $i }}].buka ? 'bg-slate-100' : ''">
                                    @error('jadwal_hari.'.$nama.'.jam_tutup')<p class="mt-1 text-[11px] font-medium text-rose-600">{{ $message }}</p>@enderror
                                </div>

                                <div class="flex w-16 justify-end">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide"
                                          :class="rows[{{ $i }}].buka ? (rows[{{ $i }}].jam_buka && rows[{{ $i }}].jam_tutup ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-sky-50 text-sky-700 ring-1 ring-sky-200') : 'bg-slate-100 text-slate-400 ring-1 ring-slate-200'"
                                          x-text="rows[{{ $i }}].buka ? (rows[{{ $i }}].jam_buka && rows[{{ $i }}].jam_tutup ? 'Buka' : '24 Jam') : 'Tutup'"></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <p class="mt-3 text-xs text-slate-400">Pola jam tutup hanya ditampilkan di layar lebar; pada ponsel tetap ikut tersimpan.</p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
            <a href="{{ route('admin.poli.index') }}"
               class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Batal</a>
            <button type="submit" :disabled="saving"
                    class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span x-text="saving ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('jadwalPoli', (jadwalAwal) => ({
            saving: false,
            rows: @php
                $daftar = \App\Models\Poli::DAFTAR_HARI;
                $baris = [];
                foreach ($daftar as $nama) {
                    $entri = $jadwalAwal[$nama] ?? ['buka' => false, 'jam_buka' => null, 'jam_tutup' => null];
                    $baris[] = [
                        'hari' => $nama,
                        'buka' => (bool) ($entri['buka'] ?? false),
                        'jamBuka' => $entri['jam_buka'] ?? '',
                        'jamTutup' => $entri['jam_tutup'] ?? '',
                    ];
                }
            @endphp
            @js($baris),

            // Label singkat tiap hari.
            pendek: { 'Senin': 'Sen', 'Selasa': 'Sel', 'Rabu': 'Rab', 'Kamis': 'Kam', 'Jumat': 'Jum', 'Sabtu': 'Sab', 'Minggu': 'Min' },

            bukaSemua() {
                this.rows.forEach(r => r.buka = true);
            },

            isiWaktu(buka, tutup) {
                this.rows.forEach(r => {
                    if (r.buka) { r.jamBuka = buka; r.jamTutup = tutup; }
                });
            },

            salinDariSenin() {
                const s = this.rows[0];
                if (!s.jamBuka && !s.jamTutup) return;
                this.rows.forEach((r, i) => {
                    if (r.buka && i !== 0) { r.jamBuka = s.jamBuka; r.jamTutup = s.jamTutup; }
                });
            },

            labelSegmen(s) {
                const a = this.pendek[s.awal.hari] ?? s.awal.hari;
                const b = this.pendek[s.akhir.hari] ?? s.akhir.hari;
                const nama = s.awal === s.akhir ? a : `${a}–${b}`;

                if (s.label === 'Tutup') return `${nama} Tutup`;
                return `${nama} ${s.label}`;
            },

            ringkasan() {
                const segmen = [];
                let terakhir = null;

                this.rows.forEach(r => {
                    const label = r.buka
                        ? (r.jamBuka && r.jamTutup ? `${r.jamBuka}–${r.jamTutup}` : '24 Jam')
                        : 'Tutup';

                    if (terakhir && terakhir.label === label) {
                        terakhir.akhir = r;
                    } else {
                        if (terakhir) segmen.push(terakhir);
                        terakhir = { awal: r, akhir: r, label };
                    }
                });

                if (terakhir) segmen.push(terakhir);

                if (segmen.length === 1 && segmen[0].label === 'Tutup') return 'Poli tutup sepanjang minggu — minimal satu hari harus dibuka.';

                return segmen.map(s => this.labelSegmen(s)).join(' · ');
            }
        }));
    });
</script>
@endsection