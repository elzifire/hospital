@extends('layouts.app')

@section('title', 'Tambah Dokter')
@section('page-title', 'Tambah Dokter')

@section('content')
<div x-data="{ saving: false }" class="mx-auto max-w-2xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.dokter.index') }}" class="rounded transition hover:text-sky-600">Data Dokter</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">Tambah Baru</span>
        </nav>
        <h2 class="text-xl font-bold tracking-tight text-slate-900">Tambah Dokter</h2>
        <p class="mt-0.5 text-sm text-slate-500">Satu dokter terikat ke satu poli.</p>
    </div>

    <form action="{{ route('admin.dokter.store') }}" method="POST" @submit="saving = true" x-cloak>
        @csrf
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-100 p-6">
                <h3 class="text-base font-bold text-slate-900">Informasi Dokter</h3>
                <p class="mt-0.5 text-sm text-slate-500">Poli wajib dipilih, spesialisasi opsional.</p>
            </div>

            <div class="space-y-5 p-6">
                <div>
                    <label for="nama" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Nama Dokter <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama" id="nama" value="{{ old('nama') }}" required placeholder="cth. dr. Rina Pratiwi"
                           class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('nama') ring-rose-300 focus:ring-rose-500 @enderror">
                    @error('nama')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="spesialisasi" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Spesialisasi</label>
                    <input type="text" name="spesialisasi" id="spesialisasi" value="{{ old('spesialisasi') }}" placeholder="cth. Spesialis Penyakit Dalam"
                           class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('spesialisasi') ring-rose-300 focus:ring-rose-500 @enderror">
                    @error('spesialisasi')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="poli_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Poli <span class="text-rose-500">*</span></label>
                    @if ($polis->isEmpty())
                        <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500 ring-1 ring-slate-100">
                            Belum ada data poli. <a href="{{ route('admin.poli.create') }}" class="font-semibold text-sky-600 hover:underline">Tambahkan dulu</a>.
                        </p>
                    @else
                        <select name="poli_id" id="poli_id"
                                class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition cursor-pointer @error('poli_id') ring-rose-300 focus:ring-rose-500 @enderror">
                            <option value="">— Pilih Poli —</option>
                            @foreach ($polis as $poli)
                                <option value="{{ $poli->id }}" @selected(old('poli_id') == $poli->id)>{{ $poli->nama }}</option>
                            @endforeach
                        </select>
                        @error('poli_id')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 rounded-b-2xl border-t border-slate-100 bg-slate-50/70 p-4">
                <a href="{{ route('admin.dokter.index') }}"
                   class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Batal</a>
                <button type="submit" :disabled="saving"
                        class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                    <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-text="saving ? 'Menyimpan...' : 'Simpan Dokter'"></span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
