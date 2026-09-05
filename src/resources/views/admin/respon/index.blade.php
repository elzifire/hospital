@extends('layouts.app')

@section('title', 'Respon')
@section('page-title', 'Respon')

@section('content')
@php
    $stats = [
        ['label' => 'Total Balasan',         'value' => '112', 'tone' => 'sky',     'icon' => 'M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-2.029 2.115 2.115 0 0 0-1.661-.586 48.744 48.744 0 0 0-8.983 0 2.115 2.115 0 0 0-1.661.586 2.126 2.126 0 0 0-.476 2.029c.172.714.308 1.44.41 2.174m3.923-2.174a41.03 41.03 0 0 0-.41 2.174c-.058.35-.088.706-.088 1.066v4.286c0 .36.03.716.088 1.066'],
        ['label' => 'Balasan Hari Ini',      'value' => '23',  'tone' => 'emerald', 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['label' => 'Belum Ditindaklanjuti', 'value' => '9',   'tone' => 'amber',   'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
        ['label' => 'Nomor Aktif',           'value' => '48',  'tone' => 'violet',  'icon' => 'M10.5 18.75a.75.75 0 0 0 0 1.5h3a.75.75 0 0 0 0-1.5h-3ZM7.5 15.75a.75.75 0 0 0 0 1.5h3a.75.75 0 0 0 0-1.5h-3Zm0-8.25a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 0 1.5h-3A.75.75 0 0 1 7.5 7.5Zm6.75-4.5a.75.75 0 0 0-1.5 0v9.113l-2.47-2.47a.75.75 0 0 0-1.06 1.06l3.75 3.75a.75.75 0 0 0 1.06 0l3.75-3.75a.75.75 0 1 0-1.06-1.06l-2.47 2.47V3Z'],
    ];

    $toneColor = [
        'sky'     => ['bg' => 'bg-sky-50',     'text' => 'text-sky-600'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
        'amber'   => ['bg' => 'bg-amber-50',   'text' => 'text-amber-600'],
        'violet'  => ['bg' => 'bg-violet-50',  'text' => 'text-violet-600'],
    ];

    $tabs = [
        ['label' => 'Semua Nomor',    'count' => 48, 'active' => true],
        ['label' => 'Belum Ditindak', 'count' => 9,  'active' => false],
        ['label' => 'Selesai',        'count' => 39, 'active' => false],
    ];

    $rows = [
        ['nomor' => '081234567890', 'nama' => 'Budi Santoso', 'terakhir' => 'Ya, saya akan datang.',            'jumlah' => 3, 'waktu' => 'Hari ini, 07:32', 'status' => 'Belum Ditindak'],
        ['nomor' => '081298765432', 'nama' => 'Siti Aminah',  'terakhir' => 'Tidak bisa, ada keperluan.',        'jumlah' => 2, 'waktu' => 'Hari ini, 08:05', 'status' => 'Selesai'],
        ['nomor' => '081377445566', 'nama' => 'Agus Wijaya',  'terakhir' => 'Insya Allah hadir, pak.',           'jumlah' => 1, 'waktu' => 'Hari ini, 09:40', 'status' => 'Belum Ditindak'],
        ['nomor' => '082112345678', 'nama' => 'Dewi Lestari', 'terakhir' => 'Baik, terima kasih infonya.',       'jumlah' => 4, 'waktu' => 'Hari ini, 09:55', 'status' => 'Selesai'],
        ['nomor' => '085655667788', 'nama' => 'Rudi Hartono', 'terakhir' => 'Maaf, saya tidak bisa hadir.',      'jumlah' => 2, 'waktu' => 'Hari ini, 10:10', 'status' => 'Selesai'],
        ['nomor' => '081912223344', 'nama' => 'Lina Marlina', 'terakhir' => 'K',                                'jumlah' => 1, 'waktu' => 'Hari ini, 11:22', 'status' => 'Belum Ditindak'],
    ];

    $statusStyle = [
        'Belum Ditindak' => 'bg-amber-50 text-amber-700 ring-amber-200/70',
        'Selesai'        => 'bg-emerald-50 text-emerald-700 ring-emerald-200/70',
    ];

    $terbaru = [
        ['nama' => 'Budi Santoso', 'nomor' => '081234567890', 'pesan' => 'Ya, saya akan datang.',       'waktu' => '07:32'],
        ['nama' => 'Siti Aminah',  'nomor' => '081298765432', 'pesan' => 'Tidak bisa, ada keperluan.', 'waktu' => '08:05'],
        ['nama' => 'Agus Wijaya',  'nomor' => '081377445566', 'pesan' => 'Insya Allah hadir, pak.',    'waktu' => '09:40'],
    ];
@endphp
<div class="space-y-6">

    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Respon</h2>
            <p class="mt-0.5 text-sm text-slate-500">Balasan pesan WhatsApp dari pasien, dikelompokkan per nomor telepon (masuk via webhook).</p>
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

        {{-- ===== Tabel Balasan per Nomor ===== --}}
        <div class="lg:col-span-2">
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">

                {{-- Tabs --}}
                <div class="flex gap-1 overflow-x-auto border-b border-slate-100 bg-slate-50/60 px-4 pt-4">
                    @foreach ($tabs as $tab)
                        <button class="inline-flex items-center gap-2 whitespace-nowrap rounded-t-lg border-b-2 px-4 py-2.5 text-sm font-semibold transition {{ $tab['active'] ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
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
                        <input type="text" placeholder="Cari nomor telepon atau nama..."
                               class="block w-full rounded-xl border-0 bg-white py-2.5 pl-10 pr-9 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition">
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <input type="date" class="rounded-lg border-0 bg-white py-2 px-3 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500">
                    </div>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                                <th class="px-6 py-3.5 font-semibold">Nomor Telepon</th>
                                <th class="px-6 py-3.5 font-semibold">Balasan Terakhir</th>
                                <th class="px-6 py-3.5 font-semibold">Jumlah</th>
                                <th class="px-6 py-3.5 font-semibold">Waktu</th>
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
                                                {{ substr($r['nama'], 0, 1) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate font-mono text-sm font-bold text-slate-900">{{ $r['nomor'] }}</p>
                                                <p class="truncate text-xs text-slate-400">{{ $r['nama'] }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="max-w-sm truncate text-sm text-slate-600">“{{ $r['terakhir'] }}”</p>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold tabular-nums text-slate-600">{{ $r['jumlah'] }} balasan</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="text-xs font-semibold text-slate-500">{{ $r['waktu'] }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $statusStyle[$r['status']] }}">
                                            {{ $r['status'] }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5 opacity-60 transition-opacity group-hover:opacity-100 lg:opacity-0">
                                            <a href="{{ route('admin.respon.show', $r['nomor']) }}" title="Lihat Percakapan" class="rounded-lg p-2 text-slate-400 transition-all hover:bg-sky-50 hover:text-sky-600 focus:opacity-100">
                                                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                            </a>
                                            <a href="{{ route('admin.follow-up.create') }}" title="Buat Follow Up" class="rounded-lg p-2 text-slate-400 transition-all hover:bg-emerald-50 hover:text-emerald-600 focus:opacity-100">
                                                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" /></svg>
                                            </a>
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
                        dari <span class="font-bold text-slate-800">48</span> nomor telepon
                    </p>
                    <div class="flex items-center gap-1">
                        <button class="rounded-lg bg-white p-2 text-slate-500 shadow-xs ring-1 ring-slate-200 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40" disabled>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                        </button>
                        <button class="h-8 min-w-8 rounded-lg bg-sky-600 px-2 text-xs font-bold tabular-nums text-white shadow-sm">1</button>
                        <button class="h-8 min-w-8 rounded-lg bg-white px-2 text-xs font-bold tabular-nums text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">2</button>
                        <button class="h-8 min-w-8 rounded-lg bg-white px-2 text-xs font-bold tabular-nums text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">3</button>
                        <span class="px-1 text-xs font-bold text-slate-400">…</span>
                        <button class="h-8 min-w-8 rounded-lg bg-white px-2 text-xs font-bold tabular-nums text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">8</button>
                        <button class="rounded-lg bg-white p-2 text-slate-500 shadow-xs ring-1 ring-slate-200 transition hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Sidebar ===== --}}
        <div class="space-y-6">
            {{-- Status Webhook --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-bold text-slate-900">Webhook WhatsApp</h3>
                </div>
                <div class="space-y-3 p-5">
                    <div class="flex items-center justify-between rounded-xl bg-emerald-50/70 p-3 ring-1 ring-emerald-100">
                        <span class="text-sm font-semibold text-emerald-700">Status</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-200/70">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            </span>
                            Aktif
                        </span>
                    </div>
                    <div>
                        <p class="mb-1 text-xs font-bold uppercase tracking-wide text-slate-500">Endpoint</p>
                        <code class="block truncate rounded-lg bg-slate-50 px-3 py-2 font-mono text-xs text-slate-600 ring-1 ring-slate-200">POST /api/webhook/whatsapp</code>
                    </div>
                    <p class="text-xs text-slate-400">Balasan pasien otomatis masuk ke sistem melalui webhook — tanpa input manual.</p>
                </div>
            </div>

            {{-- Balasan Terbaru --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-bold text-slate-900">Balasan Terbaru</h3>
                </div>
                <ul class="divide-y divide-slate-50">
                    @foreach ($terbaru as $t)
                        <li>
                            <a href="{{ route('admin.respon.show', $t['nomor']) }}" class="flex items-center gap-3 px-5 py-3.5 transition-colors hover:bg-sky-50/30">
                                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold uppercase text-emerald-700">
                                    {{ substr($t['nama'], 0, 1) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-bold text-slate-900">{{ $t['nama'] }}</p>
                                    <p class="truncate text-xs text-slate-400">“{{ $t['pesan'] }}”</p>
                                </div>
                                <span class="flex-shrink-0 text-[11px] font-semibold text-slate-400">{{ $t['waktu'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
