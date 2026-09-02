@extends('layouts.app')

@section('title', 'Monitoring')
@section('page-title', 'Monitoring')

@section('content')
@php
    $stats = [
        ['label' => 'Antrian Aktif',       'value' => '12',  'tone' => 'sky',     'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75Z'],
        ['label' => 'Kunjungan Hari Ini',  'value' => '42',  'tone' => 'emerald', 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['label' => 'Reminder Terkirim',   'value' => '298', 'tone' => 'violet',  'icon' => 'M4.5 12.75l6 6 9-13.5'],
        ['label' => 'Follow Up Terlambat', 'value' => '3',   'tone' => 'rose',    'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
    ];

    $toneColor = [
        'sky'     => ['bg' => 'bg-sky-50',     'text' => 'text-sky-600'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
        'violet'  => ['bg' => 'bg-violet-50',  'text' => 'text-violet-600'],
        'rose'    => ['bg' => 'bg-rose-50',    'text' => 'text-rose-600'],
    ];

    $kunjunganPoli = [
        ['poli' => 'Poli Umum',          'jumlah' => 18, 'width' => 'w-full',   'bar' => 'bg-sky-500'],
        ['poli' => 'Poli Gigi',          'jumlah' => 9,  'width' => 'w-1/2',    'bar' => 'bg-emerald-500'],
        ['poli' => 'Poli Penyakit Dalam','jumlah' => 8,  'width' => 'w-2/5',    'bar' => 'bg-violet-500'],
        ['poli' => 'Poli Anak',          'jumlah' => 7,  'width' => 'w-1/3',    'bar' => 'bg-amber-500'],
    ];

    $antrian = [
        ['no' => 'A-014', 'nama' => 'Tono Sutrisno', 'poli' => 'Poli Umum',           'lama' => '12 mnt'],
        ['no' => 'A-015', 'nama' => 'Maya Putri',    'poli' => 'Poli Gigi',           'lama' => '8 mnt'],
        ['no' => 'A-016', 'nama' => 'Bambang Eko',   'poli' => 'Poli Penyakit Dalam', 'lama' => '5 mnt'],
        ['no' => 'A-017', 'nama' => 'Sari Wulandari','poli' => 'Poli Anak',           'lama' => '3 mnt'],
        ['no' => 'A-018', 'nama' => 'Fajar Hidayat', 'poli' => 'Poli Umum',           'lama' => '1 mnt'],
    ];

    $aktivitas = [
        ['teks' => 'Reminder "Pengingat Kontrol" terkirim ke 42 penerima.', 'waktu' => 'Baru saja', 'tone' => 'emerald'],
        ['teks' => 'Kunjungan baru masuk di Poli Umum (Tono Sutrisno).',   'waktu' => '2 mnt lalu', 'tone' => 'sky'],
        ['teks' => 'Follow up untuk Agus Wijaya terlambat 30 menit.',      'waktu' => '5 mnt lalu', 'tone' => 'rose'],
        ['teks' => 'Gateway WhatsApp kembali normal.',                     'waktu' => '8 mnt lalu', 'tone' => 'emerald'],
        ['teks' => 'Kunjungan Siti Aminah selesai di Poli Gigi.',          'waktu' => '10 mnt lalu', 'tone' => 'sky'],
    ];

    $sistem = [
        ['nama' => 'Gateway WhatsApp', 'status' => 'Aktif',  'tone' => 'emerald'],
        ['nama' => 'Gateway Email',    'status' => 'Aktif',  'tone' => 'emerald'],
        ['nama' => 'Gateway SMS',      'status' => 'Error',  'tone' => 'rose'],
        ['nama' => 'Antrian Redis',    'status' => 'Normal', 'tone' => 'emerald'],
    ];
@endphp
<div class="space-y-6">

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
            <p class="mt-0.5 text-sm text-slate-500">Pantau aktivitas layanan secara real-time: antrian, reminder, dan follow up.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                Muat Ulang
            </button>
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

        {{-- ===== Kolom Utama ===== --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- Grafik Kunjungan per Poli --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-bold text-slate-900">Kunjungan per Poli (Hari Ini)</h3>
                    <select class="rounded-lg border-0 bg-slate-50 py-1.5 pl-3 pr-8 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200 cursor-pointer">
                        <option>Hari Ini</option>
                        <option>7 Hari Terakhir</option>
                        <option>30 Hari Terakhir</option>
                    </select>
                </div>
                <div class="space-y-4 p-5">
                    @foreach ($kunjunganPoli as $k)
                        <div>
                            <div class="mb-1.5 flex items-center justify-between text-sm">
                                <span class="font-semibold text-slate-600">{{ $k['poli'] }}</span>
                                <span class="font-bold tabular-nums text-slate-800">{{ $k['jumlah'] }} kunjungan</span>
                            </div>
                            <div class="h-3 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ $k['bar'] }} {{ $k['width'] }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Antrian Berjalan --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-bold text-slate-900">Antrian Berjalan</h3>
                    <a href="#" class="text-xs font-bold text-sky-600 hover:underline">Lihat Semua</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[520px] text-left">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                                <th class="px-6 py-3 font-semibold">No. Antrian</th>
                                <th class="px-6 py-3 font-semibold">Nama</th>
                                <th class="px-6 py-3 font-semibold">Poli</th>
                                <th class="px-6 py-3 text-right font-semibold">Menunggu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach ($antrian as $a)
                                <tr class="bg-white transition-colors hover:bg-sky-50/40">
                                    <td class="whitespace-nowrap px-6 py-3">
                                        <span class="font-mono text-xs font-bold text-sky-700">{{ $a['no'] }}</span>
                                    </td>
                                    <td class="px-6 py-3">
                                        <span class="text-sm font-semibold text-slate-800">{{ $a['nama'] }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-3">
                                        <span class="text-sm text-slate-600">{{ $a['poli'] }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-3 text-right">
                                        <span class="text-xs font-semibold text-slate-500">{{ $a['lama'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ===== Sidebar ===== --}}
        <div class="space-y-6">

            {{-- Aktivitas Real-time --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-bold text-slate-900">Aktivitas Real-time</h3>
                </div>
                <ul class="divide-y divide-slate-50">
                    @foreach ($aktivitas as $act)
                        @php $dot = ['emerald' => 'bg-emerald-500', 'sky' => 'bg-sky-500', 'rose' => 'bg-rose-500'][$act['tone']]; @endphp
                        <li class="flex items-start gap-3 px-5 py-3.5">
                            <span class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full {{ $dot }}"></span>
                            <div class="min-w-0">
                                <p class="text-sm text-slate-600">{{ $act['teks'] }}</p>
                                <p class="text-xs text-slate-400">{{ $act['waktu'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Status Sistem --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-bold text-slate-900">Status Sistem</h3>
                </div>
                <ul class="divide-y divide-slate-50">
                    @foreach ($sistem as $sys)
                        @php
                            $stTone = $sys['tone'] === 'emerald'
                                ? 'bg-emerald-50 text-emerald-700 ring-emerald-200/70'
                                : 'bg-rose-50 text-rose-700 ring-rose-200/70';
                        @endphp
                        <li class="flex items-center justify-between px-5 py-3.5">
                            <span class="text-sm font-semibold text-slate-700">{{ $sys['nama'] }}</span>
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $stTone }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $sys['tone'] === 'emerald' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                {{ $sys['status'] }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
