@extends('layouts.app')

@section('title', 'Laporan ' . $config['label'])
@section('page-title', 'Monitoring')

@section('content')
@php
    // Peta warna ubin ikon & badge (mengikuti tone di MonitoringRegistry).
    $tileTone = [
        'sky'     => 'bg-sky-50 text-sky-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'violet'  => 'bg-violet-50 text-violet-600',
        'amber'   => 'bg-amber-50 text-amber-600',
        'rose'    => 'bg-rose-50 text-rose-600',
        'slate'   => 'bg-slate-100 text-slate-500',
        'indigo'  => 'bg-indigo-50 text-indigo-600',
        'teal'    => 'bg-teal-50 text-teal-600',
    ];

    $badgeTone = [
        'sky'     => 'bg-sky-50 text-sky-700 ring-sky-200/70',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200/70',
        'violet'  => 'bg-violet-50 text-violet-700 ring-violet-200/70',
        'amber'   => 'bg-amber-50 text-amber-700 ring-amber-200/70',
        'rose'    => 'bg-rose-50 text-rose-700 ring-rose-200/70',
        'slate'   => 'bg-slate-100 text-slate-500 ring-slate-200/70',
        'indigo'  => 'bg-indigo-50 text-indigo-700 ring-indigo-200/70',
        'teal'    => 'bg-teal-50 text-teal-700 ring-teal-200/70',
    ];

    $tile     = $tileTone[$config['tone']] ?? $tileTone['slate'];
    $formatN  = fn ($n) => is_int($n) || is_float($n) ? number_format($n, 0, ',', '.') : $n;

    // URL export: bawa seluruh query aktif (search, filter, sort) minus page.
    $exportQuery = fn (string $format) => route('admin.monitoring.report.export', $entity)
        . '?' . http_build_query(array_merge(request()->except('page'), ['format' => $format]));

    // URL tanpa parameter pencarian (tombol X di kotak search).
    $noSearch = collect(request()->except('search', 'page'))->filter(fn ($v) => $v !== null && $v !== '')->all();
    $noSearchUrl = route('admin.monitoring.report.show', $entity) . (count($noSearch) ? '?' . http_build_query($noSearch) : '');

    // Ada filter/pencarian aktif? (untuk tombol reset)
    $filterKeys = collect($config['filters'] ?? [])->pluck('key')->all();
    $hasActive  = trim((string) request('search')) !== ''
        || collect($filterKeys)->contains(fn ($k) => trim((string) request($k)) !== '');

    // Sort aktif (validasi ulang di sisi view).
    $currentSort = (string) request('sort', '');
    if (! isset($config['sorts'][$currentSort])) {
        $currentSort = $config['defaultSort'] ?? array_key_first($config['sorts'] ?? []);
    }

    $columns   = $config['columns'] ?? [];
    $tableMinW = max(640, 130 * count($columns) + 90);
@endphp
<div class="space-y-6">

    {{-- ===== Breadcrumb ===== --}}
    <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400">
        <a href="{{ route('admin.monitoring.index') }}" class="inline-flex items-center gap-1.5 transition hover:text-sky-600">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
            Monitoring
        </a>
        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
        <span class="text-slate-600">{{ $config['label'] }}</span>
    </nav>

    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3.5">
            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl {{ $tile }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $config['icon'] }}" /></svg>
            </div>
            <div>
                <h2 class="text-xl font-bold tracking-tight text-slate-900">Laporan {{ $config['label'] }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ $config['description'] }}</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ $exportQuery('csv') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50"
               title="Simpan laporan sesuai data yang sedang ditampilkan (format CSV)">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                Unduh CSV
            </a>
            <a href="{{ $exportQuery('xlsx') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
               title="Simpan laporan sesuai data yang sedang ditampilkan (format Excel)">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                Unduh Excel
            </a>
        </div>
    </div>

    {{-- ===== Kartu Statistik ===== --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($stats as $s)
            @php $sc = $tileTone[$s['tone']] ?? $tileTone['slate']; @endphp
            <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-xs ring-1 ring-slate-200">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg {{ $sc }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $s['icon'] }}" /></svg>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-lg font-extrabold leading-tight tabular-nums text-slate-900" title="{{ $s['value'] }}">{{ $formatN($s['value']) }}</p>
                    <p class="truncate text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ $s['label'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== Tabel Laporan ===== --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">

        {{-- Toolbar: pencarian + filter + sort + per halaman --}}
        <form method="GET" action="{{ route('admin.monitoring.report.show', $entity) }}"
              class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/60 p-4 lg:flex-row lg:items-end lg:justify-between">

            <div class="relative w-full lg:max-w-sm">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="block w-full rounded-xl border-0 bg-white py-2.5 pl-10 pr-9 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 transition focus:ring-2 focus:ring-inset focus:ring-sky-500"
                       placeholder="{{ $config['searchHint'] ?? 'Cari data...' }}">
                @if (trim((string) request('search')) !== '')
                    <a href="{{ $noSearchUrl }}" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 transition hover:text-slate-600" title="Bersihkan pencarian">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </a>
                @endif
            </div>

            <div class="flex flex-wrap items-end gap-2">
                @foreach ($config['filters'] ?? [] as $filter)
                    @if (($filter['type'] ?? 'select') === 'date')
                        <label class="flex flex-col gap-1">
                            <span class="pl-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $filter['label'] }}</span>
                            <input type="date" name="{{ $filter['key'] }}" value="{{ request($filter['key']) }}" onchange="this.form.submit()"
                                   class="rounded-lg border-0 bg-white py-2 pl-3 pr-2.5 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 transition focus:ring-2 focus:ring-sky-500">
                        </label>
                    @else
                        <select name="{{ $filter['key'] }}" onchange="this.form.submit()"
                                class="cursor-pointer rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 transition focus:ring-2 focus:ring-sky-500">
                            <option value="">{{ $filter['label'] }}</option>
                            @foreach (($filterOptions[$filter['key']] ?? []) as $value => $label)
                                <option value="{{ $value }}" @selected(request($filter['key']) == $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @endif
                @endforeach

                <select name="sort" onchange="this.form.submit()"
                        class="cursor-pointer rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 transition focus:ring-2 focus:ring-sky-500">
                    @foreach ($config['sorts'] ?? [] as $key => $sort)
                        <option value="{{ $key }}" @selected($currentSort === $key)>{{ $sort['label'] }}</option>
                    @endforeach
                </select>

                <select name="per_page" onchange="this.form.submit()"
                        class="cursor-pointer rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 transition focus:ring-2 focus:ring-sky-500">
                    <option value="10" @selected(request('per_page', '10') == '10')>10 / halaman</option>
                    <option value="25" @selected(request('per_page') == '25')>25 / halaman</option>
                    <option value="50" @selected(request('per_page') == '50')>50 / halaman</option>
                    <option value="100" @selected(request('per_page') == '100')>100 / halaman</option>
                </select>

                @if ($hasActive)
                    <a href="{{ route('admin.monitoring.report.show', $entity) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3.5 py-2 text-xs font-bold text-white transition hover:bg-slate-700"
                       title="Hapus pencarian &amp; penyaringan">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                        Reset
                    </a>
                @endif
            </div>
        </form>

        {{-- Tabel --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[{{ $tableMinW }}px] text-left">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                        <th class="px-4 py-3.5 text-center font-semibold">#</th>
                        @foreach ($columns as $col)
                            <th class="px-5 py-3.5 font-semibold">{{ $col['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($rows as $row)
                        @php $no = $rows->firstItem() + $loop->index; @endphp
                        <tr class="group bg-white transition-colors hover:bg-sky-50/40">
                            <td class="px-4 py-4 text-center text-xs font-bold tabular-nums text-slate-300">{{ $no }}</td>

                            @foreach ($columns as $col)
                                @php
                                    $v    = ($col['value'])($row);
                                    $tone = $col['tone'] ?? 'slate';
                                @endphp

                                @if ($col['type'] === 'profile')
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full {{ $tileTone[$tone] ?? $tileTone['slate'] }} text-xs font-bold uppercase">
                                                {{ strtoupper(substr((string) ($v[0] ?? '?'), 0, 1)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-bold text-slate-900">{{ $v[0] ?? '—' }}</p>
                                                @if (! empty($v[1]))
                                                    <p class="truncate font-mono text-[11px] text-slate-400">{{ $v[1] }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                @elseif ($col['type'] === 'strong')
                                    <td class="whitespace-nowrap px-5 py-4">
                                        @if ($v !== null && $v !== '')
                                            <span class="text-sm font-bold text-slate-900">{{ $v }}</span>
                                        @else
                                            <span class="text-xs italic text-slate-300">—</span>
                                        @endif
                                    </td>

                                @elseif ($col['type'] === 'mono')
                                    <td class="whitespace-nowrap px-5 py-4">
                                        @if ($v !== null && $v !== '')
                                            <span class="font-mono text-xs font-semibold text-slate-600">{{ $v }}</span>
                                        @else
                                            <span class="text-xs italic text-slate-300">—</span>
                                        @endif
                                    </td>

                                @elseif ($col['type'] === 'text')
                                    <td class="px-5 py-4">
                                        @if ($v !== null && $v !== '')
                                            <p class="max-w-[240px] truncate text-sm text-slate-600" title="{{ $v }}">{{ $v }}</p>
                                        @else
                                            <span class="text-xs italic text-slate-300">—</span>
                                        @endif
                                    </td>

                                @elseif ($col['type'] === 'number')
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold tabular-nums ring-1 ring-inset {{ $badgeTone[$tone] ?? $badgeTone['slate'] }}">{{ $v }}</span>
                                    </td>

                                @elseif ($col['type'] === 'stat')
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <p class="text-sm font-extrabold tabular-nums text-slate-900">{{ $v[0] ?? '0' }}</p>
                                        @if (! empty($v[1]))
                                            <p class="text-[11px] text-slate-400">{{ $v[1] }}</p>
                                        @endif
                                    </td>

                                @elseif ($col['type'] === 'badge')
                                    @php $badgeToneKey = is_array($v) ? ($v[1] ?? $tone) : $tone; @endphp
                                    <td class="whitespace-nowrap px-5 py-4">
                                        @if (is_array($v) && ! empty($v[0]))
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $badgeTone[$badgeToneKey] ?? $badgeTone['slate'] }}">{{ $v[0] }}</span>
                                        @else
                                            <span class="text-xs italic text-slate-300">—</span>
                                        @endif
                                    </td>

                                @elseif ($col['type'] === 'tags')
                                    <td class="px-5 py-4">
                                        @if (is_array($v) && count($v) > 0)
                                            <div class="flex max-w-[260px] flex-wrap gap-1.5">
                                                @foreach (array_slice($v, 0, 3) as $tag)
                                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $badgeTone[$tone] ?? $badgeTone['slate'] }}">{{ $tag }}</span>
                                                @endforeach
                                                @if (count($v) > 3)
                                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-500 ring-1 ring-inset ring-slate-200" title="{{ implode(', ', $v) }}">+{{ count($v) - 3 }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-xs italic text-slate-300">—</span>
                                        @endif
                                    </td>

                                @else
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $v }}</td>
                                @endif
                            @endforeach
                        </tr>

                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + 1 }}" class="px-6 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 ring-8 ring-slate-50">
                                        <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $config['icon'] }}" /></svg>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-900">Tidak ada data {{ $config['label'] }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">Coba gunakan kata kunci lain, atau tampilkan semua data tanpa penyaringan.</p>
                                    @if ($hasActive)
                                        <a href="{{ route('admin.monitoring.report.show', $entity) }}" class="mt-5 rounded-lg bg-slate-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-700">Tampilkan Semua Data</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination (sisi server) --}}
        @if ($rows->total() > 0)
            <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:flex-row">
                <p class="text-xs font-medium text-slate-500">
                    Menampilkan <span class="font-bold text-slate-800">{{ $rows->firstItem() }}</span>–<span class="font-bold text-slate-800">{{ $rows->lastItem() }}</span>
                    dari <span class="font-bold text-slate-800">{{ $formatN($rows->total()) }}</span> data {{ $config['label'] }}
                </p>
                {{ $rows->links() }}
            </div>
        @endif
    </div>

    {{-- ===== Info strip ===== --}}
    <div class="flex items-start gap-3 rounded-xl bg-sky-50/70 p-4 text-xs leading-relaxed text-sky-800 ring-1 ring-sky-100">
        <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-sky-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
        </svg>
        <p><strong>Tips:</strong> Klik "Unduh Excel" atau "Unduh CSV" untuk menyimpan laporan ke komputer Anda. Isi unduhan mengikuti data yang sedang ditampilkan di layar.</p>
    </div>
</div>
@endsection
