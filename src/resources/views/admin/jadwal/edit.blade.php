@extends('layouts.app')

@section('title', 'Edit Jadwal')
@section('page-title', 'Edit Jadwal')

@section('content')
<div x-data="{ saving: false }" class="mx-auto max-w-2xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.jadwal.index') }}" class="rounded transition hover:text-sky-600">Jadwal Dokter</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">{{ $jadwal->dokter?->nama }}</span>
        </nav>
        <h2 class="text-xl font-bold tracking-tight text-slate-900">Edit Jadwal Dokter</h2>
        <p class="mt-0.5 text-sm text-slate-500">Perbarui hari dan jam praktik dokter.</p>
    </div>

    <form action="{{ route('admin.jadwal.update', $jadwal->id) }}" method="POST" @submit="saving = true" x-cloak>
        @csrf
        @method('PUT')
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-100 p-6">
                <h3 class="text-base font-bold text-slate-900">Informasi Jadwal</h3>
                <p class="mt-0.5 text-sm text-slate-500">Pilih dokter, hari, serta rentang jam.</p>
            </div>

            <div class="space-y-5 p-6">
                <div>
                    <label for="dokter_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Dokter <span class="text-rose-500">*</span></label>
                    <select name="dokter_id" id="dokter_id"
                            class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition cursor-pointer @error('dokter_id') ring-rose-300 focus:ring-rose-500 @enderror">
                        <option value="">— Pilih Dokter —</option>
                        @foreach ($dokters as $dokter)
                            <option value="{{ $dokter->id }}" @selected(old('dokter_id', $jadwal->dokter_id) == $dokter->id)>{{ $dokter->nama }} — {{ $dokter->poli?->nama }}</option>
                        @endforeach
                    </select>
                    @error('dokter_id')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="hari" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Hari <span class="text-rose-500">*</span></label>
                    <select name="hari" id="hari"
                            class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition cursor-pointer @error('hari') ring-rose-300 focus:ring-rose-500 @enderror">
                        <option value="">— Pilih Hari —</option>
                        @foreach (\App\Models\Jadwal::HARI as $hari)
                            <option value="{{ $hari }}" @selected(old('hari', $jadwal->hari) === $hari)>{{ $hari }}</option>
                        @endforeach
                    </select>
                    @error('hari')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="jam_mulai" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Jam Mulai <span class="text-rose-500">*</span></label>
                        <input type="time" name="jam_mulai" id="jam_mulai" value="{{ old('jam_mulai', $jadwal->jam_mulai->format('H:i')) }}" required
                               class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('jam_mulai') ring-rose-300 focus:ring-rose-500 @enderror">
                        @error('jam_mulai')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="jam_selesai" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Jam Selesai <span class="text-rose-500">*</span></label>
                        <input type="time" name="jam_selesai" id="jam_selesai" value="{{ old('jam_selesai', $jadwal->jam_selesai->format('H:i')) }}" required
                               class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('jam_selesai') ring-rose-300 focus:ring-rose-500 @enderror">
                        @error('jam_selesai')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 rounded-b-2xl border-t border-slate-100 bg-slate-50/70 p-4">
                <a href="{{ route('admin.jadwal.index') }}"
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
