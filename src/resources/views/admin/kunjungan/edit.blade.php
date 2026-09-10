@extends('layouts.app')

@section('title', 'Edit Catatan Poli — ' . $pnpp->nama)
@section('page-title', 'Edit Catatan Poli')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.pnpp.index') }}" class="rounded transition hover:text-sky-600">Data PNPP</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <a href="{{ route('admin.pnpp.kunjungan', $pnpp) }}" class="rounded transition hover:text-sky-600">Riwayat Kunjungan</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">Edit Catatan Poli</span>
        </nav>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Catatan Poli</h1>
        <p class="mt-1 text-sm text-slate-500">
            {{ $pnpp->nama }} · NIP {{ $pnpp->nip ?? '—' }} · {{ $pnpp->satker?->nama ?? '—' }}
        </p>
    </div>

    {{-- ===== Form edit ===== --}}
    <form action="{{ route('admin.pnpp.kunjungan.update', [$pnpp, $kunjungan]) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Catat Poli</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Koreksi tanggal, poli, keluhan, atau diagnosa — bebas tanpa harus hapus lalu tambah ulang.</p>
                    </div>
                    @if ($kunjungan->reminder_id)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200/70"
                              title="Baris ini realisasi dari jadwal: {{ $kunjungan->reminder->poli?->nama ?? '—' }}">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            Realisasi Digital Reminder — relasi tetap terjaga
                        </span>
                    @endif
                </div>
            </div>
            <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tanggal Kunjungan <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal_kunjungan" value="{{ old('tanggal_kunjungan', $kunjungan->tanggal_kunjungan?->format('Y-m-d')) }}" required
                           class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    @error('tanggal_kunjungan')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Poli <span class="text-rose-500">*</span></label>
                    <select name="poli_id" required
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        <option value="" disabled {{ old('poli_id', $kunjungan->poli_id) ? '' : 'selected' }}>— Pilih poli —</option>
                        @foreach ($polis as $po)
                            <option value="{{ $po->id }}" {{ old('poli_id', $kunjungan->poli_id) == $po->id ? 'selected' : '' }}>{{ $po->nama }}</option>
                        @endforeach
                    </select>
                    @error('poli_id')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Keluhan</label>
                    <input type="text" name="keluhan" maxlength="1000" value="{{ old('keluhan', $kunjungan->keluhan) }}" placeholder="cth. Pusing, demam"
                           class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    @error('keluhan')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Diagnosa</label>
                    <input type="text" name="diagnosa" maxlength="1000" value="{{ old('diagnosa', $kunjungan->diagnosa) }}" placeholder="cth. Hipertensi"
                           class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    @error('diagnosa')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/50 px-5 py-4">
                <button type="button" onclick='confirmSubmit( @json(route("admin.pnpp.kunjungan.destroy", [$pnpp, $kunjungan])), { title: "Hapus Catatan Poli?", html: @json( '<p class="text-sm text-slate-600"> Catatan poli <strong>' . ($kunjungan->poli?->nama ?? 'tanpa poli') . '</strong> tanggal <strong>' . ($kunjungan->tanggal_kunjungan?->translatedFormat('d M Y') ?? '-') . '</strong> akan dihapus permanen. </p>' ), confirmText: "Ya, hapus" } )' class="inline-flex items-center gap-2 rounded-xl bg-rose-50 px-4 py-2.5 text-sm font-bold text-rose-600 ring-1 ring-inset ring-rose-200 transition hover:bg-rose-100" >
                        class="inline-flex items-center gap-2 rounded-xl bg-rose-50 px-4 py-2.5 text-sm font-bold text-rose-600 ring-1 ring-inset ring-rose-200 transition hover:bg-rose-100">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                    Hapus
                </button>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.pnpp.kunjungan', $pnpp) }}"
                       class="rounded-xl bg-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-300">Batal</a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
