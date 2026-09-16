@php
    $exportQuery = fn (string $format) => route('admin.monitoring.report.export', $entity)
        . '?' . http_build_query(array_merge(request()->except('page'), ['format' => $format]));
@endphp

<nav class="flex items-center gap-2 text-xs font-semibold text-slate-400">
    <a href="{{ route('admin.monitoring.index') }}" class="inline-flex items-center gap-1.5 transition hover:text-sky-600">
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
        Monitoring
    </a>
    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
    <span class="text-slate-600">{{ $config['label'] }}</span>
</nav>

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
           class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700"
           title="Simpan laporan sesuai data yang sedang ditampilkan (format Excel)">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
            Unduh Excel
        </a>
    </div>
</div>