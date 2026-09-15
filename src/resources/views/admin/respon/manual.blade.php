@extends('layouts.app')

@section('title', 'Input Manual')
@section('page-title', 'Respon Pasien')

@section('content')
<div class="space-y-6">
    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Input Manual</h2>
            <p class="mt-0.5 text-sm text-slate-500">Catat balasan pasien secara manual.</p>
        </div>
    </div>

    @include('admin.respon._flash')

    @include('admin.respon._subnav', ['active' => 'manual'])

    <div class="mx-auto max-w-2xl space-y-4">
        @if ($errors->any())
            <div class="rounded-2xl bg-rose-50 px-5 py-4 text-sm text-rose-700 ring-1 ring-rose-200">
                Harap perbaiki: {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.respon.manual-store') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <h3 class="mb-4 text-base font-bold text-slate-900">Catat Balasan Manual</h3>

            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Nama</label>
                    <input type="text" name="nama" value="{{ old('nama') }}" placeholder="Nama pasien"
                           class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">NRP/NIP</label>
                    <input type="text" name="nrp_nip" value="{{ old('nrp_nip') }}" placeholder="NRP / NIP pasien"
                           class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">No. HP <span class="text-rose-500">*</span></label>
                    <input type="text" name="no_hp" value="{{ old('no_hp') }}" placeholder="081234567890" required
                           class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Satker</label>
                    <input type="text" name="satker" value="{{ old('satker') }}" placeholder="Satuan Kerja"
                           class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Isi <span class="text-rose-500">*</span></label>
                    <textarea name="isi" rows="4" required placeholder="Isi balasan pasien…"
                              class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('isi') }}</textarea>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Waktu Masuk</label>
                    <input type="datetime-local" name="waktu" value="{{ old('waktu') }}"
                           class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    <p class="mt-1 text-xs text-slate-400">Kosongkan untuk memakai waktu sekarang.</p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75 6 14.25l3.75-3.75M4.5 18.75l9.75-9.75 3 3" /></svg>
                    Simpan Balasan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection