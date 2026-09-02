@extends('layouts.app')

@section('title', 'Digital Reminder')
@section('page-title', 'Digital Reminder')

@section('content')
@php
    $stats = [
        ['label' => 'Total Reminder', 'value' => '356', 'tone' => 'sky',     'icon' => 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0'],
        ['label' => 'Terkirim',       'value' => '298', 'tone' => 'emerald', 'icon' => 'M4.5 12.75l6 6 9-13.5'],
        ['label' => 'Terjadwal',      'value' => '41',  'tone' => 'amber',   'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['label' => 'Gagal',          'value' => '17',  'tone' => 'rose',    'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
    ];

    $toneColor = [
        'sky'     => ['bg' => 'bg-sky-50',     'text' => 'text-sky-600'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
        'amber'   => ['bg' => 'bg-amber-50',   'text' => 'text-amber-600'],
        'rose'    => ['bg' => 'bg-rose-50',    'text' => 'text-rose-600'],
    ];

    $tabs = [
        ['label' => 'Semua',     'count' => 356, 'active' => true],
        ['label' => 'Terjadwal', 'count' => 41,  'active' => false],
        ['label' => 'Terkirim',  'count' => 298, 'active' => false],
        ['label' => 'Gagal',     'count' => 17,  'active' => false],
    ];

    $rows = [
        ['penerima' => 'Budi Santoso', 'no_hp' => '081234567890', 'pesan' => 'Apakah Anda akan datang untuk kontrol besok? Balas "ya" atau "tidak".', 'jadwal' => 'Hari ini, 07:30', 'status' => 'Terkirim'],
        ['penerima' => 'Siti Aminah',  'no_hp' => '081298765432', 'pesan' => 'Jadwal kontrol asma Anda besok pukul 09:00 di Poli Umum.',               'jadwal' => 'Hari ini, 08:00', 'status' => 'Terkirim'],
        ['penerima' => 'Agus Wijaya',  'no_hp' => '081377445566', 'pesan' => 'Hasil pemeriksaan jantung sudah tersedia. Segera konsultasi.',          'jadwal' => 'Hari ini, 09:15', 'status' => 'Terjadwal'],
        ['penerima' => 'Dewi Lestari', 'no_hp' => '082112345678', 'pesan' => 'Pengingat kontrol anak di Poli Anak. Jangan lupa membawa kartu BPJS.',    'jadwal' => 'Besok, 07:00',   'status' => 'Terjadwal'],
        ['penerima' => 'Rudi Hartono', 'no_hp' => '085655667788', 'pesan' => 'Kontrol rutin tekanan darah Anda. Tekan untuk konfirmasi hadir.',       'jadwal' => 'Hari ini, 10:30', 'status' => 'Gagal'],
        ['penerima' => 'Lina Marlina', 'no_hp' => '081912223344', 'pesan' => 'Pengambilan obat asam urat. Hormat kami RS Bhayangkara Bogor.',        'jadwal' => 'Hari ini, 11:00', 'status' => 'Terkirim'],
    ];

    $statusStyle = [
        'Terkirim'  => 'bg-emerald-50 text-emerald-700 ring-emerald-200/70',
        'Terjadwal' => 'bg-amber-50 text-amber-700 ring-amber-200/70',
        'Gagal'     => 'bg-rose-50 text-rose-700 ring-rose-200/70',
    ];

    $pengiriman = [
        ['label' => 'Terkirim',  'jumlah' => 298, 'bar' => 'bg-emerald-500', 'width' => 'w-4/5'],
        ['label' => 'Terjadwal', 'jumlah' => 41,  'bar' => 'bg-amber-500',   'width' => 'w-1/5'],
        ['label' => 'Gagal',     'jumlah' => 17,  'bar' => 'bg-rose-500',    'width' => 'w-1/12'],
    ];

    $templates = [
        ['judul' => 'Pengingat Kontrol', 'teks' => 'Halo {nama}, jadwal kontrol Anda…'],
        ['judul' => 'Pengingat Obat',    'teks' => 'Jangan lupa minum obat rutin…'],
        ['judul' => 'Konfirmasi Hadir',  'teks' => 'Apakah Anda akan datang? Balas "ya"…'],
    ];
@endphp
<div class="space-y-6">

    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Digital Reminder</h2>
            <p class="mt-0.5 text-sm text-slate-500">Broadcast pengingat otomatis kepada PNPP melalui WhatsApp.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.digital-reminder.import') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                Import
            </a>
            <a href="{{ route('admin.digital-reminder.template') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" /></svg>
                Template Pesan
            </a>
            <a href="{{ route('admin.digital-reminder.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Tambah Reminder
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
                    <p class="text-lg font-extrabold leading-tight tabular-nums text-slate-900">{{ $s['value'] }}</p>
                    <p class="truncate text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ $s['label'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ===== Tabel Reminder ===== --}}
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
                        <input type="text" placeholder="Cari penerima atau pesan..."
                               class="block w-full rounded-xl border-0 bg-white py-2.5 pl-10 pr-9 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition">
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <select class="rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                            <option>Semua Status</option>
                            <option>Terkirim</option>
                            <option>Terjadwal</option>
                            <option>Gagal</option>
                        </select>
                        <input type="date" class="rounded-lg border-0 bg-white py-2 px-3 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500">
                    </div>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                                <th class="px-6 py-3.5 font-semibold">Penerima</th>
                                <th class="px-6 py-3.5 font-semibold">Pesan</th>
                                <th class="px-6 py-3.5 font-semibold">Jadwal</th>
                                <th class="px-6 py-3.5 font-semibold">Status</th>
                                <th class="px-6 py-3.5 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach ($rows as $r)
                                <tr class="group bg-white transition-colors hover:bg-sky-50/40">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold uppercase text-emerald-700">
                                                {{ substr($r['penerima'], 0, 1) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-bold text-slate-900">{{ $r['penerima'] }}</p>
                                                <p class="truncate font-mono text-xs text-slate-400">{{ $r['no_hp'] }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="max-w-md truncate text-sm text-slate-600" title="{{ $r['pesan'] }}">{{ $r['pesan'] }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="text-xs font-semibold text-slate-500">{{ $r['jadwal'] }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $statusStyle[$r['status']] }}">
                                            {{ $r['status'] }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5 opacity-60 transition-opacity group-hover:opacity-100 lg:opacity-0">
                                            <button title="Kirim Ulang" class="rounded-lg p-2 text-slate-400 transition-all hover:bg-emerald-50 hover:text-emerald-600 focus:opacity-100">
                                                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                            </button>
                                            <a href="{{ route('admin.digital-reminder.edit', 1) }}" title="Edit" class="rounded-lg p-2 text-slate-400 transition-all hover:bg-sky-50 hover:text-sky-600 focus:opacity-100">
                                                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                            </a>
                                            <button title="Hapus" class="rounded-lg p-2 text-slate-400 transition-all hover:bg-rose-50 hover:text-rose-600 focus:opacity-100">
                                                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                            </button>
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
                        dari <span class="font-bold text-slate-800">356</span> reminder
                    </p>
                    <div class="flex items-center gap-1">
                        <button class="rounded-lg bg-white p-2 text-slate-500 shadow-xs ring-1 ring-slate-200 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40" disabled>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                        </button>
                        <button class="h-8 min-w-8 rounded-lg bg-sky-600 px-2 text-xs font-bold tabular-nums text-white shadow-sm">1</button>
                        <button class="h-8 min-w-8 rounded-lg bg-white px-2 text-xs font-bold tabular-nums text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">2</button>
                        <button class="h-8 min-w-8 rounded-lg bg-white px-2 text-xs font-bold tabular-nums text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">3</button>
                        <span class="px-1 text-xs font-bold text-slate-400">…</span>
                        <button class="h-8 min-w-8 rounded-lg bg-white px-2 text-xs font-bold tabular-nums text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">60</button>
                        <button class="rounded-lg bg-white p-2 text-slate-500 shadow-xs ring-1 ring-slate-200 transition hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Sidebar ===== --}}
        <div class="space-y-6">
            {{-- Status Pengiriman --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-bold text-slate-900">Status Pengiriman</h3>
                </div>
                <div class="space-y-4 p-5">
                    @foreach ($pengiriman as $p)
                        <div>
                            <div class="mb-1.5 flex items-center justify-between text-xs">
                                <span class="font-semibold text-slate-600">{{ $p['label'] }}</span>
                                <span class="font-bold tabular-nums text-slate-800">{{ $p['jumlah'] }}</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ $p['bar'] }} {{ $p['width'] }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Template Pesan --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-bold text-slate-900">Template Pesan</h3>
                    <a href="{{ route('admin.digital-reminder.template') }}" class="text-xs font-bold text-sky-600 hover:underline">Kelola</a>
                </div>
                <ul class="divide-y divide-slate-50">
                    @foreach ($templates as $t)
                        <li class="px-5 py-3.5 transition-colors hover:bg-sky-50/30">
                            <p class="text-sm font-semibold text-slate-800">{{ $t['judul'] }}</p>
                            <p class="mt-0.5 truncate text-xs text-slate-400">{{ $t['teks'] }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
