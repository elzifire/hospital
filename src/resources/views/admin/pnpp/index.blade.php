@extends('layouts.app')

@section('title', 'Data PNPP')
@section('page-title', 'Data PNPP')

@section('content')
@php
    $gradients = [
        'from-sky-500 to-indigo-500',
        'from-emerald-500 to-teal-500',
        'from-violet-500 to-purple-500',
        'from-rose-500 to-pink-500',
        'from-amber-500 to-orange-500',
    ];

    $buildUrl = function (array $overrides = []) {
        $params = array_filter(
            array_merge(request()->except('page'), $overrides),
            fn ($v) => $v !== null && $v !== '' && $v !== 'all'
        );

        return route('admin.pnpp.index', $params);
    };

    $cards = [
        ['key' => 'all',   'label' => 'Total PNPP',  'count' => $counts['total'],     'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z', 'color' => 'sky',  'href' => $buildUrl(['jk' => null, 'kronis' => null]), 'active' => ! request('jk') && ! request('kronis')],
        ['key' => 'kronis','label' => 'Penyakit Kronis', 'count' => $counts['kronis'], 'icon' => 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z', 'color' => 'amber', 'href' => $buildUrl(['kronis' => request('kronis') === 'yes' ? null : 'yes']), 'active' => request('kronis') === 'yes'],
    ];

    // dd($counts['total']);
    $cardColor = [
        'sky'   => ['bg' => 'bg-sky-50',   'text' => 'text-sky-600',   'ring' => 'ring-2 ring-sky-500'],
        'rose'  => ['bg' => 'bg-rose-50',  'text' => 'text-rose-600',  'ring' => 'ring-2 ring-rose-500'],
        'amber' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-600', 'ring' => 'ring-2 ring-amber-500'],
    ];
@endphp
<div class="space-y-6">

    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Data PNPP</h2>
            <p class="mt-0.5 text-sm text-slate-500">Master pegawai/pegawai yang menerima layanan kesehatan beserta riwayat kunjungannya.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.master.import', 'pnpp') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                Import
            </a>
            <a href="{{ route('admin.master.export', 'pnpp') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                Export
            </a>
            <a href="{{ route('admin.pnpp.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Tambah PNPP
            </a>
        </div>
    </div>

    {{-- ===== Kartu Statistik (klik untuk filter) ===== --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($cards as $card)
            @php $c = $cardColor[$card['color']]; @endphp
            <a href="{{ $card['href'] }}" title="Klik untuk filter tabel"
               class="group flex items-center gap-3 rounded-xl bg-white px-4 py-3 text-left shadow-xs ring-1 ring-slate-200 transition-all hover:-translate-y-0.5 hover:shadow-md focus:outline-none {{ $card['active'] ? $c['ring'] . ' shadow-md' : '' }}">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg {{ $c['bg'] }} {{ $c['text'] }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" /></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-lg font-extrabold leading-tight tabular-nums text-slate-900">{{ $card['count'] }}</p>
                    <p class="truncate text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ $card['label'] }}</p>
                </div>
            </a>
        @endforeach
    </div>

    {{-- ===== Tabel ===== --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">

        {{-- Toolbar --}}
        <form method="GET" action="{{ route('admin.pnpp.index') }}" class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/60 p-4 lg:flex-row lg:items-center lg:justify-between">
            @if (request('jk'))
                <input type="hidden" name="jk" value="{{ request('jk') }}">
            @endif

            <div class="relative w-full lg:max-w-sm">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="block w-full rounded-xl border-0 bg-white py-2.5 pl-10 pr-9 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition"
                       placeholder="Cari nama, NIP, atau No. BPJS...">
                @if (request('search'))
                    <a href="{{ $buildUrl(['search' => null]) }}" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600" title="Bersihkan pencarian">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </a>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <select name="satker" onchange="this.form.submit()" class="rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                    <option value="">Semua Satker</option>
                    @foreach ($satkers as $satker)
                        <option value="{{ $satker->id }}" @selected(request('satker') == $satker->id)>{{ $satker->nama }}</option>
                    @endforeach
                </select>
                <select name="kronis" onchange="this.form.submit()" class="rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                    <option value="">Semua</option>
                    <option value="yes" @selected(request('kronis') === 'yes')>Ada Penyakit Kronis</option>
                    <option value="no" @selected(request('kronis') === 'no')>Tanpa Penyakit Kronis</option>
                </select>
                <select name="sort" onchange="this.form.submit()" class="rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                    <option value="az" @selected(request('sort', 'az') === 'az')>Nama A–Z</option>
                    <option value="za" @selected(request('sort') === 'za')>Nama Z–A</option>
                    <option value="newest" @selected(request('sort') === 'newest')>Terbaru</option>
                    <option value="oldest" @selected(request('sort') === 'oldest')>Terlama</option>
                </select>
                <select name="per_page" onchange="this.form.submit()" class="rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                    <option value="10" @selected(request('per_page', '10') == '10')>10</option>
                    <option value="25" @selected(request('per_page') == '25')>25</option>
                    <option value="50" @selected(request('per_page') == '50')>50</option>
                </select>
            </div>
        </form>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-left">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                        <th class="px-6 py-3.5 font-semibold">PNPP</th>
                        <th class="px-6 py-3.5 font-semibold">No. BPJS</th>
                        <th class="px-6 py-3.5 font-semibold">Satker</th>
                        <th class="px-6 py-3.5 font-semibold">Usia / JK</th>
                        <th class="px-6 py-3.5 font-semibold">Penyakit Kronis</th>
                        <th class="px-6 py-3.5 font-semibold">Kunjungan</th>
                        <th class="px-6 py-3.5 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($pnpps as $p)
                        @php
                            $gradient = $gradients[abs(crc32($p->nama)) % count($gradients)];
                            $initial  = strtoupper(substr($p->nama, 0, 1));
                        @endphp
                        <tr class="group bg-white transition-colors hover:bg-sky-50/40">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3.5">
                                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-linear-to-br {{ $gradient }} text-sm font-bold uppercase text-white shadow-sm ring-2 ring-white">
                                        {{ $initial }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-900">{{ $p->nama }}</p>
                                        <p class="truncate font-mono text-xs text-slate-400">{{ $p->nip ?? 'NIP —' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="font-mono text-xs font-medium text-slate-600">{{ $p->no_bpjs ?? '—' }}</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if ($p->satker)
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18" /></svg>
                                        <span>{{ $p->satker->nama }}</span>
                                    </span>
                                @else
                                    <span class="text-xs italic text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold tabular-nums text-slate-700">{{ $p->usia !== null ? $p->usia . ' th' : '—' }}</span>
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 ring-inset {{ $p->jenis_kelamin === 'L' ? 'bg-sky-50 text-sky-700 ring-sky-600/20' : ($p->jenis_kelamin === 'P' ? 'bg-rose-50 text-rose-700 ring-rose-600/20' : 'bg-slate-100 text-slate-500 ring-slate-300') }}">
                                        {{ $p->jenis_kelamin === 'L' ? 'L' : ($p->jenis_kelamin === 'P' ? 'P' : '—') }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if ($p->penyakit->isNotEmpty())
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($p->penyakit as $penyakit)
                                            <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 ring-1 ring-amber-200/70">{{ $penyakit->nama }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">Sehat / —</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-bold tabular-nums text-sky-700 ring-1 ring-sky-200/70">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                                        <span>{{ $p->kunjungans_count }}</span>
                                    </span>
                                    <span class="text-xs text-slate-400">{{ $p->tanggal_terakhir_berobat ? 'Terakhir ' . $p->tanggal_terakhir_berobat->translatedFormat('d M Y') : 'Belum ada' }}</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5 opacity-60 transition-opacity group-hover:opacity-100 lg:opacity-0">
                                    <a href="{{ route('admin.pnpp.kunjungan', $p) }}" title="Riwayat Kunjungan"
                                       class="rounded-lg p-2 text-slate-400 transition-all hover:bg-emerald-50 hover:text-emerald-600 focus:opacity-100">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                    </a>
                                    <a href="{{ route('admin.pnpp.edit', $p) }}" title="Edit PNPP"
                                       class="rounded-lg p-2 text-slate-400 transition-all hover:bg-sky-50 hover:text-sky-600 focus:opacity-100">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                    </a>
                                    <button type="button" title="Hapus PNPP"
                                            data-url="{{ route('admin.pnpp.destroy', $p) }}"
                                            data-nama="{{ $p->nama }}"
                                            data-kunjungan="{{ $p->kunjungans_count }}"
                                            onclick="hapusPnpp(this)"
                                            class="rounded-lg p-2 text-slate-400 transition-all hover:bg-rose-50 hover:text-rose-600 focus:opacity-100">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 ring-8 ring-slate-50">
                                        <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z" /></svg>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-900">Tidak ada data PNPP</h3>
                                    <p class="mt-1 text-sm text-slate-500">Ubah kata kunci atau reset filter, atau tambah data baru.</p>
                                    <a href="{{ route('admin.pnpp.index') }}" class="mt-5 rounded-lg bg-slate-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-700">Reset Filter</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($pnpps->total() > 0)
            <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:flex-row">
                <p class="text-xs font-medium text-slate-500">
                    Menampilkan <span class="font-bold text-slate-800">{{ $pnpps->firstItem() }}</span>–<span class="font-bold text-slate-800">{{ $pnpps->lastItem() }}</span>
                    dari <span class="font-bold text-slate-800">{{ $pnpps->total() }}</span> PNPP
                </p>
                {{ $pnpps->links() }}
            </div>
        @endif
    </div>

    {{-- Info strip --}}
    <div class="flex items-start gap-3 rounded-xl bg-sky-50/70 p-4 text-xs leading-relaxed text-sky-800 ring-1 ring-sky-100">
        <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-sky-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
        </svg>
        <p><strong>Tips:</strong> Usia dihitung otomatis dari tanggal lahir, dan tanggal terakhir berobat diambil dari kunjungan terbaru. Klik kartu statistik untuk memfilter tabel.</p>
    </div>
</div>

<script>
    function hapusPnpp(el) {
        const nama = el.dataset.nama;
        const kunjungan = parseInt(el.dataset.kunjungan || '0', 10);
        const warning = kunjungan > 0
            ? `<div class="flex items-start gap-2.5 rounded-xl bg-amber-50 p-3.5 mt-3 ring-1 ring-amber-200 text-left">
                   <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                   <p class="text-sm font-medium text-amber-800"><strong>${kunjungan} riwayat kunjungan</strong> akan ikut terhapus!</p>
               </div>`
            : '';

        confirmSubmit(el.dataset.url, {
            title: 'Hapus Data PNPP?',
            html: `<div class="text-left text-sm mt-2">
                       <p class="text-slate-600">Apakah Anda yakin ingin menghapus data <strong class="text-slate-900">${nama}</strong>?</p>
                       ${warning}
                       <p class="mt-3 rounded-lg bg-slate-50 p-2.5 text-xs text-slate-500 ring-1 ring-slate-200">Tindakan ini permanen dan tidak dapat dibatalkan.</p>
                   </div>`,
            confirmText: 'Ya, hapus',
        });
    }
</script>
@endsection
