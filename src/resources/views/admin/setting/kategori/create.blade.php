@extends('layouts.app')

@section('title', 'Tambah Kategori Template')
@section('page-title', 'Tambah Kategori Template')

@section('content')
<div x-data="{ saving: false, selectedWarna: '{{ old('warna', 'sky') }}' }" class="mx-auto max-w-2xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.setting.kategori.index') }}" class="rounded transition hover:text-sky-600">Kategori Template</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">Tambah Baru</span>
        </nav>
        <h2 class="text-xl font-bold tracking-tight text-slate-900">Tambah Kategori Template</h2>
        <p class="mt-0.5 text-sm text-slate-500">Buat kelompok template baru untuk memudahkan klasifikasi pesan.</p>
    </div>

    <form action="{{ route('admin.setting.kategori.store') }}" method="POST" @submit="saving = true" x-cloak>
        @csrf
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-100 p-6">
                <h3 class="text-base font-bold text-slate-900">Informasi Kategori</h3>
                <p class="mt-0.5 text-sm text-slate-500">Nama wajib diisi, slug dibuat otomatis dari nama.</p>
            </div>

            <div class="space-y-5 p-6">
                <div>
                    <label for="nama" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Nama Kategori <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama" id="nama" value="{{ old('nama') }}" required placeholder="cth. Outreach"
                           class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('nama') ring-rose-300 focus:ring-rose-500 @enderror">
                    @error('nama')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    @include('admin.setting.kategori._warna_picker', ['selectedWarna' => old('warna', 'sky')])
                </div>

                <div>
                    <label for="deskripsi" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Deskripsi</label>
                    <textarea name="deskripsi" id="deskripsi" rows="3" placeholder="Jelaskan tujuan atau konteks kategori ini..."
                              class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('deskripsi') ring-rose-300 focus:ring-rose-500 @enderror">{{ old('deskripsi') }}</textarea>
                    @error('deskripsi')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <label class="flex cursor-pointer items-center gap-2.5 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                    Kategori aktif — bisa dipakai untuk mengelompokkan template
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 rounded-b-2xl border-t border-slate-100 bg-slate-50/70 p-4">
                <a href="{{ route('admin.setting.kategori.index') }}"
                   class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Batal</a>
                <button type="submit" :disabled="saving"
                        class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                    <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-text="saving ? 'Menyimpan...' : 'Simpan Kategori'"></span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection