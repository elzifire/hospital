@extends('layouts.app')

@section('title', 'Edit Hari Libur')
@section('page-title', 'Edit Hari Libur')

@section('content')
<div x-data="{ saving: false }" class="mx-auto max-w-2xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.hari-libur.index') }}" class="rounded transition hover:text-sky-600">Hari Libur</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">Edit</span>
        </nav>
        <h2 class="text-xl font-bold tracking-tight text-slate-900">Edit Hari Libur</h2>
        <p class="mt-0.5 text-sm text-slate-500">{{ $hariLibur->tanggal->translatedFormat('l, d F Y') }} — {{ $hariLibur->nama }}</p>
    </div>

    @if ($hariLibur->sumber === \App\Models\HariLibur::SUMBER_GOOGLE_CALENDAR)
        <div class="flex items-start gap-3 rounded-xl bg-sky-50 px-4 py-3 text-sm text-sky-800 ring-1 ring-sky-200">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
            <p>Data ini tersinkron dari Google Calendar. Nama &amp; tanggal akan mengikuti kalender lagi pada sinkronisasi berikutnya — perubahan di sini hanya sementara sampai sinkronisasi berikutnya.</p>
        </div>
    @endif

    <form action="{{ route('admin.hari-libur.update', $hariLibur) }}" method="POST" @submit="saving = true" x-cloak>
        @csrf
        @method('PUT')
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-100 p-6">
                <h3 class="text-base font-bold text-slate-900">Informasi Hari Libur</h3>
                <p class="mt-0.5 text-sm text-slate-500">Kosongkan cakupan poli jika libur untuk semua poli.</p>
            </div>

            <div class="space-y-5 p-6">
                <div>
                    <label for="tanggal" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Tanggal <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal" id="tanggal" value="{{ old('tanggal', $hariLibur->tanggal->format('Y-m-d')) }}" required
                           class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('tanggal') ring-rose-300 focus:ring-rose-500 @enderror">
                    @error('tanggal')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="nama" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Nama Hari Libur <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama" id="nama" value="{{ old('nama', $hariLibur->nama) }}" required placeholder="cth. Cuti Bersama"
                           class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('nama') ring-rose-300 focus:ring-rose-500 @enderror">
                    @error('nama')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="poli_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Cakupan Poli</label>
                    <select name="poli_id" id="poli_id"
                            class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('poli_id') ring-rose-300 focus:ring-rose-500 @enderror">
                        <option value="">Semua Poli</option>
                        @foreach ($polis as $poli)
                            <option value="{{ $poli->id }}" @selected((string) old('poli_id', $hariLibur->poli_id) === (string) $poli->id)>{{ $poli->nama }}</option>
                        @endforeach
                    </select>
                    @error('poli_id')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    <p class="mt-1.5 text-xs text-slate-400">Isi hanya jika libur khusus satu poli; kosongkan jika berlaku untuk semua poli.</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 rounded-b-2xl border-t border-slate-100 bg-slate-50/70 p-4">
                <a href="{{ route('admin.hari-libur.index') }}"
                   class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Batal</a>
                <button type="submit" :disabled="saving"
                        class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                    <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-text="saving ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection