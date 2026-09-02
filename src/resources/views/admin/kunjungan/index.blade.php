@extends('layouts.app')

@section('title', 'Kunjungan')
@section('page-title', 'Kunjungan')

@section('content')
@php
    $stats = [
        ['label' => 'Total Kunjungan',  'value' => '1.284', 'delta' => '+12%', 'tone' => 'sky',     'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5'],
        ['label' => 'Hari Ini',        'value' => '42',    'delta' => '+5',   'tone' => 'emerald', 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['label' => 'Bulan Ini',       'value' => '312',   'delta' => '+8%',  'tone' => 'violet',  'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5'],
        ['label' => 'Perlu Follow Up', 'value' => '18',    'delta' => '3',    'tone' => 'amber',   'icon' => 'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z'],
    ];

    $toneColor = [
        'sky'     => ['bg' => 'bg-sky-50',     'text' => 'text-sky-600'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
        'violet'  => ['bg' => 'bg-violet-50',  'text' => 'text-violet-600'],
        'amber'   => ['bg' => 'bg-amber-50',   'text' => 'text-amber-600'],
    ];

    $tabs = [
        ['label' => 'Semua',         'count' => 1284, 'active' => true],
        ['label' => 'Hari Ini',      'count' => 42,   'active' => false],
        ['label' => 'Dalam Proses',  'count' => 8,    'active' => false],
        ['label' => 'Selesai',       'count' => 1226, 'active' => false],
    ];

    $rows = [
        ['waktu' => '08:12', 'nama' => 'Budi Santoso', 'nip' => '198501012010011001', 'satker' => 'Dinas Kesehatan',       'poli' => 'Poli Umum',          'dokter' => 'dr. Rina Pratiwi',    'diagnosa' => 'Hipertensi derajat 1', 'status' => 'Selesai'],
        ['waktu' => '09:40', 'nama' => 'Siti Aminah',  'nip' => '199003152015122002', 'satker' => 'BPJS Kesehatan',       'poli' => 'Poli Gigi',          'dokter' => 'drg. Andi Saputra',   'diagnosa' => 'Gigi berlubang',       'status' => 'Dalam Proses'],
        ['waktu' => '10:05', 'nama' => 'Agus Wijaya',  'nip' => '197812302008121003', 'satker' => 'Kementerian Kesehatan','poli' => 'Poli Penyakit Dalam', 'dokter' => 'dr. Bambang Haryanto','diagnosa' => 'Angina pektoris',      'status' => 'Selesai'],
        ['waktu' => '11:20', 'nama' => 'Dewi Lestari', 'nip' => '198802102010042004', 'satker' => 'Dinas Pendidikan',     'poli' => 'Poli Anak',          'dokter' => 'dr. Sari Wulandari',  'diagnosa' => 'ISPA',                 'status' => 'Dalam Proses'],
        ['waktu' => '13:00', 'nama' => 'Rudi Hartono', 'nip' => '199105052016051005', 'satker' => 'Dinas Kesehatan',      'poli' => 'Poli Umum',          'dokter' => 'dr. Rina Pratiwi',    'diagnosa' => 'Kontrol rutin',        'status' => 'Terjadwal'],
        ['waktu' => '14:30', 'nama' => 'Lina Marlina', 'nip' => '199311122017112006', 'satker' => 'BPJS Kesehatan',       'poli' => 'Poli Umum',          'dokter' => 'dr. Rina Pratiwi',    'diagnosa' => 'Asam urat',            'status' => 'Terjadwal'],
    ];

    $statusStyle = [
        'Selesai'      => 'bg-emerald-50 text-emerald-700 ring-emerald-200/70',
        'Dalam Proses' => 'bg-sky-50 text-sky-700 ring-sky-200/70',
        'Terjadwal'    => 'bg-amber-50 text-amber-700 ring-amber-200/70',
    ];

    $antrian = [
        ['jam' => '15:00', 'nama' => 'Tono Sutrisno', 'poli' => 'Poli Umum', 'status' => 'Menunggu'],
        ['jam' => '15:30', 'nama' => 'Maya Putri',    'poli' => 'Poli Gigi',  'status' => 'Menunggu'],
        ['jam' => '16:00', 'nama' => 'Bambang Eko',   'poli' => 'Poli Penyakit Dalam', 'status' => 'Menunggu'],
        ['jam' => '16:30', 'nama' => 'Sari Wulandari','poli' => 'Poli Anak',  'status' => 'Menunggu'],
    ];
@endphp
<div class="space-y-6">

    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Kunjungan</h2>
            <p class="mt-0.5 text-sm text-slate-500">Pantau seluruh kunjungan berobat PNPP secara real-time.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="#" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                Ekspor
            </a>
            <a href="#" class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Tambah Kunjungan
            </a>
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
                    <div class="flex items-baseline gap-1.5">
                        <p class="text-lg font-extrabold leading-tight tabular-nums text-slate-900">{{ $s['value'] }}</p>
                        <span class="text-[11px] font-bold text-emerald-600">{{ $s['delta'] }}</span>
                    </div>
                    <p class="truncate text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ $s['label'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ===== Tabel Kunjungan ===== --}}
        <div class="lg:col-span-2">
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">

                {{-- Tabs --}}
                <div class="flex gap-1 border-b border-slate-100 bg-slate-50/60 px-4 pt-4">
                    @foreach ($tabs as $tab)
                        <button class="inline-flex items-center gap-2 rounded-t-lg border-b-2 px-4 py-2.5 text-sm font-semibold transition {{ $tab['active'] ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                            {{ $tab['label'] }}
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold tabular-nums text-slate-500">{{ $tab['count'] }}</span>
                        </button>
                    @endforeach
                </div>

                {{-- Toolbar --}}
                <div class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/60 p-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="relative w-full lg:max-w-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                        </div>
                        <input type="text" placeholder="Cari nama, NIP, atau no. rekam medis..."
                               class="block w-full rounded-xl border-0 bg-white py-2.5 pl-10 pr-9 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition">
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <input type="date" class="rounded-lg border-0 bg-white py-2 px-3 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500">
                        <span class="text-xs text-slate-400">s/d</span>
                        <input type="date" class="rounded-lg border-0 bg-white py-2 px-3 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500">
                        <select class="rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                            <option>Semua Poli</option>
                            <option>Poli Umum</option>
                            <option>Poli Gigi</option>
                            <option>Poli Penyakit Dalam</option>
                            <option>Poli Anak</option>
                        </select>
                    </div>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                                <th class="px-6 py-3.5 font-semibold">Waktu</th>
                                <th class="px-6 py-3.5 font-semibold">Pasien (PNPP)</th>
                                <th class="px-6 py-3.5 font-semibold">Poli / Dokter</th>
                                <th class="px-6 py-3.5 font-semibold">Diagnosa</th>
                                <th class="px-6 py-3.5 font-semibold">Status</th>
                                <th class="px-6 py-3.5 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach ($rows as $r)
                                <tr class="group bg-white transition-colors hover:bg-sky-50/40">
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="text-xs font-semibold text-slate-500">{{ $r['waktu'] }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-sky-100 text-xs font-bold uppercase text-sky-700">
                                                {{ substr($r['nama'], 0, 1) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-bold text-slate-900">{{ $r['nama'] }}</p>
                                                <p class="truncate font-mono text-xs text-slate-400">{{ $r['nip'] }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-semibold text-slate-700">{{ $r['poli'] }}</p>
                                        <p class="text-xs text-slate-400">{{ $r['dokter'] }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-slate-600">{{ $r['diagnosa'] }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $statusStyle[$r['status']] }}">
                                            {{ $r['status'] }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5 opacity-60 transition-opacity group-hover:opacity-100 lg:opacity-0">
                                            <a href="#" title="Detail" class="rounded-lg p-2 text-slate-400 transition-all hover:bg-sky-50 hover:text-sky-600 focus:opacity-100">
                                                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                            </a>
                                            <a href="#" title="Follow Up" class="rounded-lg p-2 text-slate-400 transition-all hover:bg-emerald-50 hover:text-emerald-600 focus:opacity-100">
                                                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" /></svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination (placeholder) --}}
                <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:flex-row">
                    <p class="text-xs font-medium text-slate-500">
                        Menampilkan <span class="font-bold text-slate-800">1</span>–<span class="font-bold text-slate-800">6</span>
                        dari <span class="font-bold text-slate-800">1.284</span> kunjungan
                    </p>
                    <div class="flex items-center gap-1">
                        <button class="rounded-lg bg-white p-2 text-slate-500 shadow-xs ring-1 ring-slate-200 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40" disabled>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                        </button>
                        <button class="h-8 min-w-8 rounded-lg bg-sky-600 px-2 text-xs font-bold tabular-nums text-white shadow-sm">1</button>
                        <button class="h-8 min-w-8 rounded-lg bg-white px-2 text-xs font-bold tabular-nums text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">2</button>
                        <button class="h-8 min-w-8 rounded-lg bg-white px-2 text-xs font-bold tabular-nums text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">3</button>
                        <span class="px-1 text-xs font-bold text-slate-400">…</span>
                        <button class="h-8 min-w-8 rounded-lg bg-white px-2 text-xs font-bold tabular-nums text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">214</button>
                        <button class="rounded-lg bg-white p-2 text-slate-500 shadow-xs ring-1 ring-slate-200 transition hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Sidebar ===== --}}
        <div class="space-y-6">
            {{-- Antrian Hari Ini --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-bold text-slate-900">Antrian Hari Ini</h3>
                    <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-bold tabular-nums text-sky-700 ring-1 ring-sky-200/70">4</span>
                </div>
                <ul class="divide-y divide-slate-50">
                    @foreach ($antrian as $a)
                        <li class="flex items-center gap-3 px-5 py-3.5 transition-colors hover:bg-sky-50/30">
                            <span class="w-10 flex-shrink-0 rounded-lg bg-slate-100 py-1 text-center text-xs font-bold tabular-nums text-slate-600">{{ $a['jam'] }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-slate-900">{{ $a['nama'] }}</p>
                                <p class="truncate text-xs text-slate-400">{{ $a['poli'] }}</p>
                            </div>
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-amber-600">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>{{ $a['status'] }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Aktivitas Terbaru --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-bold text-slate-900">Aktivitas Terbaru</h3>
                </div>
                <ul class="divide-y divide-slate-50">
                    <li class="flex items-start gap-3 px-5 py-3.5">
                        <span class="mt-1 h-2 w-2 flex-shrink-0 rounded-full bg-emerald-500"></span>
                        <div class="min-w-0">
                            <p class="text-sm text-slate-600">Kunjungan <strong class="text-slate-900">Budi Santoso</strong> selesai.</p>
                            <p class="text-xs text-slate-400">08:45 · Poli Umum</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3 px-5 py-3.5">
                        <span class="mt-1 h-2 w-2 flex-shrink-0 rounded-full bg-sky-500"></span>
                        <div class="min-w-0">
                            <p class="text-sm text-slate-600"><strong class="text-slate-900">Siti Aminah</strong> mulai diperiksa.</p>
                            <p class="text-xs text-slate-400">09:40 · Poli Gigi</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3 px-5 py-3.5">
                        <span class="mt-1 h-2 w-2 flex-shrink-0 rounded-full bg-amber-500"></span>
                        <div class="min-w-0">
                            <p class="text-sm text-slate-600">Janji baru untuk <strong class="text-slate-900">Lina Marlina</strong>.</p>
                            <p class="text-xs text-slate-400">10:12 · Terjadwal</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
