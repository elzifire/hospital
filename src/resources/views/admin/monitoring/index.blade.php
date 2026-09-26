@extends('layouts.app')

@section('title', 'Monitoring')
@section('page-title', 'Monitoring')

@section('content')
@php
    // Peta warna untuk ubin ikon kartu (mengikuti tone entitas di tiap fitur laporan).
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

    $formatCount = fn ($n) => number_format((int) $n, 0, ',', '.');
@endphp
<div class="space-y-8">

    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Monitoring</h2>
            <p class="mt-0.5 text-sm text-slate-500">Pilih salah satu laporan di bawah ini untuk melihat datanya — Anda bisa mencari, menyaring, hingga mengunduhnya.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-2 rounded-xl bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-xs ring-1 ring-slate-200">
                <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                {{ $formatCount($summary['available']) }} laporan aktif
            </span>
            <span class="inline-flex items-center gap-2 rounded-xl bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-xs ring-1 ring-slate-200">
                <svg class="h-4 w-4 text-sky-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
                {{ $formatCount($summary['records']) }} data terpantau
            </span>
            @if ($summary['total'] > $summary['available'])
                <span class="inline-flex items-center gap-2 rounded-xl bg-white px-3.5 py-2 text-xs font-bold text-slate-400 shadow-xs ring-1 ring-slate-200">
                    <svg class="h-4 w-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    {{ $summary['total'] - $summary['available'] }} segera
                </span>
            @endif
        </div>
    </div>

    {{-- ===== Grup kartu laporan ===== --}}
    @foreach ($groups as $group)
        @php
            $groupIcons = [
                'master'      => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
                'broadcasting' => 'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46',
            ];
            $availableCount = $group['cards']->where('available', true)->count();
        @endphp

        <section>
            {{-- Kepala grup --}}
            <div class="mb-3 flex flex-wrap items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 text-white">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $groupIcons[$group['key']] ?? '' }}" /></svg>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2.5">
                        <h3 class="text-base font-bold tracking-tight text-slate-900">{{ $group['label'] }}</h3>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold tabular-nums text-slate-500">{{ $availableCount }}/{{ $group['cards']->count() }} laporan</span>
                    </div>
                    @if ($group['key'] === 'master')
                        <p class="mt-0.5 text-xs text-slate-400">Data utama layanan rumah sakit: pasien PNPP, satker, penyakit, dan instalasi.</p>
                    @else
                        <p class="mt-0.5 text-xs text-slate-400">Ringkasan aktivitas pengiriman pesan dan kunjungan pasien.</p>
                    @endif
                </div>
            </div>

            {{-- Kartu laporan --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($group['cards'] as $card)
                    @php
                        $tile  = $tileTone[$card['tone']] ?? $tileTone['slate'];
                        $initial = strtoupper(substr($card['label'], 0, 1));
                    @endphp

                    @if ($card['available'])
                        <a href="{{ route('admin.monitoring.'.$card['entity']) }}"
                           title="Lihat laporan {{ $card['label'] }}"
                           class="group relative flex flex-col gap-3 rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-200 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:ring-slate-300 focus:outline-none focus:ring-2 focus:ring-sky-500">
                            <div class="flex items-start justify-between">
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $tile }}">
                                    <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" /></svg>
                                </div>
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-50 text-slate-300 transition-all group-hover:bg-sky-600 group-hover:text-white">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                                </span>
                            </div>

                            <div class="min-w-0">
                                <h4 class="text-sm font-bold text-slate-900">{{ $card['label'] }}</h4>
                                <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-500">{{ $card['description'] }}</p>
                            </div>

                            <div class="mt-auto flex items-center justify-between border-t border-slate-100 pt-3">
                                <p class="text-xs font-bold tabular-nums text-slate-800">
                                    {{ $formatCount($card['count']) }} <span class="font-medium text-slate-400">data</span>
                                </p>
                                <span class="text-[11px] font-bold text-sky-600 opacity-0 transition-opacity group-hover:opacity-100">Lihat laporan →</span>
                            </div>
                        </a>
                    @else
                        <div title="Laporan {{ $card['label'] }} sedang disiapkan dan akan segera tersedia"
                             class="relative flex flex-col gap-3 rounded-2xl bg-white/60 p-5 shadow-xs ring-1 ring-slate-200/70">
                            <div class="flex items-start justify-between">
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $tile }} opacity-70">
                                    <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" /></svg>
                                </div>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-600 ring-1 ring-amber-200/70">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                                    Segera
                                </span>
                            </div>

                            <div class="min-w-0">
                                <h4 class="text-sm font-bold text-slate-400">{{ $card['label'] }}</h4>
                                <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-400">{{ $card['description'] }}</p>
                            </div>

                            <div class="mt-auto flex items-center justify-between border-t border-slate-100 pt-3">
                                <p class="text-xs font-medium text-slate-300">Sedang disiapkan</p>
                                <svg class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.375c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 0 1-1.875-1.875V13.5m0 0-3.75 3.75M3 3l18 18" /></svg>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    @endforeach

    {{-- ===== Info strip ===== --}}
    <div class="flex items-start gap-3 rounded-xl bg-sky-50/70 p-4 text-xs leading-relaxed text-sky-800 ring-1 ring-sky-100">
        <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-sky-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
        </svg>
        <p><strong>Tips:</strong> Setiap laporan bisa dicari, disaring, dan diurutkan sesuai kebutuhan, lalu disimpan ke komputer Anda dalam bentuk Excel atau CSV. Isi unduhan sesuai dengan data yang sedang Anda lihat.</p>
    </div>
</div>
@endsection
