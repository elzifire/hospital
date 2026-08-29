@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@php
    // Helper warna (bg / text / hex)
    $c = [
        'blue'   => ['bg' => 'bg-blue-500',   'text' => 'text-blue-600',   'hex' => '#3b82f6', 'light' => 'bg-blue-50'],
        'navy'   => ['bg' => 'bg-blue-700',   'text' => 'text-blue-700',   'hex' => '#1d4ed8', 'light' => 'bg-blue-50'],
        'green'  => ['bg' => 'bg-green-500',  'text' => 'text-green-600',  'hex' => '#16a34a', 'light' => 'bg-green-50'],
        'orange' => ['bg' => 'bg-orange-500', 'text' => 'text-orange-600', 'hex' => '#f59e0b', 'light' => 'bg-orange-50'],
        'yellow' => ['bg' => 'bg-yellow-500', 'text' => 'text-yellow-600', 'hex' => '#eab308', 'light' => 'bg-yellow-50'],
        'cyan'   => ['bg' => 'bg-cyan-500',   'text' => 'text-cyan-600',   'hex' => '#06b6d4', 'light' => 'bg-cyan-50'],
        'pink'   => ['bg' => 'bg-pink-500',   'text' => 'text-pink-600',   'hex' => '#ec4899', 'light' => 'bg-pink-50'],
        'purple' => ['bg' => 'bg-purple-500', 'text' => 'text-purple-600', 'hex' => '#9333ea', 'light' => 'bg-purple-50'],
        'red'    => ['bg' => 'bg-red-500',    'text' => 'text-red-600',    'hex' => '#dc2626', 'light' => 'bg-red-50'],
        'gray'   => ['bg' => 'bg-gray-400',   'text' => 'text-gray-500',   'hex' => '#9ca3af', 'light' => 'bg-gray-50'],
    ];
    $fill = function ($p) { return (int) rtrim($p, '%'); };
@endphp

@section('content')
<div class="space-y-4">

    {{-- ================= ROW 1: STAT CARDS ================= --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ($stats as $stat)
            @php $col = $c[$stat['color']]; @endphp
            <div class="relative overflow-hidden rounded-xl bg-white shadow-sm border border-gray-100">
                {{-- Header biru gradient --}}
                <div class="bg-gradient-to-r from-blue-700 to-blue-500 px-3 py-2">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-white/20 text-white">
                            @if ($stat['icon'] === 'users')
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z"/></svg>
                            @elseif ($stat['icon'] === 'send')
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                            @elseif ($stat['icon'] === 'chat')
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z"/></svg>
                            @elseif ($stat['icon'] === 'refresh')
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                            @elseif ($stat['icon'] === 'hospital')
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3H21m-3.75 3H21"/></svg>
                            @else
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/></svg>
                            @endif
                        </span>
                        <span class="text-[9px] font-bold uppercase tracking-wide text-white/90 leading-tight">{{ $stat['label'] }}</span>
                    </div>
                </div>
                {{-- Angka utama --}}
                <div class="px-3 py-2.5">
                    <div class="text-2xl font-extrabold {{ $col['text'] }}">{{ $stat['value'] }}</div>
                    <div class="mt-0.5 text-[10px] text-gray-400">{{ $stat['note'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ================= ROW 2: CHARTS ================= --}}
    <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">

        {{-- TREND KUNJUNGAN PNPP --}}
        <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm lg:col-span-3">
            <h3 class="mb-2 text-xs font-bold uppercase text-gray-700">Trend Kunjungan PNPP (6 Bulan Terakhir)</h3>
            <div class="mb-2 flex flex-wrap items-center gap-3 text-[10px]">
                <span class="flex items-center gap-1"><span class="inline-block h-2 w-4 rounded-sm bg-red-500"></span> IGD</span>
                <span class="flex items-center gap-1"><span class="inline-block h-2 w-4 rounded-sm bg-blue-500"></span> Rawat Jalan</span>
                <span class="flex items-center gap-1"><span class="inline-block h-2 w-4 rounded-sm bg-green-500"></span> Rawat Inap</span>
            </div>
            <div id="trendChart" class="h-48 w-full"></div>
        </div>

        {{-- OUTREACH PER SATKER --}}
        <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm lg:col-span-3">
            <h3 class="mb-3 text-xs font-bold uppercase text-gray-700">Outreach Per Satker</h3>
            <div class="space-y-2.5">
                @foreach ($outreach as $o)
                    @php $col = $c[$o['color']]; @endphp
                    <div class="flex items-center gap-2">
                        <span class="w-28 flex-shrink-0 truncate text-[11px] text-gray-600">{{ $o['name'] }}</span>
                        <div class="h-4 min-w-0 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-full rounded-sm {{ $col['bg'] }}" style="width: {{ $fill($o['percent']) }}%"></div>
                        </div>
                        <span class="flex-shrink-0 text-[11px] font-bold text-gray-700">
                            {{ $o['value'] }} <span class="font-normal text-gray-400">({{ $o['percent'] }})</span>
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- STATUS FOLLOW-UP --}}
        <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm lg:col-span-3">
            <h3 class="mb-1 text-xs font-bold uppercase text-gray-700">Status Follow-Up</h3>
            <div id="donutChart" class="mx-auto h-40 w-full"></div>
            <div class="mt-1 space-y-1.5">
                @foreach ($followup['series'] as $lg)
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="flex items-center gap-1.5 text-gray-600">
                            <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $lg['color'] }}"></span>
                            {{ $lg['name'] }}
                        </span>
                        <span class="font-semibold text-gray-700">{{ $lg['value'] }} <span class="font-normal text-gray-400">({{ $lg['percent'] }})</span></span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- FOLLOW-UP HARI INI --}}
        <div class="flex flex-col rounded-xl border border-gray-100 bg-white p-4 shadow-sm lg:col-span-3">
            <h3 class="text-xs font-bold uppercase text-gray-700">Follow-Up Hari Ini</h3>
            <div class="flex flex-1 flex-col items-center justify-center text-center py-3">
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-red-500 text-white">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                </span>
                <div class="mt-3 text-4xl font-extrabold text-red-500">{{ $followupToday['count'] }}</div>
                <p class="mt-1 text-[11px] text-gray-500">{{ $followupToday['note'] }}</p>
            </div>
            <button class="w-full rounded-lg bg-red-500 py-2 text-center text-xs font-semibold text-white transition hover:bg-red-600">
                Lihat Detail
            </button>
        </div>
    </div>

    {{-- ================= ROW 3: BOTTOM CARDS ================= --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">

        {{-- AKTIVITAS TERKINI --}}
        <div class="flex flex-col rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-xs font-bold uppercase text-gray-700">Aktivitas Terkini</h3>
            <div class="flex-1 space-y-3">
                @foreach ($activities as $act)
                    @php $col = $c[$act['color']]; @endphp
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-white {{ $col['bg'] }}">
                            @if ($act['icon'] === 'phone')
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                            @elseif ($act['icon'] === 'chat')
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>
                            @elseif ($act['icon'] === 'bell')
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                            @elseif ($act['icon'] === 'check')
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            @else
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                            @endif
                        </span>
                        <div class="min-w-0 flex-1 leading-tight">
                            <p class="truncate text-[11px] font-semibold text-gray-700">{{ $act['title'] }}
                                <span class="font-normal text-gray-400">{{ $act['name'] }}</span>
                            </p>
                            <p class="truncate text-[10px] text-gray-400">{{ $act['satker'] }}</p>
                        </div>
                        <span class="flex-shrink-0 text-[10px] font-medium text-gray-400">{{ $act['time'] }}</span>
                    </div>
                @endforeach
            </div>
            <button class="mt-3 w-full rounded-lg border border-gray-200 bg-gray-50 py-2 text-xs font-semibold text-gray-600 transition hover:bg-gray-100">
                Lihat Semua Aktivitas >
            </button>
        </div>

        {{-- MONITORING TARGET 60 HARI --}}
        <div class="flex flex-col rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-xs font-bold uppercase text-gray-700">Monitoring Target 60 Hari</h3>
            <div class="flex-1 overflow-x-auto">
                <table class="w-full text-[10px]">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-gray-400 uppercase">
                            <th class="py-1.5 pr-2 font-semibold">Indikator</th>
                            <th class="py-1.5 pr-2 font-semibold">Target</th>
                            <th class="py-1.5 pr-2 font-semibold">Kunjungan</th>
                            <th class="py-1.5 pr-2 font-semibold">Capaian</th>
                            <th class="py-1.5 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($monitoring as $row)
                            <tr class="border-b border-gray-50">
                                <td class="py-1.5 pr-2 font-semibold text-gray-700">{{ $row['name'] }}</td>
                                <td class="py-1.5 pr-2 text-gray-600">{{ $row['target'] }}</td>
                                <td class="py-1.5 pr-2 text-gray-600">{{ $row['kunjungan'] }}</td>
                                <td class="py-1.5 pr-2 text-gray-600">{{ $row['capaian'] }}</td>
                                <td class="py-1.5">
                                    @if ($row['status'] === 'On Track')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-1.5 py-0.5 text-[9px] font-semibold text-green-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>On Track
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-1.5 py-0.5 text-[9px] font-semibold text-red-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>Perlu Perhatian
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button class="mt-3 w-full rounded-lg bg-blue-700 py-2 text-xs font-semibold text-white transition hover:bg-blue-800">
                Lihat Dashboard Target >
            </button>
        </div>

        {{-- KUNJUNGAN PNPP HARI INI --}}
        <div class="flex flex-col rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-xs font-bold uppercase text-gray-700">Kunjungan PNPP Hari Ini</h3>
            <div class="flex flex-1 items-center gap-4">
                {{-- Angka besar --}}
                <div class="text-center flex-shrink-0">
                    <div class="text-4xl font-extrabold text-gray-800">{{ $kunjunganToday['total'] }}</div>
                    <p class="text-[10px] text-gray-400 mt-0.5">Total Kunjungan</p>
                </div>
                {{-- Icon gedung RS sederhana (SVG) --}}
                <div class="flex-1 flex justify-center">
                    <svg viewBox="0 0 96 80" class="h-20 w-auto" fill="none">
                        {{-- tanah --}}
                        <rect x="6" y="72" width="84" height="5" rx="2" fill="#cbd5e1"/>
                        {{-- gedung utama --}}
                        <rect x="28" y="20" width="40" height="50" rx="3" fill="#e2e8f0" stroke="#94a3b8" stroke-width="1.5"/>
                        {{-- sayap kiri --}}
                        <rect x="18" y="34" width="14" height="36" rx="2" fill="#f1f5f9" stroke="#94a3b8" stroke-width="1.5"/>
                        {{-- sayap kanan --}}
                        <rect x="64" y="34" width="14" height="36" rx="2" fill="#f1f5f9" stroke="#94a3b8" stroke-width="1.5"/>
                        {{-- atap --}}
                        <path d="M24 22 48 8 72 22 Z" fill="#60a5fa" stroke="#3b82f6" stroke-width="1.5"/>
                        {{-- jendela --}}
                        <rect x="32" y="28" width="8" height="8" rx="1.5" fill="#bae6fd"/>
                        <rect x="44" y="28" width="8" height="8" rx="1.5" fill="#bae6fd"/>
                        <rect x="56" y="28" width="8" height="8" rx="1.5" fill="#bae6fd"/>
                        <rect x="32" y="42" width="8" height="8" rx="1.5" fill="#bae6fd"/>
                        <rect x="44" y="42" width="8" height="8" rx="1.5" fill="#bae6fd"/>
                        <rect x="56" y="42" width="8" height="8" rx="1.5" fill="#bae6fd"/>
                        {{-- pintu --}}
                        <rect x="44" y="58" width="8" height="12" rx="1.5" fill="#3b82f6"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3 space-y-2">
                @foreach ($kunjunganToday['items'] as $k)
                    @php $col = $c[$k['color']]; @endphp
                    <div class="flex items-center justify-between rounded-lg {{ $col['light'] }} px-3 py-2">
                        <span class="flex items-center gap-2 text-[11px] font-medium text-gray-700">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full {{ $col['bg'] }} text-white">
                                @if ($k['label'] === 'IGD')
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                                @elseif ($k['label'] === 'Rawat Jalan')
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                @else
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                                @endif
                            </span>
                            {{ $k['label'] }}
                        </span>
                        <span class="text-lg font-extrabold {{ $col['text'] }}">{{ $k['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ALERT & NOTIFIKASI --}}
        <div class="flex flex-col rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-xs font-bold uppercase text-gray-700">Alert & Notifikasi</h3>
            <div class="flex-1 space-y-3">
                @foreach ($alerts as $al)
                    @php $col = $c[$al['color']]; @endphp
                    <div class="flex items-start gap-3 rounded-lg {{ $col['light'] }} p-3">
                        <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full {{ $col['bg'] }} text-white">
                            @if ($al['color'] === 'red')
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                            @elseif ($al['color'] === 'yellow')
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                            @else
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                            @endif
                        </span>
                        <div class="min-w-0 flex-1 leading-tight">
                            <p class="text-[11px] font-semibold text-gray-700">{{ $al['title'] }}</p>
                            <p class="text-[10px] text-gray-400">{{ $al['count'] }} Kasus</p>
                        </div>
                    </div>
                @endforeach
            </div>
            <button class="mt-3 w-full rounded-lg border border-blue-200 bg-blue-50 py-2 text-xs font-semibold text-blue-600 transition hover:bg-blue-100">
                Lihat Semua Alert >
            </button>
        </div>
    </div>

</div>

{{-- HIGHCHARTS --}}
<script src="https://code.highcharts.com/highcharts.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // ===== Trend kunjungan (line) =====
        Highcharts.chart('trendChart', {
            chart: { type: 'line', height: 190, spacing: [5, 5, 5, 0], backgroundColor: 'transparent' },
            title: { text: null },
            colors: @json(collect($trend['series'])->pluck('color')),
            xAxis: {
                categories: @json($trend['months']),
                lineColor: '#e5e7eb',
                tickColor: '#e5e7eb',
                labels: { style: { fontSize: '10px', color: '#6b7280' } }
            },
            yAxis: {
                min: 0, max: 100,
                tickInterval: 20,
                gridLineColor: '#f3f4f6',
                labels: { style: { fontSize: '10px', color: '#9ca3af' } },
                title: { text: null }
            },
            legend: { enabled: false },
            credits: { enabled: false },
            plotOptions: {
                line: {
                    marker: { enabled: true, radius: 3.5, symbol: 'circle' },
                    lineWidth: 2.5
                }
            },
            tooltip: {
                shared: true,
                borderRadius: 8,
                shadow: true,
                style: { fontSize: '11px' }
            },
            series: @json($trend['series'])
        });

        // ===== Status follow-up (donut) =====
        Highcharts.chart('donutChart', {
            chart: { type: 'pie', height: 160, spacing: [0, 0, 0, 0], backgroundColor: 'transparent' },
            title: {
                text: 'Total<br><b style="font-size:18px;color:#1e293b">{{ $followup['total'] }}</b>',
                align: 'center', verticalAlign: 'middle',
                y: 0, useHTML: true,
                style: { fontSize: '11px', color: '#6b7280', fontWeight: 'normal' }
            },
            credits: { enabled: false },
            tooltip: {
                pointFormat: '<b>{point.percentage:.1f}%</b>',
                borderRadius: 8
            },
            plotOptions: {
                pie: {
                    innerSize: '65%',
                    dataLabels: { enabled: false },
                    showInLegend: false,
                    borderWidth: 0,
                    states: { hover: { brightness: 0.05 } }
                }
            },
            series: [{
                type: 'pie',
                name: 'Status Follow-up',
                center: ['50%', '50%'],
                data: @json(collect($followup['series'])->map(function ($s) {
                    return ['name' => $s['name'], 'y' => $s['value'], 'color' => $s['color']];
                })->values())
            }],
            accessibility: { enabled: false }
        });
    });
</script>
@endsection
