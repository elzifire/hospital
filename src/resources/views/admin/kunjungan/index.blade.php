@extends('layouts.app')

@section('title', 'Kunjungan')
@section('page-title', 'Kunjungan')

@section('content')
@php
    $stats = [
        ['label' => 'Total Kunjungan',     'value' => number_format($total),    'tone' => 'sky',     'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5'],
        ['label' => 'Hari Ini',            'value' => number_format($hariIni),  'tone' => 'emerald', 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['label' => 'Bulan Ini',           'value' => number_format($bulanIni), 'tone' => 'violet',  'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5'],
        ['label' => 'Realisasi Reminder',  'value' => number_format($realisasi), 'tone' => 'teal',   'icon' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z'],
        ['label' => 'PNPP Dilayani',       'value' => number_format($pasien),   'tone' => 'rose',    'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
    ];

    $toneColor = [
        'sky'     => ['bg' => 'bg-sky-50',     'text' => 'text-sky-600'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
        'violet'  => ['bg' => 'bg-violet-50',  'text' => 'text-violet-600'],
        'teal'    => ['bg' => 'bg-teal-50',    'text' => 'text-teal-600'],
        'rose'    => ['bg' => 'bg-rose-50',    'text' => 'text-rose-600'],
    ];

    $periodeLabel = ['hari-ini' => 'Hari Ini', '7-hari' => '7 Hari', '30-hari' => '30 Hari'];
@endphp

<div class="space-y-6">
    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Kunjungan</h2>
            <p class="mt-0.5 text-sm text-slate-500">Realisasi kunjungan pasien — dari penjadwalan Digital Reminder maupun catatan manual; satu pasien bisa ke beberapa poli dalam satu tanggal.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.monitoring.report.show', 'kunjungan') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                Ekspor
            </a>
            <a href="{{ route('admin.kunjungan.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Tambah Kunjungan
            </a>
        </div>
    </div>

    {{-- ===== Kartu Statistik ===== --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        @foreach ($stats as $s)
            @php($c = $toneColor[$s['tone']])
            <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-xs ring-1 ring-slate-200">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg {{ $c['bg'] }} {{ $c['text'] }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $s['icon'] }}" /></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-lg font-extrabold leading-tight tabular-nums text-slate-900">{{ $s['value'] }}</p>
                    <p class="truncate text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ $s['label'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== Daftar kunjungan + filter ===== --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-bold text-slate-900">Daftar Kunjungan</h3>
            <p class="mt-0.5 text-xs text-slate-500">Satu baris = satu pasien pada satu tanggal, beserta semua poli yang dikunjunginya.</p>
        </div>

        <form method="GET" action="{{ route('admin.kunjungan.index') }}"
              class="flex flex-wrap items-end gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4">
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Nama / NIP / no. BPJS…"
                       class="w-56 rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Poli</label>
                <select name="poli" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    <option value="">Semua</option>
                    @foreach ($polis as $po)
                        <option value="{{ $po->id }}" {{ $filters['poli'] == $po->id ? 'selected' : '' }}>{{ $po->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Periode</label>
                <select name="periode" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    <option value="">Semua</option>
                    @foreach ($periodeLabel as $key => $label)
                        <option value="{{ $key }}" {{ $filters['periode'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Dari</label>
                <input type="date" name="dari" value="{{ $filters['dari'] }}"
                       class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Sampai</label>
                <input type="date" name="sampai" value="{{ $filters['sampai'] }}"
                       class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">Filter</button>
                <a href="{{ route('admin.kunjungan.index') }}" class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-300">Reset</a>
            </div>
        </form>

        @if ($kunjungan->isEmpty())
            <div class="px-5 py-12 text-center">
                <p class="text-sm font-medium text-slate-500">Belum ada kunjungan tercatat.</p>
                <a href="{{ route('admin.kunjungan.create') }}" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">
                    Catat kunjungan pertama
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-5 py-3">Tanggal</th>
                            <th class="px-5 py-3">Pasien</th>
                            <th class="px-5 py-3">Poli Dikunjungi</th>
                            {{-- <th class="px-5 py-3">Sumber</th> --}}
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($kunjungan as $grup)
                            @php($pasien = $grup->first()->pnpp)
                            @php($viaReminder = $grup->contains(fn ($k) => $k->reminder_id !== null))
                            <tr class="hover:bg-slate-50/60">
                                <td class="whitespace-nowrap px-5 py-3">
                                    <p class="font-semibold text-slate-800">{{ $grup->first()->tanggal_kunjungan?->translatedFormat('d M Y') }}</p>
                                    <p class="text-xs text-slate-400">{{ $grup->count() }} poli dikunjungi</p>
                                </td>
                                <td class="px-5 py-3">
                                    <p class="font-semibold text-slate-800">{{ $pasien->nama ?? '—' }}</p>
                                    <p class="text-xs text-slate-400">NIP {{ $pasien->nip ?? '—' }} · {{ $pasien->satker?->nama ?? '—' }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($grup as $k)
                                            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $k->poli_id === null ? 'bg-slate-100 text-slate-500 ring-slate-200/70' : 'bg-sky-50 text-sky-700 ring-sky-200/70' }}">
                                                {{ $k->poli?->nama ?? 'Tanpa poli' }}
                                            </span>
                                        @endforeach
                                    </div>
                                    @if ($grup->contains(fn ($k) => $k->diagnosa))
                                        <p class="mt-1 max-w-xs truncate text-xs text-slate-400">
                                            {{ $grup->filter(fn ($k) => $k->diagnosa)->implode('diagnosa', ' · ') }}
                                        </p>
                                    @endif
                                </td>
                                {{-- <td class="whitespace-nowrap px-5 py-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $viaReminder ? 'bg-emerald-50 text-emerald-700 ring-emerald-200/70' : 'bg-slate-100 text-slate-500 ring-slate-200/70' }}">
                                        {{ $viaReminder ? 'Realisasi Reminder' : 'Manual' }}
                                    </span>
                                </td> --}}
                                <td class="whitespace-nowrap px-5 py-3 text-right">
                                    <a href="{{ route('admin.pnpp.kunjungan', $pasien) }}"
                                       class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-200">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-3">
                {{ $kunjungan->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
