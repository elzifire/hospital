@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@php
    // Helper warna (bg / text / hex / gradient)
    $c = [
        'blue'   => ['bg' => 'bg-blue-500',   'text' => 'text-blue-600',   'hex' => '#3b82f6', 'light' => 'bg-blue-50',   'grad' => 'from-blue-600 to-sky-500'],
        'navy'   => ['bg' => 'bg-blue-700',   'text' => 'text-blue-700',   'hex' => '#1d4ed8', 'light' => 'bg-blue-50',   'grad' => 'from-blue-800 to-indigo-600'],
        'green'  => ['bg' => 'bg-emerald-500','text' => 'text-emerald-600','hex' => '#10b981', 'light' => 'bg-emerald-50','grad' => 'from-emerald-600 to-teal-500'],
        'orange' => ['bg' => 'bg-amber-500',  'text' => 'text-amber-600',  'hex' => '#f59e0b', 'light' => 'bg-amber-50',  'grad' => 'from-amber-600 to-orange-500'],
        'yellow' => ['bg' => 'bg-yellow-500', 'text' => 'text-yellow-600', 'hex' => '#eab308', 'light' => 'bg-yellow-50', 'grad' => 'from-yellow-600 to-amber-500'],
        'cyan'   => ['bg' => 'bg-cyan-500',   'text' => 'text-cyan-600',   'hex' => '#06b6d4', 'light' => 'bg-cyan-50',   'grad' => 'from-cyan-600 to-sky-500'],
        'pink'   => ['bg' => 'bg-pink-500',   'text' => 'text-pink-600',   'hex' => '#ec4899', 'light' => 'bg-pink-50',   'grad' => 'from-pink-600 to-rose-500'],
        'purple' => ['bg' => 'bg-purple-500', 'text' => 'text-purple-600', 'hex' => '#9333ea', 'light' => 'bg-purple-50', 'grad' => 'from-purple-600 to-violet-500'],
        'red'    => ['bg' => 'bg-rose-500',   'text' => 'text-rose-600',   'hex' => '#f43f5e', 'light' => 'bg-rose-50',   'grad' => 'from-rose-600 to-red-500'],
        'gray'   => ['bg' => 'bg-slate-400',  'text' => 'text-slate-500',  'hex' => '#94a3b8', 'light' => 'bg-slate-50',  'grad' => 'from-slate-600 to-slate-500'],
    ];
    $fill = function ($p) { return (int) rtrim($p, '%'); };

    // Kalkulasi ringkasan trend tahun berjalan (Januari s/d bulan sekarang) untuk tabel modal
    $trendMonths = $trend['months'] ?? [];
    $trendSeries = $trend['series'] ?? [];
    $trendTotalsPerMonth = array_fill(0, count($trendMonths), 0);
    foreach ($trendSeries as $ts) {
        foreach ($ts['data'] as $idx => $val) {
            $trendTotalsPerMonth[$idx] += $val;
        }
    }
    $trendGrandTotal = array_sum($trendTotalsPerMonth);
@endphp

@section('content')
<div x-data="dashboardApp()" class="space-y-4">

    {{-- ================= ROW 1: STAT CARDS ================= --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-8">
        @foreach ($stats as $stat)
            @php 
                $colKey = $stat['color'] ?? 'blue';
                $col = $c[$colKey] ?? $c['blue']; 
            @endphp
            <div class="group relative overflow-hidden rounded-2xl bg-white shadow-xs ring-1 ring-slate-200 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                {{-- Header gradient --}}
                <div class="bg-linear-to-r {{ $col['grad'] }} px-3 py-2 text-white">
                    <div class="flex items-center gap-1.5">
                        <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg bg-white/20 text-white backdrop-blur-xs">
                            @if ($stat['icon'] === 'users')
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z"/></svg>
                            @elseif ($stat['icon'] === 'send')
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                            @elseif ($stat['icon'] === 'chat')
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z"/></svg>
                            @elseif ($stat['icon'] === 'refresh')
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                            @elseif ($stat['icon'] === 'hospital')
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3H21m-3.75 3H21"/></svg>
                            @elseif ($stat['icon'] === 'bell')
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                            @else
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/></svg>
                            @endif
                        </span>
                        <span class="truncate text-[9px] font-bold uppercase tracking-wider text-white/95">{{ $stat['label'] }}</span>
                    </div>
                </div>
                {{-- Angka nilai --}}
                <div class="px-3 py-2.5">
                    <div class="text-xl font-black tabular-nums text-slate-900">{{ $stat['value'] }}</div>
                    <div class="mt-0.5 truncate text-[10px] text-slate-400 font-medium">{{ $stat['note'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ================= ROW 2: CHARTS ================= --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">

        {{-- 1. TREND KUNJUNGAN PNPP (DENGAN TOMBOL ZOOM MODAL & CLIENT-SIDE EXPORT) --}}
        <div class="flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-4.5 shadow-xs lg:col-span-6 xl:col-span-4">
            <div>
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-sky-50 text-sky-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.143 2.143L15.75 6" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wide text-slate-800">Trend Kunjungan PNPP</h3>
                            <p class="text-[10px] text-slate-400">Tahun {{ $trend['year'] ?? date('Y') }} (Jan – {{ end($trendMonths) }})</p>
                        </div>
                    </div>
                    
                    {{-- Tombol Perbesar / Modal Zoom --}}
                    <button type="button" @click="openTrendModal()"
                            class="inline-flex items-center gap-1 rounded-lg bg-sky-50 px-2.5 py-1 text-[11px] font-bold text-sky-700 ring-1 ring-sky-200/70 transition hover:bg-sky-100 hover:text-sky-800 cursor-pointer shadow-2xs"
                            title="Perbesar grafik & ekspor data">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                        </svg>
                        <span>Perbesar</span>
                    </button>
                </div>

                {{-- Legend Mini --}}
                <div class="my-2.5 flex flex-wrap items-center justify-between gap-2 text-[10px] font-semibold text-slate-600">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span> IGD</span>
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span> Rawat Jalan</span>
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> Rawat Inap</span>
                    </div>
                    <span class="text-[10px] text-slate-400">Total: <strong class="text-slate-700">{{ number_format($trendGrandTotal, 0, ',', '.') }}</strong></span>
                </div>

                {{-- Chart Container --}}
                <div id="trendChart" class="h-52 w-full"></div>
            </div>

            <div class="mt-2 flex items-center justify-between border-t border-slate-100 pt-2 text-[10px] text-slate-400">
                <span>Ekspor langsung via tombol titik tiga di grafik</span>
                <button type="button" @click="openTrendModal()" class="font-bold text-sky-600 hover:underline cursor-pointer">Lihat Detail Lengkap &rarr;</button>
            </div>
        </div>

        {{-- 2. OUTREACH PER SATKER --}}
        <div class="flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-4.5 shadow-xs lg:col-span-6 xl:col-span-4">
            <div>
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wide text-slate-800">Outreach Per Satker</h3>
                            <p class="text-[10px] text-slate-400">Distribusi Satuan Kerja</p>
                        </div>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">{{ count($outreach) }} Satker</span>
                </div>

                <div class="mt-3 space-y-3">
                    @foreach ($outreach as $o)
                        @php $col = $c[$o['color']]; @endphp
                        <div>
                            <div class="flex items-center justify-between text-[11px] mb-1">
                                <span class="truncate font-semibold text-slate-700" title="{{ $o['name'] }}">{{ $o['name'] }}</span>
                                <span class="font-bold tabular-nums text-slate-900">
                                    {{ $o['value'] }} <span class="font-normal text-slate-400 text-[10px]">({{ $o['percent'] }})</span>
                                </span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ $col['bg'] }} transition-all duration-500" style="width: {{ $fill($o['percent']) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-4 border-t border-slate-100 pt-2 text-[10px] text-slate-400 text-right">
                <a href="{{ route('admin.outreach.index') }}" class="font-bold text-sky-600 hover:underline">Kelola Data Outreach &rarr;</a>
            </div>
        </div>

        {{-- 3. STATUS FOLLOW-UP (DONUT DENGAN MODAL ZOOM & EXPORT) --}}
        <div class="flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-4.5 shadow-xs lg:col-span-6 xl:col-span-4">
            <div>
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wide text-slate-800">Status Follow-Up</h3>
                            <p class="text-[10px] text-slate-400">Total: {{ $followup['total'] }}</p>
                        </div>
                    </div>
                    <button type="button" @click="openDonutModal()"
                            class="inline-flex items-center gap-1 rounded-lg bg-slate-50 px-2 py-1 text-[10px] font-bold text-slate-600 ring-1 ring-slate-200 transition hover:bg-sky-50 hover:text-sky-700 cursor-pointer"
                            title="Perbesar diagram status">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                        </svg>
                        <span>Perbesar</span>
                    </button>
                </div>

                <div id="donutChart" class="mx-auto h-44 w-full"></div>

                <div class="mt-2 grid grid-cols-3 gap-2">
                    @foreach ($followup['series'] as $lg)
                        <div class="rounded-xl bg-slate-50/80 p-2 text-center ring-1 ring-slate-100">
                            <div class="flex items-center justify-center gap-1 text-[10px] font-bold text-slate-600">
                                <span class="h-2 w-2 rounded-full" style="background: {{ $lg['color'] }}"></span>
                                {{ $lg['name'] }}
                            </div>
                            <p class="mt-0.5 text-xs font-black text-slate-800">{{ $lg['value'] }}</p>
                            <p class="text-[9px] text-slate-400 font-medium">{{ $lg['percent'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-3 border-t border-slate-100 pt-2 text-[10px] text-slate-400 text-right">
                <a href="{{ route('admin.follow-up.index') }}" class="font-bold text-sky-600 hover:underline">Kelola Follow-Up &rarr;</a>
            </div>
        </div>

    </div>

    {{-- ================= ROW 3: BOTTOM CARDS ================= --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

        {{-- AKTIVITAS TERKINI --}}
        <div class="flex flex-col rounded-2xl border border-slate-200/80 bg-white p-4.5 shadow-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wide text-slate-800">Aktivitas Terkini</h3>
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
            </div>
            <div class="flex-1 space-y-3 mt-3">
                @foreach ($activities as $act)
                    @php $col = $c[$act['color']]; @endphp
                    <div class="flex items-center gap-2.5 rounded-xl bg-slate-50/60 p-2.5 ring-1 ring-slate-100 transition hover:bg-white hover:ring-sky-200">
                        <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl text-white shadow-2xs {{ $col['bg'] }}">
                            @if ($act['icon'] === 'phone')
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                            @elseif ($act['icon'] === 'chat')
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>
                            @else
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                            @endif
                        </span>
                        <div class="min-w-0 flex-1 leading-tight">
                            <p class="truncate text-[11px] font-bold text-slate-800">{{ $act['title'] }}</p>
                            <p class="truncate text-[10px] text-slate-500">{{ $act['name'] }} · <span class="text-slate-400">{{ $act['satker'] }}</span></p>
                        </div>
                        <span class="flex-shrink-0 text-[10px] font-bold text-slate-400">{{ $act['time'] }}</span>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('admin.monitoring.index') }}" class="mt-3 block w-full rounded-xl border border-slate-200 bg-slate-50 py-2 text-center text-xs font-bold text-slate-700 transition hover:bg-slate-100">
                Lihat Semua Aktivitas &rarr;
            </a>
        </div>

        {{-- MONITORING TARGET 60 HARI --}}
        <div class="flex flex-col rounded-2xl border border-slate-200/80 bg-white p-4.5 shadow-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wide text-slate-800">Monitoring Target</h3>
                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[9px] font-bold text-blue-700">60 Hari</span>
            </div>
            <div class="flex-1 overflow-x-auto mt-2">
                <table class="w-full text-[10px]">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-slate-400 uppercase font-bold">
                            <th class="py-1.5 pr-2">Indikator</th>
                            <th class="py-1.5 pr-2">Target</th>
                            <th class="py-1.5 pr-2">Capaian</th>
                            <th class="py-1.5 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach ($monitoring as $row)
                            <tr>
                                <td class="py-1.5 pr-2 font-bold text-slate-700 truncate max-w-[100px]">{{ $row['name'] }}</td>
                                <td class="py-1.5 pr-2 font-medium text-slate-500">{{ $row['target'] }}</td>
                                <td class="py-1.5 pr-2 font-bold text-slate-800">{{ $row['kunjungan'] }}</td>
                                <td class="py-1.5 text-right">
                                    @if ($row['status'] === 'On Track')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                            On Track
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-1.5 py-0.5 text-[9px] font-bold text-rose-700 ring-1 ring-inset ring-rose-200">
                                            Perlu Atensi
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <a href="{{ route('admin.monitoring.index') }}" class="mt-3 block w-full rounded-xl bg-blue-600 py-2 text-center text-xs font-bold text-white shadow-xs transition hover:bg-blue-700">
                Lihat Dashboard Target &rarr;
            </a>
        </div>

        {{-- KUNJUNGAN PNPP HARI INI --}}
        <div class="flex flex-col rounded-2xl border border-slate-200/80 bg-white p-4.5 shadow-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wide text-slate-800">Kunjungan Hari Ini</h3>
                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[9px] font-bold text-emerald-700">Live</span>
            </div>
            <div class="mt-3 flex items-center justify-between gap-3">
                <div>
                    <div class="text-3xl font-black text-slate-900">{{ $kunjunganToday['total'] }}</div>
                    <p class="text-[10px] text-slate-400 font-medium">Total Pasien Hari Ini</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 text-sky-600 ring-1 ring-sky-100">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3H21m-3.75 3H21"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3 space-y-1.5">
                @foreach ($kunjunganToday['items'] as $k)
                    @php $col = $c[$k['color']]; @endphp
                    <div class="flex items-center justify-between rounded-xl {{ $col['light'] }} px-3 py-1.5 ring-1 ring-inset ring-black/5">
                        <span class="text-[11px] font-bold text-slate-700">{{ $k['label'] }}</span>
                        <span class="text-xs font-black {{ $col['text'] }}">{{ $k['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- FOLLOW-UP HARI INI & ALERT --}}
        <div class="flex flex-col rounded-2xl border border-slate-200/80 bg-white p-4.5 shadow-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wide text-slate-800">Alert & Follow-Up</h3>
                <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[9px] font-bold text-rose-700">{{ $followupToday['count'] }} Hari Ini</span>
            </div>
            <div class="flex-1 space-y-2 mt-3">
                <div class="flex items-center justify-between rounded-xl bg-rose-50/80 p-3 ring-1 ring-rose-200/70">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-500 text-white">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                        </span>
                        <div>
                            <p class="text-xs font-bold text-rose-900">Perlu Follow-up</p>
                            <p class="text-[10px] text-rose-600">{{ $followupToday['note'] }}</p>
                        </div>
                    </div>
                    <span class="text-lg font-black text-rose-600">{{ $followupToday['count'] }}</span>
                </div>

                @foreach ($alerts as $al)
                    @php $col = $c[$al['color']]; @endphp
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 ring-1 ring-slate-100">
                        <span class="text-[11px] font-semibold text-slate-700">{{ $al['title'] }}</span>
                        <span class="text-xs font-bold {{ $col['text'] }}">{{ $al['count'] }}</span>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('admin.follow-up.index') }}" class="mt-3 block w-full rounded-xl border border-slate-200 bg-slate-50 py-2 text-center text-xs font-bold text-slate-700 transition hover:bg-slate-100">
                Buka Antrean Follow-Up &rarr;
            </a>
        </div>

    </div>

    {{-- ==================================================================== --}}
    {{-- MODAL ZOOM & EXPORT GRAFIK (INTERAKTIF & CLIENT-SIDE EXPORT)        --}}
    {{-- ==================================================================== --}}
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5"
         role="dialog" aria-modal="true">
        
        {{-- Backdrop --}}
        <div x-show="modalOpen"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="modalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>

        {{-- Modal Dialog Container --}}
        <div x-show="modalOpen"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             class="relative flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200">
            
            {{-- Modal Header --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/70 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-600 text-white shadow-xs">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.143 2.143L15.75 6" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900" x-text="modalTitle"></h3>
                        <p class="text-xs text-slate-500">Tampilan grafis resolusi tinggi dengan fasilitas ekspor data langsung (Client-Side Export).</p>
                    </div>
                </div>

                {{-- Toolbar Modal --}}
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Tipe Grafik Switcher (khusus Trend) --}}
                    <template x-if="modalChartType === 'trend'">
                        <div class="inline-flex rounded-xl bg-slate-100 p-0.5 ring-1 ring-slate-200/80">
                            <button type="button" @click="setModalChartType('spline')"
                                    :class="chartDisplayType === 'spline' ? 'bg-white text-sky-600 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                    class="rounded-lg px-2.5 py-1 text-xs transition cursor-pointer">
                                Garis (Spline)
                            </button>
                            <button type="button" @click="setModalChartType('column')"
                                    :class="chartDisplayType === 'column' ? 'bg-white text-sky-600 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                    class="rounded-lg px-2.5 py-1 text-xs transition cursor-pointer">
                                Batang (Bar)
                            </button>
                            <button type="button" @click="setModalChartType('areaspline')"
                                    :class="chartDisplayType === 'areaspline' ? 'bg-white text-sky-600 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                    class="rounded-lg px-2.5 py-1 text-xs transition cursor-pointer">
                                Area
                            </button>
                        </div>
                    </template>

                    {{-- Tombol Tutup --}}
                    <button type="button" @click="modalOpen = false" class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-200 hover:text-slate-700 cursor-pointer">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>

            {{-- Modal Body --}}
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                {{-- Container Grafik Besar --}}
                <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-xs">
                    <div id="modalChartContainer" class="h-96 w-full"></div>
                </div>

                {{-- Tabel Rincian Data (Untuk Trend 1 Tahun / Jan - Sekarang) --}}
                <template x-if="modalChartType === 'trend'">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Tabel Rincian Data Kunjungan Per Kategori (Tahun {{ $trend['year'] ?? date('Y') }})</h4>
                            <span class="text-xs font-semibold text-slate-500">Total YTD: <strong class="text-slate-900">{{ number_format($trendGrandTotal, 0, ',', '.') }} Pasien</strong></span>
                        </div>
                        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold text-slate-600 uppercase">
                                        <th class="px-4 py-2.5">Kategori</th>
                                        @foreach ($trendMonths as $m)
                                            <th class="px-3 py-2.5 text-center">{{ $m }}</th>
                                        @endforeach
                                        <th class="px-4 py-2.5 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-700">
                                    @foreach ($trendSeries as $ts)
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-4 py-2.5 font-bold flex items-center gap-1.5">
                                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $ts['color'] }}"></span>
                                                {{ $ts['name'] }}
                                            </td>
                                            @foreach ($ts['data'] as $val)
                                                <td class="px-3 py-2.5 text-center tabular-nums font-semibold">{{ number_format($val, 0, ',', '.') }}</td>
                                            @endforeach
                                            <td class="px-4 py-2.5 text-right font-bold tabular-nums text-slate-900">
                                                {{ number_format(array_sum($ts['data']), 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-slate-50/80 font-black text-slate-900 border-t-2 border-slate-200">
                                        <td class="px-4 py-2.5 uppercase text-[11px]">Total Bulanan</td>
                                        @foreach ($trendTotalsPerMonth as $tot)
                                            <td class="px-3 py-2.5 text-center tabular-nums text-sky-700">{{ number_format($tot, 0, ',', '.') }}</td>
                                        @endforeach
                                        <td class="px-4 py-2.5 text-right tabular-nums text-sky-800 text-sm">{{ number_format($trendGrandTotal, 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Modal Footer with Client-Side Export Quick Actions --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/70 px-6 py-3.5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-slate-600">Ekspor Cepat:</span>
                    <button type="button" @click="exportModal('image/png')"
                            class="inline-flex items-center gap-1 rounded-lg bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs ring-1 ring-slate-200 transition hover:bg-slate-50 cursor-pointer">
                        <svg class="h-3.5 w-3.5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                        PNG
                    </button>
                    <button type="button" @click="exportModal('image/jpeg')"
                            class="inline-flex items-center gap-1 rounded-lg bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs ring-1 ring-slate-200 transition hover:bg-slate-50 cursor-pointer">
                        JPEG
                    </button>
                    <button type="button" @click="exportModal('application/pdf')"
                            class="inline-flex items-center gap-1 rounded-lg bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs ring-1 ring-slate-200 transition hover:bg-slate-50 cursor-pointer">
                        <svg class="h-3.5 w-3.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                        PDF
                    </button>
                    <button type="button" @click="exportModal('image/svg+xml')"
                            class="inline-flex items-center gap-1 rounded-lg bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs ring-1 ring-slate-200 transition hover:bg-slate-50 cursor-pointer">
                        SVG
                    </button>
                    <button type="button" @click="exportDataCSV()"
                            class="inline-flex items-center gap-1 rounded-lg bg-white px-2.5 py-1.5 text-xs font-semibold text-emerald-700 shadow-2xs ring-1 ring-emerald-200 transition hover:bg-emerald-50 cursor-pointer">
                        <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.143 2.143L15.75 6" /></svg>
                        CSV / Excel
                    </button>
                </div>

                <button type="button" @click="modalOpen = false" class="rounded-xl border border-slate-300 bg-white px-5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>

{{-- HIGHCHARTS CORE & CLIENT-SIDE EXPORT MODULES (OFFLINE READY) --}}
<script src="{{ asset('vendor/highcharts/highcharts.js') }}"></script>
<script src="{{ asset('vendor/highcharts/exporting.js') }}"></script>
<script src="{{ asset('vendor/highcharts/offline-exporting.js') }}"></script>
<script src="{{ asset('vendor/highcharts/export-data.js') }}"></script>
<script src="{{ asset('vendor/highcharts/accessibility.js') }}"></script>

<script>
    // Konfigurasi Global Highcharts Bahasa Indonesia & Client-Side Offline Export
    Highcharts.setOptions({
        lang: {
            contextButtonTitle: 'Menu Ekspor Grafik',
            downloadPNG: 'Unduh Gambar PNG',
            downloadJPEG: 'Unduh Gambar JPEG',
            downloadPDF: 'Unduh Dokumen PDF',
            downloadSVG: 'Unduh Vektor SVG',
            downloadCSV: 'Unduh Format CSV',
            downloadXLS: 'Unduh Format Excel (XLS)',
            viewData: 'Lihat Tabel Data',
            viewFullscreen: 'Tampilkan Layar Penuh',
            printChart: 'Cetak Grafik',
            months: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            shortMonths: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            weekdays: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
            thousandsSep: '.',
            decimalPoint: ','
        },
        exporting: {
            fallbackToExportServer: false // 100% Client-Side Export murni di browser
        }
    });

    document.addEventListener('alpine:init', () => {
        Alpine.data('dashboardApp', () => ({
            modalOpen: false,
            modalChartType: 'trend',
            chartDisplayType: 'spline',
            modalChartInstance: null,

            get modalTitle() {
                return this.modalChartType === 'trend'
                    ? 'Trend Kunjungan Pasien PNPP (Tahun ' + @json($trend['year'] ?? date('Y')) + ' : Jan – ' + @json(end($trendMonths) ?: 'Des') + ')'
                    : 'Status Follow-Up Pasien PNPP';
            },

            openTrendModal() {
                this.modalChartType = 'trend';
                this.chartDisplayType = 'spline';
                this.modalOpen = true;
                this.$nextTick(() => {
                    this.renderModalTrendChart();
                });
            },

            openDonutModal() {
                this.modalChartType = 'followup';
                this.modalOpen = true;
                this.$nextTick(() => {
                    this.renderModalDonutChart();
                });
            },

            setModalChartType(type) {
                this.chartDisplayType = type;
                this.renderModalTrendChart();
            },

            renderModalTrendChart() {
                if (this.modalChartInstance) {
                    this.modalChartInstance.destroy();
                }

                const seriesData = @json($trend['series']).map(s => ({
                    ...s,
                    type: this.chartDisplayType,
                    fillOpacity: this.chartDisplayType === 'areaspline' ? 0.2 : undefined
                }));

                this.modalChartInstance = Highcharts.chart('modalChartContainer', {
                    chart: {
                        type: this.chartDisplayType,
                        backgroundColor: '#ffffff',
                        spacing: [20, 20, 20, 20],
                        style: { fontFamily: 'Inter, sans-serif' }
                    },
                    title: { text: null },
                    colors: @json(collect($trend['series'])->pluck('color')),
                    xAxis: {
                        categories: @json($trend['months']),
                        lineColor: '#cbd5e1',
                        tickColor: '#cbd5e1',
                        labels: { style: { fontSize: '12px', fontWeight: '600', color: '#475569' } }
                    },
                    yAxis: {
                        gridLineColor: '#f1f5f9',
                        title: { text: 'Jumlah Kunjungan Pasien', style: { fontSize: '12px', color: '#64748b' } },
                        labels: {
                            formatter: function () { return Highcharts.numberFormat(this.value, 0, ',', '.'); },
                            style: { fontSize: '11px', color: '#64748b' }
                        }
                    },
                    legend: {
                        enabled: true,
                        itemStyle: { fontSize: '12px', fontWeight: 'bold', color: '#334155' }
                    },
                    credits: { enabled: false },
                    plotOptions: {
                        series: {
                            animation: { duration: 600 },
                            marker: { enabled: true, radius: 5, symbol: 'circle' },
                            lineWidth: 3,
                            dataLabels: {
                                enabled: true,
                                style: { fontSize: '10px', fontWeight: 'bold' },
                                formatter: function () { return Highcharts.numberFormat(this.y, 0, ',', '.'); }
                            }
                        }
                    },
                    tooltip: {
                        shared: true,
                        useHTML: true,
                        borderRadius: 12,
                        shadow: true,
                        backgroundColor: '#ffffff',
                        borderColor: '#e2e8f0',
                        headerFormat: '<span style="font-size:12px;font-weight:bold;color:#0f172a">{point.key}</span><br/>',
                        pointFormat: '<span style="color:{point.color}">\u25CF</span> {series.name}: <b>{point.y:,.0f} pasien</b><br/>'
                    },
                    exporting: {
                        enabled: true,
                        buttons: {
                            contextButton: {
                                menuItems: ['viewFullscreen', 'downloadPNG', 'downloadJPEG', 'downloadPDF', 'downloadSVG', 'separator', 'downloadCSV', 'downloadXLS', 'printChart']
                            }
                        }
                    },
                    series: seriesData
                });
            },

            renderModalDonutChart() {
                if (this.modalChartInstance) {
                    this.modalChartInstance.destroy();
                }

                this.modalChartInstance = Highcharts.chart('modalChartContainer', {
                    chart: {
                        type: 'pie',
                        backgroundColor: '#ffffff',
                        spacing: [20, 20, 20, 20],
                        style: { fontFamily: 'Inter, sans-serif' }
                    },
                    title: {
                        text: 'Total Follow-Up<br><b style="font-size:24px;color:#0f172a">{{ number_format($followup['total'], 0, ',', '.') }}</b>',
                        align: 'center',
                        verticalAlign: 'middle',
                        y: 0,
                        useHTML: true,
                        style: { fontSize: '13px', color: '#64748b' }
                    },
                    credits: { enabled: false },
                    tooltip: {
                        pointFormat: '<b>{point.y:,.0f} kasus ({point.percentage:.1f}%)</b>',
                        borderRadius: 10
                    },
                    plotOptions: {
                        pie: {
                            innerSize: '65%',
                            dataLabels: {
                                enabled: true,
                                format: '<b>{point.name}</b>: {point.y:,.0f} ({point.percentage:.1f}%)',
                                style: { fontSize: '11px', color: '#334155' }
                            },
                            showInLegend: true,
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }
                    },
                    legend: {
                        enabled: true,
                        itemStyle: { fontSize: '12px', fontWeight: 'bold' }
                    },
                    exporting: {
                        enabled: true,
                        buttons: {
                            contextButton: {
                                menuItems: ['viewFullscreen', 'downloadPNG', 'downloadJPEG', 'downloadPDF', 'downloadSVG', 'separator', 'downloadCSV', 'downloadXLS', 'printChart']
                            }
                        }
                    },
                    series: [{
                        type: 'pie',
                        name: 'Status Follow-up',
                        data: @json(collect($followup['series'])->map(function ($s) {
                            return ['name' => $s['name'], 'y' => $s['value'], 'color' => $s['color']];
                        })->values())
                    }]
                });
            },

            exportModal(mimeType) {
                if (!this.modalChartInstance) return;
                this.modalChartInstance.exportChartLocal({
                    type: mimeType,
                    filename: 'Grafik-' + (this.modalChartType === 'trend' ? 'Trend-Kunjungan-PNPP' : 'Status-Follow-Up') + '-' + new Date().toISOString().slice(0, 10)
                });
            },

            exportDataCSV() {
                if (!this.modalChartInstance) return;
                this.modalChartInstance.downloadCSV();
            }
        }));
    });

    document.addEventListener('DOMContentLoaded', function () {
        // ===== 1. Trend kunjungan inline (spline with client-side export) =====
        Highcharts.chart('trendChart', {
            chart: {
                type: 'spline',
                height: 200,
                spacing: [10, 10, 10, 0],
                backgroundColor: 'transparent',
                style: { fontFamily: 'Inter, sans-serif' }
            },
            title: { text: null },
            colors: @json(collect($trend['series'])->pluck('color')),
            xAxis: {
                categories: @json($trend['months']),
                lineColor: '#e2e8f0',
                tickColor: '#e2e8f0',
                labels: { style: { fontSize: '10px', fontWeight: '600', color: '#64748b' } }
            },
            yAxis: {
                min: 0,
                gridLineColor: '#f1f5f9',
                labels: {
                    formatter: function() { return this.value; },
                    style: { fontSize: '10px', color: '#94a3af' }
                },
                title: { text: null }
            },
            legend: { enabled: false },
            credits: { enabled: false },
            plotOptions: {
                spline: {
                    marker: { enabled: true, radius: 4, symbol: 'circle' },
                    lineWidth: 2.5
                }
            },
            tooltip: {
                shared: true,
                borderRadius: 10,
                shadow: true,
                style: { fontSize: '11px' }
            },
            exporting: {
                enabled: true,
                buttons: {
                    contextButton: {
                        menuItems: ['viewFullscreen', 'downloadPNG', 'downloadJPEG', 'downloadPDF', 'downloadSVG', 'separator', 'downloadCSV', 'downloadXLS']
                    }
                }
            },
            series: @json($trend['series'])
        });

        // ===== 2. Status follow-up inline (donut with client-side export) =====
        Highcharts.chart('donutChart', {
            chart: {
                type: 'pie',
                height: 175,
                spacing: [0, 0, 0, 0],
                backgroundColor: 'transparent',
                style: { fontFamily: 'Inter, sans-serif' }
            },
            title: {
                text: 'Total<br><b style="font-size:18px;color:#0f172a">{{ $followup['total'] }}</b>',
                align: 'center',
                verticalAlign: 'middle',
                y: 0,
                useHTML: true,
                style: { fontSize: '11px', color: '#64748b', fontWeight: 'normal' }
            },
            credits: { enabled: false },
            tooltip: {
                pointFormat: '<b>{point.y:,.0f} ({point.percentage:.1f}%)</b>',
                borderRadius: 10
            },
            plotOptions: {
                pie: {
                    innerSize: '65%',
                    dataLabels: { enabled: false },
                    showInLegend: false,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    states: { hover: { brightness: 0.05 } }
                }
            },
            exporting: {
                enabled: true,
                buttons: {
                    contextButton: {
                        menuItems: ['viewFullscreen', 'downloadPNG', 'downloadJPEG', 'downloadPDF', 'downloadSVG', 'separator', 'downloadCSV', 'downloadXLS']
                    }
                }
            },
            series: [{
                type: 'pie',
                name: 'Status Follow-up',
                center: ['50%', '50%'],
                data: @json(collect($followup['series'])->map(function ($s) {
                    return ['name' => $s['name'], 'y' => $s['value'], 'color' => $s['color']];
                })->values())
            }]
        });
    });
</script>
@endsection

