@extends('layouts.app')

@section('title', 'Hari Libur')
@section('page-title', 'Hari Libur')

@section('content')
@php
    $totalHolidays = $hariLiburs->count();
    $manualCount = $hariLiburs->where('sumber', \App\Models\HariLibur::SUMBER_MANUAL)->count();
    $googleCount = $hariLiburs->where('sumber', \App\Models\HariLibur::SUMBER_GOOGLE_CALENDAR)->count();
    
    // Hari libur bulan ini
    $now = now();
    $thisMonthCount = $hariLiburs->filter(fn ($h) => $h->tanggal && $h->tanggal->format('Y-m') === $now->format('Y-m'))->count();

    // Hari libur mendatang (>= hari ini) urut dari yang terdekat
    $upcomingHolidays = $hariLiburs->filter(fn ($h) => $h->tanggal && $h->tanggal->gte($now->startOfDay()))
        ->sortBy('tanggal')
        ->take(5);

    $stats = [
        ['label' => 'Total Hari Libur', 'value' => $totalHolidays, 'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5', 'color' => 'sky'],
        ['label' => 'Bulan Ini (' . $now->translatedFormat('F') . ')', 'value' => $thisMonthCount, 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'color' => 'rose'],
        ['label' => 'Input Manual', 'value' => $manualCount, 'icon' => 'M16.862 4.487l-8.53 8.53a2.25 2.25 0 0 0-.53.823.75.75 0 0 0-.06.314v2.586h2.586c.112 0 .223-.021.314-.06a2.25 2.25 0 0 0 .823-.53l8.53-8.53m-6.687-6.14 1.5-1.5a2.25 2.25 0 0 1 3.182 0l1.5 1.5a2.25 2.25 0 0 1 0 3.182l-1.5 1.5m-6.687-6.14L4.272 8.234a2.25 2.25 0 0 0-.53.823.75.75 0 0 0-.06.314v2.586h2.586c.112 0 .223-.021.314-.06a2.25 2.25 0 0 0 .823-.53l1.5-1.5', 'color' => 'violet'],
        ['label' => 'Google Calendar', 'value' => $googleCount, 'icon' => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99', 'color' => 'emerald'],
    ];
    $statColor = [
        'sky'     => ['bg-sky-50',    'text-sky-600'],
        'rose'    => ['bg-rose-50',   'text-rose-600'],
        'violet'  => ['bg-violet-50', 'text-violet-600'],
        'emerald' => ['bg-emerald-50','text-emerald-600'],
    ];
@endphp

<style>
    /* FullCalendar Custom Theme Overrides to Match Tailwind UI */
    .fc {
        font-family: inherit;
        --fc-border-color: #f1f5f9;
        --fc-today-bg-color: #f0f9ff;
        --fc-page-bg-color: #ffffff;
        --fc-neutral-bg-color: #f8fafc;
        --fc-list-event-hover-bg-color: #f8fafc;
        --fc-small-font-size: 0.75rem;
    }
    .fc .fc-toolbar {
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-bottom: 1.25rem !important;
    }
    .fc .fc-toolbar-title {
        font-size: 1.125rem !important;
        font-weight: 800 !important;
        color: #0f172a;
        letter-spacing: -0.015em;
    }
    .fc .fc-button-primary {
        background-color: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        color: #334155 !important;
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        border-radius: 0.625rem !important;
        padding: 0.45rem 0.85rem !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        transition: all 0.15s ease;
        text-transform: capitalize;
    }
    .fc .fc-button-primary:hover {
        background-color: #f8fafc !important;
        color: #0284c7 !important;
        border-color: #cbd5e1 !important;
    }
    .fc .fc-button-primary:focus,
    .fc .fc-button-primary:active,
    .fc .fc-button-primary.fc-button-active {
        background-color: #0284c7 !important;
        color: #ffffff !important;
        border-color: #0284c7 !important;
        box-shadow: 0 1px 3px 0 rgba(2, 132, 199, 0.3) !important;
    }
    .fc .fc-button-group {
        border-radius: 0.625rem;
        overflow: hidden;
        gap: 2px;
    }
    .fc .fc-button-group > .fc-button {
        border-radius: 0.625rem !important;
    }
    .fc-theme-standard th {
        background-color: #f8fafc;
        border-color: #f1f5f9 !important;
        padding: 0.65rem 0.25rem !important;
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b !important;
    }
    .fc-theme-standard td {
        border-color: #f1f5f9 !important;
    }
    .fc .fc-daygrid-day-top {
        padding: 0.35rem 0.5rem 0.15rem;
    }
    .fc .fc-daygrid-day-number {
        font-size: 0.75rem;
        font-weight: 700;
        color: #475569;
        padding: 0.2rem 0.4rem;
        border-radius: 0.375rem;
    }
    .fc .fc-day-today .fc-daygrid-day-number {
        background-color: #0284c7;
        color: #ffffff;
    }
    .fc .fc-daygrid-day-frame {
        min-height: 95px !important;
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    .fc .fc-daygrid-day-frame:hover {
        background-color: #f8fafc;
    }
    .fc .fc-day-other .fc-daygrid-day-number {
        color: #cbd5e1;
    }
    /* Event pill styling */
    .fc-event {
        border-radius: 0.5rem !important;
        padding: 2px 6px !important;
        font-size: 0.7rem !important;
        font-weight: 600 !important;
        margin: 2px 4px !important;
        border: none !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        cursor: pointer !important;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .fc-event:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .fc-event-main {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .fc .fc-popover {
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        z-index: 40;
    }
    .fc .fc-popover-header {
        background: #f8fafc;
        padding: 6px 12px;
        font-weight: 700;
        font-size: 0.75rem;
    }
</style>

<div x-data="hariLiburApp()" class="space-y-6">

    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-600 text-white shadow-md shadow-sky-600/20">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-slate-900">Manajemen Hari Libur</h2>
                    <p class="text-xs text-slate-500">Hari libur membatalkan jam buka poli — pendaftaran PNPP pada tanggal terkait otomatis ditolak.</p>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <form method="POST" action="{{ route('admin.hari-libur.sync') }}" @submit="syncing = true">
                @csrf
                <button type="submit" :disabled="syncing"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-xs transition hover:bg-slate-50 hover:text-sky-600 disabled:opacity-50 cursor-pointer">
                    <svg x-show="!syncing" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                    <svg x-show="syncing" x-cloak class="h-4 w-4 animate-spin text-sky-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-text="syncing ? 'Menyinkronkan...' : 'Sinkron Google Calendar'">Sinkron Google Calendar</span>
                </button>
            </form>
            <a href="{{ route('admin.hari-libur.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm shadow-sky-600/30 transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Tambah Hari Libur
            </a>
        </div>
    </div>

    {{-- ===== Statistik Ringkas ===== --}}
    <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($stats as $stat)
            @php $c = $statColor[$stat['color']]; @endphp
            <div class="flex items-center gap-3.5 rounded-2xl bg-white p-4.5 shadow-xs ring-1 ring-slate-200 transition-all hover:shadow-md">
                <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl {{ $c[0] }} {{ $c[1] }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $stat['icon'] }}" /></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-2xl font-extrabold leading-tight tabular-nums text-slate-900">{{ $stat['value'] }}</p>
                    <p class="truncate text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $stat['label'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== Tab Switcher ===== --}}
    <div class="flex items-center justify-between border-b border-slate-200 pb-1">
        <div class="inline-flex rounded-xl bg-slate-100 p-1 ring-1 ring-slate-200/70">
            {{-- Tab 1: Kalender (Default) --}}
            <button type="button" @click="setTab('calendar')"
                    :class="activeTab === 'calendar' ? 'bg-white text-sky-600 shadow-xs font-bold' : 'text-slate-500 hover:text-slate-800 font-semibold'"
                    class="flex items-center gap-2 rounded-lg px-4 py-2 text-xs transition cursor-pointer">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <span>Kalender Hari Libur</span>
                <span class="rounded-full px-1.5 py-0.2 text-[10px] font-bold"
                      :class="activeTab === 'calendar' ? 'bg-sky-100 text-sky-700' : 'bg-slate-200 text-slate-600'">
                    {{ $totalHolidays }}
                </span>
            </button>

            {{-- Tab 2: Tabel Data --}}
            <button type="button" @click="setTab('table')"
                    :class="activeTab === 'table' ? 'bg-white text-sky-600 shadow-xs font-bold' : 'text-slate-500 hover:text-slate-800 font-semibold'"
                    class="flex items-center gap-2 rounded-lg px-4 py-2 text-xs transition cursor-pointer">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                </svg>
                <span>Daftar Tabel</span>
                <span class="rounded-full px-1.5 py-0.2 text-[10px] font-bold"
                      :class="activeTab === 'table' ? 'bg-sky-100 text-sky-700' : 'bg-slate-200 text-slate-600'">
                    {{ $totalHolidays }}
                </span>
            </button>
        </div>

        <div class="hidden text-xs text-slate-400 sm:block">
            <span x-show="activeTab === 'calendar'">💡 Klik tanggal untuk tambah cepat, klik kartu libur untuk detail</span>
            <span x-show="activeTab === 'table'" x-cloak>💡 Cari & filter tabel dengan sorting tanggal</span>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- TAB 1: KALENDER VIEW (AKTIF SECARA DEFAULT)               --}}
    {{-- ======================================================== --}}
    <div x-show="activeTab === 'calendar'" class="space-y-6">

        {{-- Filter Cepat Kalender --}}
        <div class="flex flex-col gap-3 rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-200 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap items-center gap-2.5">
                <span class="text-xs font-bold text-slate-700">Filter Kalender:</span>
                
                {{-- Filter Cakupan Poli --}}
                <select x-model="calPoliFilter" @change="refreshCalendarEvents()"
                        class="rounded-xl border-0 bg-slate-50/80 py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                    <option value="all">Semua Cakupan Poli</option>
                    <option value="semua_poli">Khusus: Semua Poli Saja</option>
                    @foreach ($polis as $p)
                        <option value="{{ $p->nama }}">{{ $p->nama }}</option>
                    @endforeach
                </select>

                {{-- Filter Sumber --}}
                <select x-model="calSumberFilter" @change="refreshCalendarEvents()"
                        class="rounded-xl border-0 bg-slate-50/80 py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                    <option value="all">Semua Sumber</option>
                    <option value="manual">Manual Saja</option>
                    <option value="google_calendar">Google Calendar Saja</option>
                </select>
            </div>

            <div class="flex items-center gap-3">
                {{-- Mini Legend --}}
                <div class="flex items-center gap-3 text-[11px] font-semibold text-slate-600">
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span> Semua Poli</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-violet-500"></span> Khusus Poli</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> Google Cal</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">

            {{-- Kolom Kalender Utama (3 Kolom) --}}
            <div class="lg:col-span-3">
                <div class="overflow-hidden rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-200">
                    <div id="fullcalendar-container"></div>
                </div>
            </div>

            {{-- Kolom Samping (1 Kolom): Upcoming Holidays & Legend --}}
            <div class="space-y-4 lg:col-span-1">
                
                {{-- Card Hari Libur Terdekat --}}
                <div class="overflow-hidden rounded-2xl bg-white shadow-xs ring-1 ring-slate-200">
                    <div class="border-b border-slate-100 bg-slate-50/70 px-4.5 py-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Libur Mendatang</h3>
                            <span class="rounded-md bg-sky-100 px-1.5 py-0.5 text-[10px] font-bold text-sky-700">{{ $upcomingHolidays->count() }}</span>
                        </div>
                    </div>
                    <div class="divide-y divide-slate-100 p-2">
                        @forelse ($upcomingHolidays as $uh)
                            @php
                                $diffDays = (int) now()->startOfDay()->diffInDays($uh->tanggal->startOfDay(), false);
                                $diffText = match(true) {
                                    $diffDays === 0 => 'Hari Ini',
                                    $diffDays === 1 => 'Besok',
                                    $diffDays > 1 && $diffDays <= 7 => "Dalam {$diffDays} hari",
                                    default => $uh->tanggal->translatedFormat('d M Y')
                                };
                                $uhBadgeColor = $uh->poli ? 'bg-violet-50 text-violet-700 ring-violet-200' : 'bg-rose-50 text-rose-700 ring-rose-200';
                            @endphp
                            <div class="flex cursor-pointer items-start gap-3 rounded-xl p-2.5 transition hover:bg-slate-50"
                                 @click="showDetailById({{ $uh->id }})">
                                <div class="flex h-10 w-10 flex-shrink-0 flex-col items-center justify-center rounded-xl bg-slate-100 text-center ring-1 ring-slate-200">
                                    <span class="text-[9px] font-extrabold uppercase text-slate-400 leading-none">{{ $uh->tanggal->translatedFormat('M') }}</span>
                                    <span class="text-sm font-extrabold text-slate-800 leading-tight">{{ $uh->tanggal->format('d') }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-bold text-slate-900" title="{{ $uh->nama }}">{{ $uh->nama }}</p>
                                    <div class="mt-1 flex flex-wrap items-center gap-1">
                                        <span class="inline-flex items-center rounded-md px-1.5 py-0.2 text-[9px] font-bold ring-1 ring-inset {{ $uhBadgeColor }}">
                                            {{ $uh->poli?->nama ?? 'Semua Poli' }}
                                        </span>
                                        <span class="text-[10px] font-semibold text-slate-400">{{ $diffText }}</span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-6 text-center text-xs text-slate-400">
                                Tidak ada hari libur mendatang dalam waktu dekat.
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Panduan & Aksi Cepat --}}
                <div class="rounded-2xl bg-sky-50/70 p-4 text-xs leading-relaxed text-sky-900 ring-1 ring-sky-200/70">
                    <div class="flex items-center gap-1.5 font-bold text-sky-950">
                        <svg class="h-4 w-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                        Interaksi Kalender:
                    </div>
                    <ul class="mt-2 space-y-1.5 text-sky-800 text-[11px]">
                        <li class="flex items-start gap-1.5">
                            <span class="font-bold text-sky-600">•</span>
                            <span><strong>Klik kotak tanggal</strong> untuk langsung membuat hari libur baru pada tanggal tersebut.</span>
                        </li>
                        <li class="flex items-start gap-1.5">
                            <span class="font-bold text-sky-600">•</span>
                            <span><strong>Klik label libur</strong> untuk membuka detail, mengedit, atau menghapus data.</span>
                        </li>
                    </ul>
                </div>

            </div>

        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- TAB 2: TABEL DATA VIEW                                    --}}
    {{-- ======================================================== --}}
    <div x-show="activeTab === 'table'" x-cloak class="space-y-4">
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/60 p-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="relative w-full lg:max-w-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    </div>
                    <input x-model.debounce.300ms="search" type="text"
                           class="block w-full rounded-xl border-0 bg-white py-2.5 pl-10 pr-9 text-xs text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition"
                           placeholder="Cari nama, tanggal, atau poli...">
                    <button x-show="search" x-cloak @click="search = ''" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <select x-model="sumber" class="rounded-xl border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                        <option value="all">Semua Sumber</option>
                        <option value="manual">Manual</option>
                        <option value="google_calendar">Google Calendar</option>
                    </select>
                    <select x-model="sortBy" class="rounded-xl border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                        <option value="tanggal_desc">Tanggal Terbaru</option>
                        <option value="tanggal_asc">Tanggal Terlama</option>
                        <option value="nama_az">Nama A–Z</option>
                    </select>
                    <select x-model.number="perPage" class="rounded-xl border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                        <option value="10">10 per halaman</option>
                        <option value="25">25 per halaman</option>
                        <option value="50">50 per halaman</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <th class="px-6 py-3.5">Tanggal</th>
                            <th class="px-6 py-3.5">Nama Hari Libur</th>
                            <th class="px-6 py-3.5">Cakupan Poli</th>
                            <th class="px-6 py-3.5">Sumber Data</th>
                            <th class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <template x-for="h in paginatedHariLiburs" :key="h.id">
                            <tr class="group bg-white transition-colors hover:bg-sky-50/40">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="text-xs font-bold text-slate-900" x-text="h.tanggalLabel"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs font-semibold text-slate-800" x-text="h.nama"></span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <template x-if="h.poli">
                                        <span class="inline-flex items-center rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-bold text-violet-700 ring-1 ring-inset ring-violet-200" x-text="h.poli"></span>
                                    </template>
                                    <template x-if="!h.poli">
                                        <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-700 ring-1 ring-inset ring-rose-200">Semua Poli</span>
                                    </template>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <template x-if="h.sumber === 'google_calendar'">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Google Calendar
                                        </span>
                                    </template>
                                    <template x-if="h.sumber === 'manual'">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700 ring-1 ring-inset ring-slate-200">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                            Manual
                                        </span>
                                    </template>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a :href="h.editUrl" title="Edit Hari Libur"
                                           class="rounded-lg p-2 text-slate-400 transition-all hover:bg-sky-50 hover:text-sky-600">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                        </a>
                                        <button type="button" @click="askDelete(h)" title="Hapus Hari Libur"
                                                class="rounded-lg p-2 text-slate-400 transition-all hover:bg-rose-50 hover:text-rose-600 cursor-pointer">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="filteredHariLiburs.length === 0" x-cloak>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 ring-8 ring-slate-50">
                                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-900">Tidak ada hari libur ditemukan</h3>
                                    <p class="mt-1 text-xs text-slate-500">Ubah kata kunci/filter atau tambah hari libur baru.</p>
                                    <button type="button" @click="search = ''; sumber = 'all'" class="mt-4 rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-700 cursor-pointer">Reset Pencarian</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:flex-row" x-show="filteredHariLiburs.length > 0" x-cloak>
                <p class="text-xs text-slate-500">
                    Menampilkan <span class="font-bold text-slate-800" x-text="startIndex + 1"></span>–<span class="font-bold text-slate-800" x-text="endIndex"></span>
                    dari <span class="font-bold text-slate-800" x-text="filteredHariLiburs.length"></span> hari libur
                </p>
                <div class="flex items-center gap-1">
                    <button type="button" @click="prevPage" :disabled="currentPage === 1" class="rounded-lg bg-white p-2 text-slate-500 shadow-xs ring-1 ring-slate-200 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 cursor-pointer">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                    </button>
                    <template x-for="page in totalPages" :key="page">
                        <button type="button" @click="currentPage = page" class="h-8 min-w-8 rounded-lg px-2 text-xs font-bold tabular-nums transition cursor-pointer"
                                :class="currentPage === page ? 'bg-sky-600 text-white shadow-sm' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'" x-text="page"></button>
                    </template>
                    <button type="button" @click="nextPage" :disabled="currentPage === totalPages" class="rounded-lg bg-white p-2 text-slate-500 shadow-xs ring-1 ring-slate-200 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 cursor-pointer">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== MODAL DETAIL HARI LIBUR ===== --}}
    <div x-show="detailModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         role="dialog" aria-modal="true">
        {{-- Backdrop --}}
        <div x-show="detailModalOpen"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="detailModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>

        {{-- Dialog --}}
        <div x-show="detailModalOpen"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200">
            
            <div class="border-b border-slate-100 p-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl text-white shadow-xs"
                             :class="selectedHoliday?.poli ? 'bg-violet-600' : (selectedHoliday?.sumber === 'google_calendar' ? 'bg-emerald-600' : 'bg-rose-500')">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Detail Hari Libur</h3>
                            <p class="text-[11px] text-slate-400" x-text="selectedHoliday?.tanggalLabel"></p>
                        </div>
                    </div>
                    <button type="button" @click="detailModalOpen = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 cursor-pointer">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>

            <div class="space-y-4 p-5 text-xs">
                <div>
                    <label class="font-bold text-slate-400 uppercase tracking-wider text-[10px]">Nama Libur / Keterangan</label>
                    <p class="mt-0.5 text-base font-extrabold text-slate-900" x-text="selectedHoliday?.nama"></p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-100">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Cakupan Layanan</p>
                        <p class="mt-1 font-bold text-slate-800" x-text="selectedHoliday?.poli || 'Semua Poli (Nasional/RS)'"></p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-100">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Sumber Data</p>
                        <p class="mt-1 font-bold text-slate-800 capitalize" x-text="selectedHoliday?.sumber === 'google_calendar' ? 'Google Calendar' : 'Input Manual'"></p>
                    </div>
                </div>

                <div class="rounded-xl bg-amber-50 p-3 text-amber-800 ring-1 ring-amber-200/70">
                    <p class="font-medium text-[11px]">
                        ℹ️ Selama tanggal ini, pendaftaran antrean PNPP pada poli terkait akan otomatis ditolak.
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50/70 px-5 py-3.5">
                <button type="button" @click="detailModalOpen = false; askDelete(selectedHoliday)"
                        class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-600 hover:text-rose-700 cursor-pointer">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                    Hapus
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" @click="detailModalOpen = false" class="rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">Tutup</button>
                    <a :href="selectedHoliday?.editUrl"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-sky-600 px-4 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-sky-700">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                        Edit
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- FullCalendar Library (Offline local asset with graceful fallback) --}}
<script src="{{ asset('vendor/fullcalendar/fullcalendar.min.js') }}"></script>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('hariLiburApp', () => ({
            activeTab: 'calendar', // DEFAULT TAB IS CALENDAR
            search: '',
            sumber: 'all',
            sortBy: 'tanggal_desc',
            perPage: 10,
            currentPage: 1,
            syncing: false,

            // Calendar filters & state
            calPoliFilter: 'all',
            calSumberFilter: 'all',
            calendarInstance: null,
            detailModalOpen: false,
            selectedHoliday: null,

            hariLiburs: [
                @foreach ($hariLiburs as $h)
                {
                    id: {{ $h->id }},
                    tanggal: "{{ $h->tanggal->format('Y-m-d') }}",
                    tanggalLabel: @js($h->tanggal->translatedFormat('l, d M Y')),
                    nama: @js($h->nama),
                    poli: @js($h->poli?->nama) ?? null,
                    sumber: @js($h->sumber),
                    editUrl: "{{ route('admin.hari-libur.edit', $h->id) }}",
                    deleteUrl: "{{ route('admin.hari-libur.destroy', $h->id) }}",
                }{{ ! $loop->last ? ',' : '' }}
                @endforeach
            ],

            init() {
                this.$nextTick(() => {
                    this.initCalendar();
                });
            },

            setTab(tab) {
                this.activeTab = tab;
                if (tab === 'calendar') {
                    this.$nextTick(() => {
                        if (this.calendarInstance) {
                            this.calendarInstance.updateSize();
                        } else {
                            this.initCalendar();
                        }
                    });
                }
            },

            get filteredEvents() {
                return this.hariLiburs.filter(h => {
                    // Filter Poli
                    if (this.calPoliFilter === 'semua_poli' && h.poli !== null) return false;
                    if (this.calPoliFilter !== 'all' && this.calPoliFilter !== 'semua_poli' && h.poli !== this.calPoliFilter) return false;
                    
                    // Filter Sumber
                    if (this.calSumberFilter !== 'all' && h.sumber !== this.calSumberFilter) return false;

                    return true;
                }).map(h => {
                    let bgColor = '#f43f5e'; // Rose 500 (Semua poli)
                    let textColor = '#ffffff';

                    if (h.poli) {
                        bgColor = '#8b5cf6'; // Violet 500 (Poli spesifik)
                    } else if (h.sumber === 'google_calendar') {
                        bgColor = '#10b981'; // Emerald 500 (Google Calendar)
                    }

                    return {
                        id: String(h.id),
                        title: (h.poli ? `[${h.poli}] ` : '') + h.nama,
                        start: h.tanggal,
                        allDay: true,
                        backgroundColor: bgColor,
                        borderColor: bgColor,
                        textColor: textColor,
                        extendedProps: {
                            raw: h
                        }
                    };
                });
            },

            initCalendar() {
                const el = document.getElementById('fullcalendar-container');
                if (!el || typeof FullCalendar === 'undefined') return;

                const self = this;

                this.calendarInstance = new FullCalendar.Calendar(el, {
                    initialView: 'dayGridMonth',
                    locale: 'id',
                    firstDay: 1, // Start on Monday
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,listMonth'
                    },
                    buttonText: {
                        today: 'Hari Ini',
                        month: 'Bulan',
                        list: 'Daftar'
                    },
                    height: 'auto',
                    events: self.filteredEvents,
                    eventClick: function(info) {
                        const raw = info.event.extendedProps.raw;
                        if (raw) {
                            self.selectedHoliday = raw;
                            self.detailModalOpen = true;
                        }
                    },
                    dateClick: function(info) {
                        // Redirect to create holiday with pre-selected date
                        window.location.href = "{{ route('admin.hari-libur.create') }}?tanggal=" + info.dateStr;
                    },
                    dayMaxEvents: 3
                });

                this.calendarInstance.render();
            },

            refreshCalendarEvents() {
                if (!this.calendarInstance) return;
                this.calendarInstance.removeAllEvents();
                this.calendarInstance.addEventSource(this.filteredEvents);
            },

            showDetailById(id) {
                const found = this.hariLiburs.find(h => h.id === id);
                if (found) {
                    this.selectedHoliday = found;
                    this.detailModalOpen = true;
                }
            },

            get filteredHariLiburs() {
                let result = this.hariLiburs.filter(h => {
                    if (this.sumber !== 'all' && h.sumber !== this.sumber) return false;
                    const q = this.search.toLowerCase().trim();
                    if (
                        q &&
                        !h.nama.toLowerCase().includes(q) &&
                        !h.tanggalLabel.toLowerCase().includes(q) &&
                        !(h.poli ?? '').toLowerCase().includes(q)
                    ) return false;
                    return true;
                });

                if (this.sortBy === 'tanggal_desc') result.sort((a, b) => b.tanggal.localeCompare(a.tanggal));
                if (this.sortBy === 'tanggal_asc')  result.sort((a, b) => a.tanggal.localeCompare(b.tanggal));
                if (this.sortBy === 'nama_az')      result.sort((a, b) => a.nama.localeCompare(b.nama));

                return result;
            },

            get totalPages()          { return Math.max(1, Math.ceil(this.filteredHariLiburs.length / this.perPage)); },
            get startIndex()          { return (this.currentPage - 1) * this.perPage; },
            get endIndex()            { return Math.min(this.startIndex + this.perPage, this.filteredHariLiburs.length); },
            get paginatedHariLiburs() { return this.filteredHariLiburs.slice(this.startIndex, this.endIndex); },

            prevPage() { if (this.currentPage > 1) this.currentPage--; },
            nextPage() { if (this.currentPage < this.totalPages) this.currentPage++; },

            askDelete(h) {
                if (!h) return;
                confirmSubmit(h.deleteUrl, {
                    title: 'Hapus Hari Libur?',
                    html: `<div class="text-left text-xs mt-2">
                               <p class="text-slate-600">Apakah Anda yakin ingin menghapus <strong class="text-slate-900">${h.nama}</strong> pada <strong class="text-slate-900">${h.tanggalLabel}</strong>?</p>
                               <p class="mt-3 rounded-lg bg-slate-50 p-2.5 text-[11px] text-slate-500 ring-1 ring-slate-200">Data hasil sinkron Google Calendar akan kembali muncul saat sinkronisasi berikutnya.</p>
                           </div>`,
                    confirmText: 'Ya, hapus',
                });
            }
        }));
    });
</script>
@endsection