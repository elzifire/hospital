@extends('layouts.app')

@section('title', 'Monitoring')
@section('page-title', 'Monitoring')

@section('content')
@php
    $stats = [
        ['label' => 'Digital Reminder', 'value' => '356', 'tone' => 'sky',     'icon' => 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0'],
        ['label' => 'Outreach',         'value' => '356', 'tone' => 'emerald', 'icon' => 'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46'],
        ['label' => 'Respon',           'value' => '112', 'tone' => 'violet',  'icon' => 'M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-2.029 2.115 2.115 0 0 0-1.661-.586 48.744 48.744 0 0 0-8.983 0 2.115 2.115 0 0 0-1.661.586 2.126 2.126 0 0 0-.476 2.029c.172.714.308 1.44.41 2.174m3.923-2.174a41.03 41.03 0 0 0-.41 2.174c-.058.35-.088.706-.088 1.066v4.286c0 .36.03.716.088 1.066'],
        ['label' => 'Follow Up',        'value' => '18',  'tone' => 'amber',   'icon' => 'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z'],
        ['label' => 'Kunjungan',        'value' => '1.284','tone' => 'rose',   'icon' => 'M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z'],
    ];

    $toneColor = [
        'sky'     => ['bg' => 'bg-sky-50',     'text' => 'text-sky-600'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
        'violet'  => ['bg' => 'bg-violet-50',  'text' => 'text-violet-600'],
        'amber'   => ['bg' => 'bg-amber-50',   'text' => 'text-amber-600'],
        'rose'    => ['bg' => 'bg-rose-50',    'text' => 'text-rose-600'],
    ];

    $statusStyle = [
        'Terkirim'         => 'bg-emerald-50 text-emerald-700 ring-emerald-200/70',
        'Menunggu Dikirim' => 'bg-amber-50 text-amber-700 ring-amber-200/70',
        'Terjadwal'        => 'bg-amber-50 text-amber-700 ring-amber-200/70',
        'Gagal'            => 'bg-rose-50 text-rose-700 ring-rose-200/70',
        'Selesai'          => 'bg-emerald-50 text-emerald-700 ring-emerald-200/70',
        'Belum Ditindak'   => 'bg-amber-50 text-amber-700 ring-amber-200/70',
        'Menunggu'         => 'bg-amber-50 text-amber-700 ring-amber-200/70',
        'Terlambat'        => 'bg-rose-50 text-rose-700 ring-rose-200/70',
        'Belum Membalas'   => 'bg-rose-50 text-rose-700 ring-rose-200/70',
    ];

    $reminderRows = [
        ['penerima' => 'Budi Santoso', 'poli' => 'Poli Umum, Poli Jantung', 'home_visit' => '12 Sep 2026', 'jadwal' => 'Hari ini, 07:30', 'status' => 'Terkirim'],
        ['penerima' => 'Siti Aminah',  'poli' => 'Poli Umum',                'home_visit' => null,          'jadwal' => 'Hari ini, 08:00', 'status' => 'Terkirim'],
        ['penerima' => 'Agus Wijaya',  'poli' => 'Poli Penyakit Dalam',      'home_visit' => null,          'jadwal' => 'Hari ini, 09:15', 'status' => 'Terjadwal'],
        ['penerima' => 'Dewi Lestari', 'poli' => 'Poli Anak',                'home_visit' => '18 Sep 2026', 'jadwal' => 'Besok, 07:00',   'status' => 'Terjadwal'],
        ['penerima' => 'Rudi Hartono', 'poli' => 'Poli Umum',                'home_visit' => null,          'jadwal' => 'Hari ini, 10:30', 'status' => 'Gagal'],
    ];
    $reminderBreakdown = [
        ['label' => 'Terkirim',  'jumlah' => 298, 'bar' => 'bg-emerald-500', 'width' => 'w-[84%]'],
        ['label' => 'Terjadwal', 'jumlah' => 41,  'bar' => 'bg-amber-500',   'width' => 'w-[12%]'],
        ['label' => 'Gagal',     'jumlah' => 17,  'bar' => 'bg-rose-500',    'width' => 'w-[5%]'],
    ];

    $outreachRows = [
        ['penerima' => 'Budi Santoso', 'pesan' => 'Jadwal kontrol di Poli Umum pada 12/09/2026.', 'waktu' => 'Hari ini, 07:30', 'status' => 'Terkirim'],
        ['penerima' => 'Siti Aminah',  'pesan' => 'Pengingat kontrol asma pukul 09:00.',            'waktu' => 'Hari ini, 08:00', 'status' => 'Terkirim'],
        ['penerima' => 'Agus Wijaya',  'pesan' => 'Hasil pemeriksaan jantung sudah tersedia.',     'waktu' => 'Hari ini, 09:15', 'status' => 'Menunggu Dikirim'],
        ['penerima' => 'Dewi Lestari', 'pesan' => 'Pengingat kontrol anak di Poli Anak.',          'waktu' => 'Besok, 07:00',   'status' => 'Menunggu Dikirim'],
    ];
    $outreachBreakdown = [
        ['label' => 'Terkirim',         'jumlah' => 298, 'bar' => 'bg-emerald-500', 'width' => 'w-[84%]'],
        ['label' => 'Menunggu Dikirim', 'jumlah' => 58,  'bar' => 'bg-amber-500',   'width' => 'w-[16%]'],
    ];

    $responRows = [
        ['nomor' => '081234567890', 'nama' => 'Budi Santoso', 'terakhir' => 'Ya, saya akan datang.',      'jumlah' => 3, 'waktu' => '07:32', 'status' => 'Belum Ditindak'],
        ['nomor' => '081298765432', 'nama' => 'Siti Aminah',  'terakhir' => 'Tidak bisa, ada keperluan.',  'jumlah' => 2, 'waktu' => '08:05', 'status' => 'Selesai'],
        ['nomor' => '081377445566', 'nama' => 'Agus Wijaya',  'terakhir' => 'Insya Allah hadir, pak.',     'jumlah' => 1, 'waktu' => '09:40', 'status' => 'Belum Ditindak'],
        ['nomor' => '081912223344', 'nama' => 'Lina Marlina', 'terakhir' => 'K',                           'jumlah' => 1, 'waktu' => '11:22', 'status' => 'Belum Ditindak'],
    ];
    $responBreakdown = [
        ['label' => 'Selesai',        'jumlah' => 39, 'bar' => 'bg-emerald-500', 'width' => 'w-[81%]'],
        ['label' => 'Belum Ditindak', 'jumlah' => 9,  'bar' => 'bg-amber-500',   'width' => 'w-[19%]'],
    ];

    $followUpRows = [
        ['nama' => 'Budi Santoso', 'terakhir' => 'Kemarin, 07:30', 'balasan' => 'Belum Membalas', 'jadwal' => 'Hari ini, 14:00', 'status' => 'Menunggu'],
        ['nama' => 'Agus Wijaya',  'terakhir' => '2 hari lalu',    'balasan' => 'Belum Membalas', 'jadwal' => 'Hari ini, 15:30', 'status' => 'Terlambat'],
        ['nama' => 'Siti Aminah',  'terakhir' => 'Kemarin, 09:00', 'balasan' => 'Belum Membalas', 'jadwal' => 'Besok, 09:00',   'status' => 'Menunggu'],
        ['nama' => 'Dewi Lestari', 'terakhir' => '3 hari lalu',    'balasan' => 'Belum Membalas', 'jadwal' => 'Besok, 10:00',   'status' => 'Selesai'],
    ];
    $followUpBreakdown = [
        ['label' => 'Selesai',   'jumlah' => 124, 'bar' => 'bg-emerald-500', 'width' => 'w-[87%]'],
        ['label' => 'Menunggu',  'jumlah' => 8,   'bar' => 'bg-amber-500',   'width' => 'w-[6%]'],
        ['label' => 'Terlambat', 'jumlah' => 3,   'bar' => 'bg-rose-500',    'width' => 'w-[3%]'],
    ];

    $kunjunganRows = [
        ['waktu' => '08:12', 'nama' => 'Budi Santoso', 'poli' => ['Poli Umum', 'Poli Jantung'],            'tanggal' => 'Hari ini', 'status' => 'Selesai'],
        ['waktu' => '09:40', 'nama' => 'Siti Aminah',  'poli' => ['Poli Gigi'],                            'tanggal' => 'Hari ini', 'status' => 'Selesai'],
        ['waktu' => '10:05', 'nama' => 'Agus Wijaya',  'poli' => ['Poli Penyakit Dalam'],                  'tanggal' => 'Hari ini', 'status' => 'Selesai'],
        ['waktu' => '11:20', 'nama' => 'Dewi Lestari', 'poli' => ['Poli Anak', 'Poli Umum'],               'tanggal' => 'Hari ini', 'status' => 'Selesai'],
    ];
    $kunjunganBreakdown = [
        ['label' => 'Poli Umum',           'jumlah' => 18, 'bar' => 'bg-sky-500',     'width' => 'w-full'],
        ['label' => 'Poli Gigi',           'jumlah' => 9,  'bar' => 'bg-emerald-500', 'width' => 'w-1/2'],
        ['label' => 'Poli Penyakit Dalam', 'jumlah' => 8,  'bar' => 'bg-violet-500',  'width' => 'w-2/5'],
        ['label' => 'Poli Anak',           'jumlah' => 7,  'bar' => 'bg-amber-500',   'width' => 'w-1/3'],
    ];
@endphp
<div x-data="{ tab: 'reminder' }" class="space-y-6">

    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-xl font-bold tracking-tight text-slate-900">Monitoring</h2>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-200/70">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    </span>
                    Live
                </span>
            </div>
            <p class="mt-0.5 text-sm text-slate-500">Laporan aktivitas Digital Reminder, Outreach, Respon, Follow Up, dan Kunjungan.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <select class="rounded-xl border-0 bg-white py-2.5 pl-3.5 pr-8 text-sm font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                <option>Hari Ini</option>
                <option>7 Hari Terakhir</option>
                <option>30 Hari Terakhir</option>
            </select>
            <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                Unduh Laporan
            </button>
        </div>
    </div>

    {{-- ===== Kartu Statistik per Fitur ===== --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ($stats as $s)
            @php $c = $toneColor[$s['tone']]; @endphp
            <a href="#" @click.prevent="tab = '{{ ['reminder', 'outreach', 'respon', 'followup', 'kunjungan'][$loop->index] }}'"
               class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-xs ring-1 transition"
               :class="tab === '{{ ['reminder', 'outreach', 'respon', 'followup', 'kunjungan'][$loop->index] }}' ? 'ring-sky-400' : 'ring-slate-200 hover:ring-slate-300'">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg {{ $c['bg'] }} {{ $c['text'] }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $s['icon'] }}" /></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-lg font-extrabold leading-tight tabular-nums text-slate-900">{{ $s['value'] }}</p>
                    <p class="truncate text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ $s['label'] }}</p>
                </div>
            </a>
        @endforeach
    </div>

    {{-- ===== Tabs Laporan ===== --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">

        {{-- Tab bar --}}
        <div class="flex gap-1 overflow-x-auto border-b border-slate-100 bg-slate-50/60 px-4 pt-4">
            <button @click="tab = 'reminder'" class="inline-flex items-center gap-2 whitespace-nowrap rounded-t-lg border-b-2 px-4 py-2.5 text-sm font-semibold transition" :class="tab === 'reminder' ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500 hover:text-slate-700'">
                Digital Reminder
            </button>
            <button @click="tab = 'outreach'" class="inline-flex items-center gap-2 whitespace-nowrap rounded-t-lg border-b-2 px-4 py-2.5 text-sm font-semibold transition" :class="tab === 'outreach' ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500 hover:text-slate-700'">
                Outreach
            </button>
            <button @click="tab = 'respon'" class="inline-flex items-center gap-2 whitespace-nowrap rounded-t-lg border-b-2 px-4 py-2.5 text-sm font-semibold transition" :class="tab === 'respon' ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500 hover:text-slate-700'">
                Respon
            </button>
            <button @click="tab = 'followup'" class="inline-flex items-center gap-2 whitespace-nowrap rounded-t-lg border-b-2 px-4 py-2.5 text-sm font-semibold transition" :class="tab === 'followup' ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500 hover:text-slate-700'">
                Follow Up
            </button>
            <button @click="tab = 'kunjungan'" class="inline-flex items-center gap-2 whitespace-nowrap rounded-t-lg border-b-2 px-4 py-2.5 text-sm font-semibold transition" :class="tab === 'kunjungan' ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500 hover:text-slate-700'">
                Kunjungan
            </button>
        </div>

        {{-- ===== Tab: Digital Reminder ===== --}}
        <div x-show="tab === 'reminder'" x-cloak class="grid grid-cols-1 gap-6 p-5 lg:grid-cols-3">
            <div class="overflow-hidden rounded-2xl ring-1 ring-slate-200 lg:col-span-2">
                <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-3.5">
                    <h3 class="text-sm font-bold text-slate-900">Laporan Digital Reminder</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[680px] text-left">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                                <th class="px-5 py-3 font-semibold">Penerima</th>
                                <th class="px-5 py-3 font-semibold">Poli</th>
                                <th class="px-5 py-3 font-semibold">Home Visit</th>
                                <th class="px-5 py-3 font-semibold">Jadwal</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach ($reminderRows as $r)
                                <tr class="bg-white transition-colors hover:bg-sky-50/40">
                                    <td class="whitespace-nowrap px-5 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-sky-100 text-xs font-bold uppercase text-sky-700">{{ substr($r['penerima'], 0, 1) }}</div>
                                            <span class="text-sm font-bold text-slate-900">{{ $r['penerima'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-sm text-slate-600">{{ $r['poli'] }}</td>
                                    <td class="whitespace-nowrap px-5 py-3">
                                        @if ($r['home_visit'])
                                            <span class="text-xs font-bold text-emerald-600">{{ $r['home_visit'] }}</span>
                                        @else
                                            <span class="text-xs text-slate-300">—</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-xs font-semibold text-slate-500">{{ $r['jadwal'] }}</td>
                                    <td class="whitespace-nowrap px-5 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $statusStyle[$r['status']] }}">{{ $r['status'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="overflow-hidden rounded-2xl ring-1 ring-slate-200">
                <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-3.5">
                    <h3 class="text-sm font-bold text-slate-900">Status Pengiriman</h3>
                </div>
                <div class="space-y-4 p-5">
                    @foreach ($reminderBreakdown as $b)
                        <div>
                            <div class="mb-1.5 flex items-center justify-between text-xs">
                                <span class="font-semibold text-slate-600">{{ $b['label'] }}</span>
                                <span class="font-bold tabular-nums text-slate-800">{{ $b['jumlah'] }}</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ $b['bar'] }} {{ $b['width'] }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ===== Tab: Outreach ===== --}}
        <div x-show="tab === 'outreach'" x-cloak class="grid grid-cols-1 gap-6 p-5 lg:grid-cols-3">
            <div class="overflow-hidden rounded-2xl ring-1 ring-slate-200 lg:col-span-2">
                <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-3.5">
                    <h3 class="text-sm font-bold text-slate-900">Laporan Outreach</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[620px] text-left">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                                <th class="px-5 py-3 font-semibold">Penerima</th>
                                <th class="px-5 py-3 font-semibold">Pesan</th>
                                <th class="px-5 py-3 font-semibold">Waktu Kirim</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach ($outreachRows as $r)
                                <tr class="bg-white transition-colors hover:bg-sky-50/40">
                                    <td class="whitespace-nowrap px-5 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold uppercase text-emerald-700">{{ substr($r['penerima'], 0, 1) }}</div>
                                            <span class="text-sm font-bold text-slate-900">{{ $r['penerima'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3"><p class="max-w-xs truncate text-sm text-slate-600">{{ $r['pesan'] }}</p></td>
                                    <td class="whitespace-nowrap px-5 py-3 text-xs font-semibold text-slate-500">{{ $r['waktu'] }}</td>
                                    <td class="whitespace-nowrap px-5 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $statusStyle[$r['status']] }}">{{ $r['status'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="overflow-hidden rounded-2xl ring-1 ring-slate-200">
                <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-3.5">
                    <h3 class="text-sm font-bold text-slate-900">Status Pengiriman</h3>
                </div>
                <div class="space-y-4 p-5">
                    @foreach ($outreachBreakdown as $b)
                        <div>
                            <div class="mb-1.5 flex items-center justify-between text-xs">
                                <span class="font-semibold text-slate-600">{{ $b['label'] }}</span>
                                <span class="font-bold tabular-nums text-slate-800">{{ $b['jumlah'] }}</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ $b['bar'] }} {{ $b['width'] }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ===== Tab: Respon ===== --}}
        <div x-show="tab === 'respon'" x-cloak class="grid grid-cols-1 gap-6 p-5 lg:grid-cols-3">
            <div class="overflow-hidden rounded-2xl ring-1 ring-slate-200 lg:col-span-2">
                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/60 px-5 py-3.5">
                    <h3 class="text-sm font-bold text-slate-900">Laporan Respon</h3>
                    <span class="text-[11px] font-semibold text-slate-400">via webhook WhatsApp</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[620px] text-left">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                                <th class="px-5 py-3 font-semibold">Nomor Telepon</th>
                                <th class="px-5 py-3 font-semibold">Balasan Terakhir</th>
                                <th class="px-5 py-3 font-semibold">Jumlah</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach ($responRows as $r)
                                <tr class="bg-white transition-colors hover:bg-sky-50/40">
                                    <td class="whitespace-nowrap px-5 py-3">
                                        <p class="font-mono text-sm font-bold text-slate-900">{{ $r['nomor'] }}</p>
                                        <p class="text-xs text-slate-400">{{ $r['nama'] }}</p>
                                    </td>
                                    <td class="px-5 py-3"><p class="max-w-xs truncate text-sm text-slate-600">“{{ $r['terakhir'] }}”</p></td>
                                    <td class="whitespace-nowrap px-5 py-3">
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold tabular-nums text-slate-600">{{ $r['jumlah'] }} balasan</span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $statusStyle[$r['status']] }}">{{ $r['status'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="overflow-hidden rounded-2xl ring-1 ring-slate-200">
                <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-3.5">
                    <h3 class="text-sm font-bold text-slate-900">Penanganan Balasan</h3>
                </div>
                <div class="space-y-4 p-5">
                    @foreach ($responBreakdown as $b)
                        <div>
                            <div class="mb-1.5 flex items-center justify-between text-xs">
                                <span class="font-semibold text-slate-600">{{ $b['label'] }}</span>
                                <span class="font-bold tabular-nums text-slate-800">{{ $b['jumlah'] }}</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ $b['bar'] }} {{ $b['width'] }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ===== Tab: Follow Up ===== --}}
        <div x-show="tab === 'followup'" x-cloak class="grid grid-cols-1 gap-6 p-5 lg:grid-cols-3">
            <div class="overflow-hidden rounded-2xl ring-1 ring-slate-200 lg:col-span-2">
                <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-3.5">
                    <h3 class="text-sm font-bold text-slate-900">Laporan Follow Up</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[680px] text-left">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                                <th class="px-5 py-3 font-semibold">Pasien</th>
                                <th class="px-5 py-3 font-semibold">Terakhir Dikirim</th>
                                <th class="px-5 py-3 font-semibold">Balasan</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach ($followUpRows as $r)
                                <tr class="bg-white transition-colors hover:bg-sky-50/40">
                                    <td class="whitespace-nowrap px-5 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 text-xs font-bold uppercase text-amber-700">{{ substr($r['nama'], 0, 1) }}</div>
                                            <span class="text-sm font-bold text-slate-900">{{ $r['nama'] }}</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-xs font-semibold text-slate-500">{{ $r['terakhir'] }}</td>
                                    <td class="whitespace-nowrap px-5 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $statusStyle[$r['balasan']] }}">{{ $r['balasan'] }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $statusStyle[$r['status']] }}">{{ $r['status'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="overflow-hidden rounded-2xl ring-1 ring-slate-200">
                <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-3.5">
                    <h3 class="text-sm font-bold text-slate-900">Status Follow Up</h3>
                </div>
                <div class="space-y-4 p-5">
                    @foreach ($followUpBreakdown as $b)
                        <div>
                            <div class="mb-1.5 flex items-center justify-between text-xs">
                                <span class="font-semibold text-slate-600">{{ $b['label'] }}</span>
                                <span class="font-bold tabular-nums text-slate-800">{{ $b['jumlah'] }}</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ $b['bar'] }} {{ $b['width'] }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ===== Tab: Kunjungan ===== --}}
        <div x-show="tab === 'kunjungan'" x-cloak class="grid grid-cols-1 gap-6 p-5 lg:grid-cols-3">
            <div class="overflow-hidden rounded-2xl ring-1 ring-slate-200 lg:col-span-2">
                <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-3.5">
                    <h3 class="text-sm font-bold text-slate-900">Laporan Kunjungan</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[620px] text-left">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                                <th class="px-5 py-3 font-semibold">Waktu</th>
                                <th class="px-5 py-3 font-semibold">Pasien</th>
                                <th class="px-5 py-3 font-semibold">Poli Dikunjungi</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach ($kunjunganRows as $r)
                                <tr class="bg-white transition-colors hover:bg-sky-50/40">
                                    <td class="whitespace-nowrap px-5 py-3 text-xs font-semibold text-slate-500">{{ $r['waktu'] }}</td>
                                    <td class="whitespace-nowrap px-5 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-rose-100 text-xs font-bold uppercase text-rose-700">{{ substr($r['nama'], 0, 1) }}</div>
                                            <span class="text-sm font-bold text-slate-900">{{ $r['nama'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($r['poli'] as $poli)
                                                <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-semibold text-sky-700 ring-1 ring-inset ring-sky-200/70">{{ $poli }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $statusStyle[$r['status']] }}">{{ $r['status'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="overflow-hidden rounded-2xl ring-1 ring-slate-200">
                <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-3.5">
                    <h3 class="text-sm font-bold text-slate-900">Kunjungan per Poli (Hari Ini)</h3>
                </div>
                <div class="space-y-4 p-5">
                    @foreach ($kunjunganBreakdown as $b)
                        <div>
                            <div class="mb-1.5 flex items-center justify-between text-xs">
                                <span class="font-semibold text-slate-600">{{ $b['label'] }}</span>
                                <span class="font-bold tabular-nums text-slate-800">{{ $b['jumlah'] }}</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ $b['bar'] }} {{ $b['width'] }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Status Sistem (channel WhatsApp saja) ===== --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="flex items-center justify-between rounded-xl bg-white px-5 py-4 shadow-xs ring-1 ring-slate-200">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" /></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-800">Gateway WhatsApp</p>
                    <p class="text-xs text-slate-400">Channel broadcast utama</p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-200/70">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif
            </span>
        </div>

        <div class="flex items-center justify-between rounded-xl bg-white px-5 py-4 shadow-xs ring-1 ring-slate-200">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-800">Webhook WhatsApp</p>
                    <p class="text-xs text-slate-400">Penerima balasan pasien</p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-200/70">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                </span>
                Aktif
            </span>
        </div>

        <div class="flex items-center justify-between rounded-xl bg-white px-5 py-4 shadow-xs ring-1 ring-slate-200">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-50 text-sky-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z" /></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-800">Antrian Redis</p>
                    <p class="text-xs text-slate-400">Cache & queue broadcast</p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-200/70">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Normal
            </span>
        </div>
    </div>
</div>
@endsection
