@extends('layouts.app')

@section('title', 'Digital Reminder')
@section('page-title', 'Digital Reminder')

@section('content')
@php
    $stats = [
        ['label' => 'Total Sesi',      'value' => number_format($total),                                                                'tone' => 'sky',     'icon' => 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0'],
        ['label' => 'Terjadwal',       'value' => number_format((int) ($perStatus['terjadwal'] ?? 0)),                                    'tone' => 'violet',  'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5'],
        ['label' => 'Mendatang',       'value' => number_format($mendatang),                                                             'tone' => 'emerald', 'icon' => 'M4.5 12.75l6 6 9-13.5'],
        ['label' => 'Selesai',         'value' => number_format((int) ($perStatus['selesai'] ?? 0)),                                     'tone' => 'teal',    'icon' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z'],
        ['label' => 'Tidak Datang',    'value' => number_format((int) ($perStatus['tidak_datang'] ?? 0)),                                'tone' => 'rose',    'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
        ['label' => 'Realisasi Reminder', 'value' => number_format($realisasi),                                                        'tone' => 'amber',   'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
    ];

    $toneColor = [
        'sky'     => ['bg' => 'bg-sky-50',     'text' => 'text-sky-600'],
        'violet'  => ['bg' => 'bg-violet-50',  'text' => 'text-violet-600'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
        'teal'    => ['bg' => 'bg-teal-50',    'text' => 'text-teal-600'],
        'rose'    => ['bg' => 'bg-rose-50',    'text' => 'text-rose-600'],
        'amber'   => ['bg' => 'bg-amber-50',   'text' => 'text-amber-600'],
    ];

    $statusStyle = [
        'terjadwal'    => 'bg-sky-50 text-sky-700 ring-sky-200/70',
        'selesai'      => 'bg-emerald-50 text-emerald-700 ring-emerald-200/70',
        'tidak_datang' => 'bg-rose-50 text-rose-700 ring-rose-200/70',
        'dibatalkan'   => 'bg-slate-100 text-slate-500 ring-slate-200/70',
        'tercatat'     => 'bg-teal-50 text-teal-700 ring-teal-200/70',
    ];

    $statusLabel = ['terjadwal' => 'Terjadwal', 'selesai' => 'Selesai', 'tidak_datang' => 'Tidak Datang', 'dibatalkan' => 'Dibatalkan', 'tercatat' => 'Tercatat'];
@endphp

<div class="space-y-6">
    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Digital Reminder &amp; Kunjungan</h2>
            <p class="mt-0.5 text-sm text-slate-500">Penjadwalan kunjungan pasien beserta realisasinya — jadwal dibuat di sini, kunjungan dicatat lewat modul Kunjungan.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @can('manage kunjungan')
                <a href="{{ route('admin.kunjungan.create') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Tambah Kunjungan
                </a>
            @endcan
            @can('manage digital-reminder')
                <a href="{{ route('admin.digital-reminder.create') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Buat Jadwal
                </a>
            @endcan
        </div>
    </div>

    {{-- ===== Kartu Statistik ===== --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ($stats as $s)
            @php($c = $toneColor[$s['tone']])
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

    {{-- ===== Daftar jadwal + kunjungan (gabungan) ===== --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-bold text-slate-900">Daftar Sesi</h3>
            <p class="mt-0.5 text-xs text-slate-500">
                Satu baris = satu penjadwalan atau satu kunjungan manual (pasien + tanggal). Sumber <strong>Realisasi Reminder</strong> = kunjungan yang dicatat dari jadwal; <strong>Manual</strong> = catatan tanpa jadwal.
            </p>
        </div>

        <form method="GET" action="{{ request()->url() }}"
              class="flex flex-wrap items-end gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4">
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Nama / NIP / catatan…"
                       class="w-56 rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Status</label>
                <select name="status" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    <option value="">Semua</option>
                    @foreach ($statusLabel as $key => $label)
                        <option value="{{ $key }}" {{ $filters['status'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if ($batasiPoli ?? false)
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Poli</label>
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-violet-50 px-3 py-2 text-sm font-semibold text-violet-700 ring-1 ring-violet-200/70">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        {{ $polis->first()?->nama }}
                    </span>
                </div>
            @else
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Poli</label>
                    <select name="poli" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        <option value="">Semua</option>
                        @foreach ($polis as $po)
                            <option value="{{ $po->id }}" {{ $filters['poli'] == $po->id ? 'selected' : '' }}>{{ $po->nama }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Periode</label>
                <select name="periode" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    <option value="">Semua</option>
                    <option value="hari-ini" {{ $filters['periode'] === 'hari-ini' ? 'selected' : '' }}>Hari Ini</option>
                    <option value="7-hari" {{ $filters['periode'] === '7-hari' ? 'selected' : '' }}>7 Hari</option>
                    <option value="30-hari" {{ $filters['periode'] === '30-hari' ? 'selected' : '' }}>30 Hari</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Dari</label>
                <input type="date" name="dari" value="{{ $filters['dari'] }}"
                       class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Sampai</label>
                <input type="date" name="sampai" value="{{ $filters['sampai'] }}"
                       class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">Filter</button>
                <a href="{{ request()->url() }}" class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-300">Reset</a>
            </div>
        </form>

        @if ($items->isEmpty())
            <div class="px-5 py-12 text-center">
                <p class="text-sm font-medium text-slate-500">Belum ada penjadwalan atau kunjungan tercatat.</p>
                <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                    @can('manage digital-reminder')
                        <a href="{{ route('admin.digital-reminder.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">
                            Buat jadwal pertama
                        </a>
                    @endcan
                    @can('manage kunjungan')
                        <a href="{{ route('admin.kunjungan.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                            Catat kunjungan pertama
                        </a>
                    @endcan
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-5 py-3">Jadwal</th>
                            <th class="px-5 py-3">Pasien</th>
                            <th class="px-5 py-3">Poli / Dokter</th>
                            <th class="px-5 py-3">Jenis</th>
                            <th class="px-5 py-3">Sumber</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($items as $baris)
                            @if ($baris['tipe'] === 'manual')
                                {{-- ==== Baris kunjungan manual (tanpa jadwal) ==== --}}
                                @php($pasien = $baris['pasien'])
                                <tr class="hover:bg-slate-50/60">
                                    <td class="whitespace-nowrap px-5 py-3">
                                        <p class="font-semibold text-slate-800">{{ $baris['tanggal']?->translatedFormat('d M Y') }}</p>
                                        <p class="text-xs text-slate-400">tanpa jadwal · {{ count($baris['poliBadges']) }} poli dikunjungi</p>
                                    </td>
                                    <td class="px-5 py-3">
                                        <p class="font-semibold text-slate-800">{{ $pasien?->nama ?? '—' }}</p>
                                        <p class="text-xs text-slate-400">NIP {{ $pasien?->nip ?? '—' }} · {{ $pasien?->satker?->nama ?? '—' }}</p>
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex flex-wrap gap-1.5">
                                            @forelse ($baris['poliBadges'] as $nama)
                                                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 ring-inset bg-sky-50 text-sky-700 ring-sky-200/70">{{ $nama }}</span>
                                            @empty
                                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-500 ring-1 ring-inset ring-slate-200/70">Tanpa poli</span>
                                            @endforelse
                                        </div>
                                        @if ($baris['diagnosa'])
                                            <p class="mt-1 max-w-xs truncate text-xs text-slate-400">{{ $baris['diagnosa'] }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-500 ring-1 ring-inset ring-slate-200/70">—</span>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-500 ring-1 ring-inset ring-slate-200/70">{{ $baris['sumberLabel'] }}</span>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $statusStyle['tercatat'] }}">Tercatat</span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right">
                                        <a href="{{ $baris['detailUrl'] }}"
                                           class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-200">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @else
                                {{-- ==== Baris penjadwalan (reminder) ==== --}}
                                @php($r = $baris)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="whitespace-nowrap px-5 py-3">
                                        <p class="font-semibold text-slate-800">{{ $r['tanggal']?->translatedFormat('d M Y') }}</p>
                                        <p class="text-xs text-slate-400">{{ $r['jam']?->format('H:i') }} WIB</p>
                                    </td>
                                    <td class="px-5 py-3">
                                        <p class="font-semibold text-slate-800">{{ $r['pasien']?->nama }}</p>
                                        <p class="text-xs text-slate-400">NIP {{ $r['pasien']?->nip ?? '—' }} · {{ $r['pasien']?->satker?->nama ?? '—' }}</p>
                                    </td>
                                    <td class="px-5 py-3">
                                        <p class="text-xs font-medium text-slate-600">{{ $r['poliNama'] ?? '—' }}</p>
                                        <p class="text-xs text-slate-400">{{ $r['dokter'] ?? 'Dokter belum ditentukan' }}</p>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $r['homeVisit'] ? 'bg-teal-50 text-teal-700 ring-teal-200/70' : 'bg-slate-100 text-slate-500 ring-slate-200/70' }}">
                                            {{ $r['homeVisit'] ? 'Home Visit' : 'Kunjungan RS' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $r['sudahKunjungan'] ? 'bg-emerald-50 text-emerald-700 ring-emerald-200/70' : 'bg-slate-100 text-slate-500 ring-slate-200/70' }}">
                                            {{ $r['sumberLabel'] }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $statusStyle[$r['status']] ?? '' }}">
                                            {{ $statusLabel[$r['status']] ?? $r['status'] }}
                                        </span>
                                        @if ($r['sudahKunjungan'])
                                            <span class="mt-0.5 block text-[10px] text-emerald-500">kunjungan tercatat</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right">
                                        @if (! $r['sudahKunjungan'])
                                            <a href="{{ $r['catatUrl'] }}"
                                               class="rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200 transition hover:bg-emerald-100">
                                                Catat Kunjungan
                                            </a>
                                        @endif
                                        <a href="{{ $r['editUrl'] }}"
                                           class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-200">
                                            Edit
                                        </a>
                                        <button type="button"
                                                onclick="confirmSubmit('{{ $r['hapusUrl'] }}', {
                                                    title: 'Hapus penjadwalan?',
                                                    html: 'Jadwal <strong>{{ $r['pasien']?->nama }}</strong> pada {{ $r['tanggal']?->translatedFormat('d M Y') }} akan dihapus permanen.',
                                                    confirmText: 'Ya, hapus'
                                                })"
                                                class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 ring-1 ring-inset ring-rose-200 transition hover:bg-rose-100">
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-3">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</div>
@endsection