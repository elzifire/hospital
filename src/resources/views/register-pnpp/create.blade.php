@extends('layouts.guest')

@section('title', 'Registrasi PNPP')

@section('content')
<div class="w-full max-w-3xl" x-data="registerForm()">

    {{-- Halaman kembali --}}
    <a href="{{ route('login') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-sky-300 transition hover:text-white">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
        Kembali ke halaman login
    </a>

    <div class="overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-slate-900/5">

        {{-- Header --}}
        <div class="border-b border-slate-100 bg-gradient-to-r from-sky-50 to-slate-50 px-8 py-6">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-sky-600 text-white shadow-sm">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">Registrasi PNPP</h1>
                    <p class="text-sm text-slate-500">Pendaftaran online untuk pegawai yang menerima layanan kesehatan di RS Bhayangkara Bogor.</p>
                </div>
            </div>
        </div>

        <div class="px-8 py-6">
            @if (session('success'))
                <div class="mb-6 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">
                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    <div class="text-sm leading-relaxed">
                        <p class="font-bold">Pendaftaran berhasil dikirim.</p>
                        <p class="mt-0.5 text-emerald-700">Data Anda akan diverifikasi petugas. Status persetujuan akan diinformasikan ke nomor HP yang didaftarkan. Silakan tunggu konfirmasi dari pihak rumah sakit.</p>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800">
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

            <form method="POST" action="{{ route('register-pnpp.store') }}" class="space-y-8">
                @csrf

                {{-- ===== Data diri ===== --}}
                <section>
                    <h2 class="mb-4 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-sky-700">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-sky-600 text-[11px] text-white">1</span>
                        Data Diri
                    </h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nama Lengkap <span class="text-rose-500">*</span></label>
                            <input type="text" name="nama" value="{{ old('nama') }}" required
                                   class="block w-full rounded-xl border-0 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-sky-500">
                            @error('nama')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">NIK <span class="text-[11px] font-normal text-slate-400">(opsional)</span></label>
                            <input type="text" name="nik" value="{{ old('nik') }}" inputmode="numeric" maxlength="16" placeholder="Nomor Induk Kependudukan"
                                   class="block w-full rounded-xl border-0 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-sky-500">
                            @error('nik')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">NIP/NRP <span class="text-[11px] font-normal text-slate-400">(opsional)</span></label>
                            <input type="text" name="nip" value="{{ old('nip') }}" inputmode="numeric" maxlength="50" placeholder="Nomor Induk Pegawai"
                                   class="block w-full rounded-xl border-0 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-sky-500">
                            @error('nip')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Jabatan <span class="text-rose-500">*</span></label>
                            <input type="text" name="jabatan" value="{{ old('jabatan') }}" required
                                   class="block w-full rounded-xl border-0 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-sky-500">
                            @error('jabatan')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Tempat, Tanggal Lahir <span class="text-rose-500">*</span></label>
                            <input type="text" name="ttl" value="{{ old('ttl') }}" required placeholder="mis. Bogor, 12 Mei 1990"
                                   class="block w-full rounded-xl border-0 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-sky-500">
                            @error('ttl')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">No. HP <span class="text-rose-500">*</span></label>
                            <input type="text" name="no_hp" value="{{ old('no_hp') }}" required inputmode="tel"
                                   class="block w-full rounded-xl border-0 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-sky-500">
                            <p class="mt-1 text-[11px] text-slate-400">Untuk konfirmasi status persetujuan.</p>
                            @error('no_hp')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Alamat <span class="text-rose-500">*</span></label>
                            <textarea name="alamat" rows="2" required
                                      class="block w-full rounded-xl border-0 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-sky-500">{{ old('alamat') }}</textarea>
                            @error('alamat')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                {{-- ===== Satuan kerja ===== --}}
                <section>
                    <h2 class="mb-4 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-sky-700">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-sky-600 text-[11px] text-white">2</span>
                        Satuan Kerja
                    </h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <div class="mb-3 flex flex-wrap gap-4">
                                <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                                    <input type="radio" name="pilih_satker" value="list" x-model="satkerMode" class="h-4 w-4 border-slate-300 text-sky-600 focus:ring-sky-500">
                                    Pilih dari daftar satker
                                </label>
                                <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                                    <input type="radio" name="pilih_satker" value="baru" x-model="satkerMode" class="h-4 w-4 border-slate-300 text-sky-600 focus:ring-sky-500">
                                    Satker belum ada, tambah manual
                                </label>
                            </div>

                            <select name="satker_id" :disabled="satkerMode === 'baru'" :class="satkerMode === 'baru' ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'"
                                    class="block w-full rounded-xl border-0 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-sky-500">
                                <option value="">— Pilih satker asal —</option>
                                @foreach ($satkers as $satker)
                                    <option value="{{ $satker->id }}" @selected(old('satker_id') == $satker->id)>{{ $satker->nama }}</option>
                                @endforeach
                            </select>

                            <div x-show="satkerMode === 'baru'" x-cloak x-transition class="mt-3">
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nama Satker Baru <span class="text-rose-500">*</span></label>
                                <input type="text" name="satker_baru" value="{{ old('satker_baru') }}"
                                       class="block w-full rounded-xl border-0 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-sky-500"
                                       placeholder="mis. Dinas Kesehatan Kota Bogor">
                            </div>
                            @error('satker_id')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            @error('satker_baru')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Unit / Bagian <span class="text-[11px] font-normal text-slate-400">(opsional)</span></label>
                            <input type="text" name="unit" value="{{ old('unit') }}"
                                   class="block w-full rounded-xl border-0 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-sky-500">
                            @error('unit')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                {{-- ===== Kunjungan ===== --}}
                <section>
                    <h2 class="mb-4 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-sky-700">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-sky-600 text-[11px] text-white">3</span>
                        Rencana Kunjungan
                    </h2>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Tanggal Kunjungan <span class="text-rose-500">*</span></label>
                            <input type="date" name="rencana_tanggal_kunjungan" value="{{ old('rencana_tanggal_kunjungan') }}" required min="{{ date('Y-m-d') }}"
                                   class="block w-full rounded-xl border-0 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-sky-500">
                            @error('rencana_tanggal_kunjungan')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Jam Kunjungan <span class="text-rose-500">*</span></label>
                            <input type="time" name="rencana_jam_kunjungan" value="{{ old('rencana_jam_kunjungan') }}" required
                                   class="block w-full rounded-xl border-0 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-sky-500">
                            @error('rencana_jam_kunjungan')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="mt-5">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Poli Tujuan <span class="text-rose-500">*</span> <span class="text-[11px] font-normal text-slate-400">(boleh pilih lebih dari satu)</span></label>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($polis as $poli)
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 transition hover:border-sky-300 hover:bg-sky-50/50">
                                    <input type="checkbox" name="poli_dituju[]" value="{{ $poli->id }}"
                                           @checked(in_array($poli->id, old('poli_dituju', [])))
                                           class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                    <span class="text-sm font-medium text-slate-700">{{ $poli->nama }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('poli_dituju')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="mt-5">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Tujuan Kunjungan <span class="text-rose-500">*</span> <span class="text-[11px] font-normal text-slate-400">(boleh pilih lebih dari satu)</span></label>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($tujuanKunjungans as $tujuan)
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 transition hover:border-sky-300 hover:bg-sky-50/50">
                                    <input type="checkbox" name="tujuan_kunjungan[]" value="{{ $tujuan->id }}"
                                           @checked(in_array($tujuan->id, old('tujuan_kunjungan', [])))
                                           class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                    <span class="text-sm font-medium text-slate-700">{{ $tujuan->nama }}</span>
                                </label>
                            @endforeach
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 transition hover:border-sky-300 hover:bg-sky-50/50">
                                <input type="checkbox" x-model="lainnya" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                <span class="text-sm font-medium text-slate-700">Yang lain...</span>
                            </label>
                        </div>
                        <div x-show="lainnya" x-cloak x-transition class="mt-3">
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Jelaskan tujuan lainnya</label>
                            <input type="text" name="tujuan_lainnya" x-model="lainnyaText" value="{{ old('tujuan_lainnya') }}" placeholder="mis. Fisioterapi, rontgen, konseling..."
                                   class="block w-full rounded-xl border-0 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-sky-500">
                        </div>
                        @error('tujuan_kunjungan')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        @error('tujuan_lainnya')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </section>

                {{-- Aksi --}}
                <div class="flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-slate-400">Dengan mengirim formulir, Anda menyetujui data diverifikasi oleh petugas rumah sakit.</p>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-8 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                        Kirim Pendaftaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('registerForm', () => ({
            satkerMode: 'list',
            lainnya: false,
            lainnyaText: @js(old('tujuan_lainnya', '')),

            init() {
                if (this.lainnyaText) this.lainnya = true;
            }
        }));
    });
</script>
@endsection