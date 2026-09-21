@extends('layouts.app')

@section('title', 'Riwayat Kunjungan — ' . $pnpp->nama)
@section('page-title', 'Riwayat Kunjungan')

@section('content')
@php
    $kelompok = $pnpp->kunjungans->groupBy(fn ($k) => $k->tanggal_kunjungan->format('Y-m-d'));
    $adaFilter = (bool) ($filters['dari'] || $filters['sampai'] || $filters['poli']);
    $jumlahHomeVisit = $pnpp->kunjungans->where('home_visit', true)->count();
    $jumlahPoli = $pnpp->kunjungans->whereNotNull('poli_id')->pluck('poli_id')->unique()->count();

    $stats = [
        ['label' => 'Total Catatan',   'value' => number_format($pnpp->kunjungans->count()), 'sub' => $adaFilter ? 'sesuai filter' : 'baris kunjungan', 'tone' => 'sky',     'icon' => 'M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z'],
        ['label' => 'Hari Kunjungan',  'value' => number_format($kelompok->count()),         'sub' => 'tanggal berbeda',          'tone' => 'violet',  'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5'],
        ['label' => 'Poli Dituju',     'value' => number_format($jumlahPoli),                'sub' => 'poli berbeda',             'tone' => 'emerald', 'icon' => 'M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72L4.318 3.44A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72m-13.5 8.65h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z'],
        ['label' => 'Home Visit',      'value' => number_format($jumlahHomeVisit),           'sub' => 'kunjungan ke rumah',       'tone' => 'teal',    'icon' => 'm2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75'],
    ];

    $toneColor = [
        'sky'     => ['bg' => 'bg-sky-50',     'text' => 'text-sky-600'],
        'violet'  => ['bg' => 'bg-violet-50',  'text' => 'text-violet-600'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
        'teal'    => ['bg' => 'bg-teal-50',    'text' => 'text-teal-600'],
    ];
@endphp

<div class="mx-auto max-w-7xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            @can('manage pnpp')
                <a href="{{ route('admin.pnpp.index') }}" class="rounded transition hover:text-sky-600">Data PNPP</a>
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                <a href="{{ route('admin.pnpp.edit', $pnpp) }}" class="rounded transition hover:text-sky-600">{{ $pnpp->nama }}</a>
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            @endcan
            <span class="font-semibold text-slate-600">Riwayat Kunjungan</span>
        </nav>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-slate-900">Riwayat Kunjungan</h2>
                <p class="mt-0.5 text-sm text-slate-500">Catatan berobat untuk <strong>{{ $pnpp->nama }}</strong></p>
            </div>
            @can('manage pnpp')
                <a href="{{ route('admin.pnpp.edit', $pnpp) }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                    Edit Data PNPP
                </a>
            @endcan
        </div>
    </div>

    {{-- ===== Kartu Statistik ===== --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($stats as $s)
            @php $c = $toneColor[$s['tone']]; @endphp
            <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-xs ring-1 ring-slate-200">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg {{ $c['bg'] }} {{ $c['text'] }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $s['icon'] }}" /></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-lg font-extrabold leading-tight tabular-nums text-slate-900">{{ $s['value'] }}</p>
                    <p class="truncate text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ $s['label'] }} · <span class="normal-case">{{ $s['sub'] }}</span></p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        {{-- ===== Ringkasan PNPP ===== --}}
        <div class="xl:col-span-1">
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="h-16 bg-linear-to-r from-sky-600 via-indigo-600 to-violet-600"></div>
                <div class="-mt-8 flex flex-col items-center px-5 pb-5 text-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-linear-to-br from-sky-500 to-indigo-500 text-2xl font-extrabold uppercase text-white shadow-lg ring-4 ring-white">
                        {{ substr($pnpp->nama, 0, 1) }}
                    </div>
                    <h3 class="mt-3 text-lg font-bold text-slate-900">{{ $pnpp->nama }}</h3>
                    <p class="font-mono text-xs text-slate-400">{{ $pnpp->nip ?? 'NIP —' }}</p>

                    <div class="mt-2.5 flex flex-wrap items-center justify-center gap-1.5">
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold capitalize ring-1 ring-inset {{ $pnpp->jenis_kelamin === 'L' ? 'bg-sky-50 text-sky-700 ring-sky-600/20' : ($pnpp->jenis_kelamin === 'P' ? 'bg-rose-50 text-rose-700 ring-rose-600/20' : 'bg-slate-100 text-slate-500 ring-slate-300') }}">
                            {{ $pnpp->jenis_kelamin === 'L' ? 'Laki-laki' : ($pnpp->jenis_kelamin === 'P' ? 'Perempuan' : '—') }}
                        </span>
                        @if ($pnpp->usia !== null)
                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">{{ $pnpp->usia }} tahun</span>
                        @endif
                    </div>

                    <dl class="mt-5 w-full space-y-2.5 border-t border-slate-100 pt-4 text-left text-xs">
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-400">No. BPJS</dt>
                            <dd class="truncate font-mono font-semibold text-slate-700">{{ $pnpp->no_bpjs ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-400">Satker</dt>
                            <dd class="truncate font-semibold text-slate-700">{{ $pnpp->satker?->nama ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-400">No. HP</dt>
                            <dd class="truncate font-semibold text-slate-700">{{ $pnpp->no_hp ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-400">Tanggal Lahir</dt>
                            <dd class="truncate font-semibold text-slate-700">{{ $pnpp->tanggal_lahir?->translatedFormat('d M Y') ?? '—' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-4 w-full border-t border-slate-100 pt-4 text-left">
                        <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Penyakit Kronis</p>
                        @if ($pnpp->penyakit->isEmpty())
                            <p class="text-xs italic text-slate-400">Tidak ada / sehat</p>
                        @else
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($pnpp->penyakit as $penyakit)
                                    <span class="rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 ring-1 ring-amber-200/70">{{ $penyakit->nama }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if ($kelompok->isNotEmpty())
                        <div class="mt-4 w-full rounded-xl bg-slate-50 px-4 py-3 text-left ring-1 ring-slate-100">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Kunjungan Terakhir</p>
                            <p class="mt-0.5 text-sm font-bold text-slate-800">{{ $pnpp->kunjungans->first()->tanggal_kunjungan->translatedFormat('d F Y') }}</p>
                            <p class="text-xs text-slate-500">{{ $pnpp->kunjungans->first()->tanggal_kunjungan->diffForHumans() }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===== Form + Tabel Riwayat ===== --}}
        <div class="space-y-6 xl:col-span-2">
            {{-- Form tambah kunjungan (multi-poli) --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 p-6">
                    <h3 class="text-base font-bold text-slate-900">Tambah Kunjungan</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Catat tanggal lalu isi tiap poli yang dikunjungi — keluhan &amp; diagnosa per poli.</p>
                </div>
                <form action="{{ route('admin.pnpp.kunjungan.store', $pnpp) }}" method="POST" x-data="{ saving: false }" @submit="saving = true">
                    @csrf
                    <div class="space-y-5 p-6">
                        <div class="max-w-xs">
                            <label for="tanggal_kunjungan" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Tanggal Kunjungan <span class="text-rose-500">*</span></label>
                            <input type="date" name="tanggal_kunjungan" id="tanggal_kunjungan" value="{{ old('tanggal_kunjungan', now()->toDateString()) }}" required
                                   class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 transition focus:ring-2 focus:ring-inset focus:ring-sky-500 @error('tanggal_kunjungan') ring-rose-300 focus:ring-rose-500 @enderror">
                            @error('tanggal_kunjungan')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        @include('admin.kunjungan._poli-rows')
                    </div>
                    <div class="flex items-center justify-end border-t border-slate-100 bg-slate-50/70 p-4">
                        <button type="submit" :disabled="saving"
                                class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                            <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="saving ? 'Menyimpan...' : 'Tambah Kunjungan'"></span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Tabel riwayat kunjungan --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-6 py-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Daftar Kunjungan</h3>
                        <p class="mt-0.5 text-xs text-slate-500">Satu baris = satu catatan poli. Baris dengan tanggal sama dikelompokkan.</p>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-bold tabular-nums text-sky-700 ring-1 ring-sky-200/70">
                        {{ $pnpp->kunjungans->count() }} catatan
                    </span>
                </div>

                {{-- Filter rentang tanggal & poli --}}
                <form method="GET" action="{{ request()->url() }}"
                      class="flex flex-wrap items-end gap-3 border-b border-slate-100 bg-slate-50/70 px-6 py-4">
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Dari</label>
                        <input type="date" name="dari" value="{{ $filters['dari'] }}"
                               class="h-10 rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Sampai</label>
                        <input type="date" name="sampai" value="{{ $filters['sampai'] }}"
                               class="h-10 rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Poli</label>
                        @if ($batasiPoli)
                            <span class="inline-flex h-10 items-center gap-1.5 rounded-lg bg-violet-50 px-3 text-sm font-semibold text-violet-700 ring-1 ring-violet-200/70">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                <span class="truncate">{{ $polis->first()?->nama }}</span>
                            </span>
                        @else
                            <select name="poli" class="h-10 rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                <option value="">Semua poli</option>
                                @foreach ($polis as $po)
                                    <option value="{{ $po->id }}" {{ (int) $filters['poli'] === (int) $po->id ? 'selected' : '' }}>{{ $po->nama }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="h-10 rounded-lg bg-sky-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">Terapkan</button>
                        <a href="{{ request()->url() }}" class="h-10 rounded-lg bg-slate-200 px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-300">Reset</a>
                    </div>
                    @if ($adaFilter)
                        <span class="mb-2.5 inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700 ring-1 ring-amber-200/70">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" /></svg>
                            Filter aktif
                        </span>
                    @endif
                </form>

                @if ($pnpp->kunjungans->isEmpty())
                    <div class="px-6 py-16 text-center">
                        <div class="mx-auto flex max-w-sm flex-col items-center">
                            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 ring-8 ring-slate-50">
                                <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-900">{{ $adaFilter ? 'Tidak ada kunjungan sesuai filter' : 'Belum ada riwayat kunjungan' }}</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                @if ($adaFilter)
                                    Ubah rentang tanggal atau pilihan poli, atau tekan Reset.
                                @else
                                    Tambahkan kunjungan pertama lewat formulir di atas.
                                @endif
                            </p>
                        </div>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                <tr>
                                    <th class="px-4 py-3">No</th>
                                    <th class="px-4 py-3">Tanggal</th>
                                    <th class="px-4 py-3">Poli / Jenis</th>
                                    <th class="px-4 py-3">Keluhan</th>
                                    <th class="px-4 py-3">Diagnosa</th>
                                    <th class="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @php $no = 0; @endphp
                                @foreach ($kelompok as $tanggal => $baris)
                                    @foreach ($baris->values() as $i => $kunjungan)
                                        @php $no++; @endphp
                                        <tr class="align-top transition hover:bg-slate-50/60 {{ $i > 0 ? 'border-t border-dashed border-slate-100' : '' }}">
                                            <td class="px-4 py-3 text-center text-xs font-semibold tabular-nums text-slate-400">{{ $no }}</td>
                                            @if ($i === 0)
                                                <td rowspan="{{ $baris->count() }}" class="border-r border-slate-100 px-4 py-3 whitespace-nowrap {{ $baris->count() > 1 ? 'align-middle bg-slate-50/40' : '' }}">
                                                    <p class="font-bold text-slate-800">{{ $kunjungan->tanggal_kunjungan->translatedFormat('d M Y') }}</p>
                                                    <p class="text-xs text-slate-400">{{ $kunjungan->tanggal_kunjungan->translatedFormat('l') }}</p>
                                                    <div class="mt-1.5 flex flex-wrap gap-1">
                                                        @if ($loop->parent->first)
                                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-700 ring-1 ring-emerald-200/70">Terbaru</span>
                                                        @endif
                                                        @if ($baris->count() > 1)
                                                            <span class="inline-flex items-center rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-bold uppercase text-sky-700 ring-1 ring-sky-200/70">{{ $baris->count() }} poli</span>
                                                        @endif
                                                    </div>
                                                </td>
                                            @endif
                                            <td class="px-4 py-3">
                                                @if ($kunjungan->home_visit)
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-2.5 py-1 text-[11px] font-semibold text-teal-700 ring-1 ring-inset ring-teal-200/70">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" /></svg>
                                                        Home Visit
                                                    </span>
                                                @elseif ($kunjungan->poli)
                                                    <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-semibold text-sky-700 ring-1 ring-inset ring-sky-200/70">
                                                        {{ $kunjungan->poli->nama }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-500 ring-1 ring-inset ring-slate-200/70">Tanpa poli</span>
                                                @endif
                                            </td>
                                            <td class="max-w-[220px] px-4 py-3 text-slate-600">{{ $kunjungan->keluhan ?: '—' }}</td>
                                            <td class="max-w-[220px] px-4 py-3 text-slate-600">{{ $kunjungan->diagnosa ?: '—' }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <a href="{{ route('admin.pnpp.kunjungan.edit', [$pnpp, $kunjungan]) }}"
                                                   class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                                                   title="Edit kunjungan {{ $kunjungan->tanggal_kunjungan->translatedFormat('d M Y') }}">
                                                    <span class="sr-only">Edit kunjungan</span>
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 bg-slate-50/70 px-6 py-3 text-xs text-slate-500">
                        <span>Menampilkan <strong class="tabular-nums text-slate-700">{{ $no }}</strong> catatan dari <strong class="tabular-nums text-slate-700">{{ $kelompok->count() }}</strong> hari kunjungan.</span>
                        @if ($adaFilter)
                            <a href="{{ request()->url() }}" class="font-semibold text-sky-600 transition hover:text-sky-700">Lihat semua riwayat →</a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
