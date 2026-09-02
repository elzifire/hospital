@extends('layouts.app')

@section('title', 'Outreach')
@section('page-title', 'Outreach')

@section('content')
@php
    $stats = [
        ['label' => 'Pesan Masuk',      'value' => '112', 'tone' => 'sky',     'icon' => 'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46'],
        ['label' => 'Konfirmasi Hadir', 'value' => '68',  'tone' => 'emerald', 'icon' => 'M4.5 12.75l6 6 9-13.5'],
        ['label' => 'Tidak Hadir',      'value' => '21',  'tone' => 'rose',    'icon' => 'M6 18 18 6M6 6l12 12'],
        ['label' => 'Perlu Ditinjau',   'value' => '23',  'tone' => 'amber',   'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
    ];

    $toneColor = [
        'sky'     => ['bg' => 'bg-sky-50',     'text' => 'text-sky-600'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
        'rose'    => ['bg' => 'bg-rose-50',    'text' => 'text-rose-600'],
        'amber'   => ['bg' => 'bg-amber-50',   'text' => 'text-amber-600'],
    ];

    $tabs = [
        ['label' => 'Semua',        'count' => 112, 'active' => true],
        ['label' => 'Hadir',        'count' => 68,  'active' => false],
        ['label' => 'Tidak Hadir',  'count' => 21,  'active' => false],
        ['label' => 'Perlu Ditinjau', 'count' => 23, 'active' => false],
    ];

    $rows = [
        ['pengirim' => 'Budi Santoso', 'no_hp' => '081234567890', 'balasan' => 'Ya, saya akan datang.', 'pertanyaan' => 'Konfirmasi kehadiran kontrol', 'waktu' => 'Hari ini, 07:32', 'status' => 'Hadir'],
        ['pengirim' => 'Siti Aminah',  'no_hp' => '081298765432', 'balasan' => 'Tidak bisa, ada keperluan.', 'pertanyaan' => 'Konfirmasi kehadiran kontrol', 'waktu' => 'Hari ini, 08:05', 'status' => 'Tidak Hadir'],
        ['pengirim' => 'Agus Wijaya',  'no_hp' => '081377445566', 'balasan' => 'Insya Allah hadir.',      'pertanyaan' => 'Konfirmasi kehadiran kontrol', 'waktu' => 'Hari ini, 09:40', 'status' => 'Perlu Ditinjau'],
        ['pengirim' => 'Dewi Lestari', 'no_hp' => '082112345678', 'balasan' => 'Ya, datang.',              'pertanyaan' => 'Konfirmasi kehadiran kontrol', 'waktu' => 'Hari ini, 09:55', 'status' => 'Hadir'],
        ['pengirim' => 'Rudi Hartono', 'no_hp' => '085655667788', 'balasan' => 'Maaf tidak hadir.',        'pertanyaan' => 'Konfirmasi kehadiran kontrol', 'waktu' => 'Hari ini, 10:10', 'status' => 'Tidak Hadir'],
        ['pengirim' => 'Lina Marlina', 'no_hp' => '081912223344', 'balasan' => 'K',                        'pertanyaan' => 'Konfirmasi kehadiran kontrol', 'waktu' => 'Hari ini, 11:22', 'status' => 'Perlu Ditinjau'],
    ];

    $statusStyle = [
        'Hadir'          => 'bg-emerald-50 text-emerald-700 ring-emerald-200/70',
        'Tidak Hadir'    => 'bg-rose-50 text-rose-700 ring-rose-200/70',
        'Perlu Ditinjau' => 'bg-amber-50 text-amber-700 ring-amber-200/70',
    ];
@endphp
<div class="space-y-6">

    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Outreach</h2>
            <p class="mt-0.5 text-sm text-slate-500">Pantau balasan pesan WhatsApp dari PNPP terhadap broadcast yang dikirim.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.outreach.import') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                Import
            </a>
            <a href="{{ route('admin.outreach.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Tambah Pesan
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

    {{-- ===== Tabel Balasan ===== --}}
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
                <input type="text" placeholder="Cari pengirim, balasan, atau pertanyaan..."
                       class="block w-full rounded-xl border-0 bg-white py-2.5 pl-10 pr-9 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition">
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <select class="rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                    <option>Semua Status</option>
                    <option>Hadir</option>
                    <option>Tidak Hadir</option>
                    <option>Perlu Ditinjau</option>
                </select>
                <input type="date" class="rounded-lg border-0 bg-white py-2 px-3 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500">
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] text-left">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                        <th class="px-6 py-3.5 font-semibold">Pengirim</th>
                        <th class="px-6 py-3.5 font-semibold">Balasan</th>
                        <th class="px-6 py-3.5 font-semibold">Pertanyaan</th>
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
                                        {{ substr($r['pengirim'], 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-900">{{ $r['pengirim'] }}</p>
                                        <p class="truncate font-mono text-xs text-slate-400">{{ $r['no_hp'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="max-w-sm text-sm text-slate-600">“{{ $r['balasan'] }}”</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-medium text-slate-500">{{ $r['pertanyaan'] }}</span>
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
                                    <button title="Buat Follow Up" class="rounded-lg p-2 text-slate-400 transition-all hover:bg-emerald-50 hover:text-emerald-600 focus:opacity-100">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" /></svg>
                                    </button>
                                    <a href="{{ route('admin.outreach.edit', 1) }}" title="Edit" class="rounded-lg p-2 text-slate-400 transition-all hover:bg-sky-50 hover:text-sky-600 focus:opacity-100">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
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
                dari <span class="font-bold text-slate-800">112</span> pesan
            </p>
            <div class="flex items-center gap-1">
                <button class="rounded-lg bg-white p-2 text-slate-500 shadow-xs ring-1 ring-slate-200 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40" disabled>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                </button>
                <button class="h-8 min-w-8 rounded-lg bg-sky-600 px-2 text-xs font-bold tabular-nums text-white shadow-sm">1</button>
                <button class="h-8 min-w-8 rounded-lg bg-white px-2 text-xs font-bold tabular-nums text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">2</button>
                <button class="h-8 min-w-8 rounded-lg bg-white px-2 text-xs font-bold tabular-nums text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">3</button>
                <span class="px-1 text-xs font-bold text-slate-400">…</span>
                <button class="h-8 min-w-8 rounded-lg bg-white px-2 text-xs font-bold tabular-nums text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">19</button>
                <button class="rounded-lg bg-white p-2 text-slate-500 shadow-xs ring-1 ring-slate-200 transition hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
