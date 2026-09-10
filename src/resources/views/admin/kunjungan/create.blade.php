@extends('layouts.app')

@section('title', 'Tambah Kunjungan')
@section('page-title', 'Tambah Kunjungan')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Tambah Kunjungan</h1>
            <p class="mt-1 text-sm text-slate-500">
                Pilih satu pasien PNPP, lalu catat semua poli yang dikunjunginya pada satu tanggal.
            </p>
        </div>
        <a href="{{ route('admin.kunjungan.index') }}"
           class="inline-flex items-center gap-2 rounded-lg bg-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-300">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Kembali ke Daftar
        </a>
    </div>

    {{-- ===== Langkah 1a: filter pasien (form GET terpisah) ===== --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-sm font-bold text-slate-900">1. Cari &amp; Pilih Pasien</h2>
            <p class="mt-0.5 text-xs text-slate-500">Saring pasien PNPP — hasil filter menjadi kandidat di bawahnya.</p>
        </div>
        <form method="GET" action="{{ request()->url() }}"
              class="flex flex-wrap items-end gap-3 bg-slate-50/50 px-5 py-4">
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Nama / NIP / no. HP…"
                       class="w-52 rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Satker</label>
                <select name="satker" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    <option value="">Semua</option>
                    @foreach ($satkers as $s)
                        <option value="{{ $s->id }}" {{ $filters['satker'] == $s->id ? 'selected' : '' }}>{{ $s->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">
                    Filter
                </button>
                <a href="{{ request()->url() }}"
                   class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-300">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- ===== Form utama (POST) ===== --}}
    <form action="{{ route('admin.kunjungan.store') }}" method="POST" x-data="{ saving: false }" @submit="saving = true" class="space-y-6">
        @csrf

        {{-- ===== Langkah 1b: tabel pasien (pilih satu) ===== --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-bold text-slate-900">
                    Pasien <span class="font-medium text-slate-400">({{ $pnpps->count() }} hasil filter)</span>
                </h2>
                <p class="mt-0.5 text-xs text-slate-500">Pilih satu pasien — kunjungan bisa dicatat untuk beberapa poli sekaligus.</p>
            </div>

            @if ($pnpps->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-slate-400">Tidak ada pasien yang cocok dengan filter.</p>
            @else
                <div class="max-h-[340px] overflow-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="sticky top-0 bg-slate-50/95 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400 backdrop-blur">
                            <tr>
                                <th class="w-12 px-5 py-3"></th>
                                <th class="px-5 py-3">Pasien</th>
                                <th class="px-5 py-3">No. WhatsApp</th>
                                <th class="px-5 py-3">Satker</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($pnpps as $p)
                                @php($validWa = \App\Broadcasting\PhoneFormat::toWa($p->no_hp))
                                <tr class="cursor-pointer transition has-[:checked]:bg-sky-50/60 hover:bg-slate-50/60">
                                    <td class="px-5 py-3">
                                        <input type="radio" name="pnpp_id" value="{{ $p->id }}" required
                                               class="h-4 w-4 border-slate-300 text-sky-600 focus:ring-sky-500"
                                               @checked(old('pnpp_id') == $p->id) title="Pilih {{ $p->nama }}">
                                    </td>
                                    <td class="px-5 py-3" onclick="this.parentElement.querySelector('input[type=radio]').click()">
                                        <p class="font-semibold text-slate-800">{{ $p->nama }}</p>
                                        <p class="text-xs text-slate-400">NIP {{ $p->nip ?? '—' }}</p>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="font-mono text-xs {{ $validWa ? 'text-slate-600' : 'text-slate-400' }}">{{ $p->no_hp ?? '—' }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-xs text-slate-500">{{ $p->satker?->nama ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            @error('pnpp_id')<p class="border-t border-slate-100 px-5 py-3 text-xs text-rose-500">{{ $message }}</p>@enderror
        </div>

        {{-- ===== Langkah 2: tanggal + poli repeatable ===== --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-bold text-slate-900">2. Catat Poli yang Dikunjungi</h2>
                <p class="mt-0.5 text-xs text-slate-500">Satu tanggal bisa beberapa poli — keluhan &amp; diagnosa diisi per poli.</p>
            </div>
            <div class="space-y-5 p-5">
                <div class="max-w-xs">
                    <label for="tanggal_kunjungan" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tanggal Kunjungan <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal_kunjungan" id="tanggal_kunjungan" value="{{ old('tanggal_kunjungan', now()->toDateString()) }}" required
                           class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    @error('tanggal_kunjungan')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>

                @include('admin.kunjungan._poli-rows')
            </div>
            <div class="flex items-center justify-end border-t border-slate-100 bg-slate-50/50 px-5 py-4">
                <button type="submit" :disabled="saving"
                        class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-text="saving ? 'Menyimpan...' : 'Simpan Kunjungan'"></span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
