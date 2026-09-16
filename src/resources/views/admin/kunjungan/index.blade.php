@extends('layouts.app')

@section('title', 'Kunjungan')
@section('page-title', 'Kunjungan')

@section('content')
@php
    $stats = [
        ['label' => 'Total Kunjungan', 'value' => number_format($total),       'tone' => 'sky',     'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['label' => 'Pasien',          'value' => number_format($pasien),      'tone' => 'violet',  'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
        ['label' => 'Baris Poli',      'value' => number_format($barisPoli),   'tone' => 'emerald', 'icon' => 'M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72L4.318 3.44A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72m-13.5 8.65h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z'],
        ['label' => 'Realisasi Reminder', 'value' => number_format($realisasi), 'tone' => 'teal',   'icon' => 'M4.5 12.75l6 6 9-13.5'],
        ['label' => 'Manual',          'value' => number_format($manual),      'tone' => 'amber',   'icon' => 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10'],
    ];

    $toneColor = [
        'sky'     => ['bg' => 'bg-sky-50',     'text' => 'text-sky-600'],
        'violet'  => ['bg' => 'bg-violet-50',  'text' => 'text-violet-600'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
        'teal'    => ['bg' => 'bg-teal-50',    'text' => 'text-teal-600'],
        'rose'    => ['bg' => 'bg-rose-50',    'text' => 'text-rose-600'],
        'amber'   => ['bg' => 'bg-amber-50',   'text' => 'text-amber-600'],
    ];
@endphp

<div class="space-y-6">
    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Kunjungan</h2>
            <p class="mt-0.5 text-sm text-slate-500">Riwayat kunjungan pasien yang tercatat — dari catatan manual maupun realisasi penjadwalan digital reminder.</p>
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
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
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

    {{-- ===== Daftar kunjungan ===== --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-bold text-slate-900">Daftar Kunjungan</h3>
            <p class="mt-0.5 text-xs text-slate-500">
                Satu baris = satu kunjungan (pasien + tanggal), bisa mencakup beberapa poli. Sumber <strong>Realisasi Reminder</strong> = kunjungan yang dicatat dari penjadwalan; <strong>Manual</strong> = catatan tanpa jadwal.
            </p>
        </div>

        <form method="GET" action="{{ request()->url() }}"
              class="flex flex-wrap items-end gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4">
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Nama / NIP…"
                       class="w-56 rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
            @if ($batasiPoli)
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
                <p class="text-sm font-medium text-slate-500">Belum ada kunjungan tercatat.</p>
                <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                    @can('manage kunjungan')
                        <a href="{{ route('admin.kunjungan.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                            Catat kunjungan pertama
                        </a>
                    @endcan
                    @can('manage digital-reminder')
                        <a href="{{ route('admin.digital-reminder.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">
                            Buat jadwal digital reminder
                        </a>
                    @endcan
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-5 py-3">Pasien</th>
                            <th class="px-5 py-3">Tanggal</th>
                            <th class="px-5 py-3">Poli</th>
                            <th class="px-5 py-3">Sumber</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($items as $baris)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-5 py-3">
                                    <p class="font-semibold text-slate-800">{{ $baris['pasien']?->nama ?? '—' }}</p>
                                    <p class="text-xs text-slate-400">NIP {{ $baris['pasien']?->nip ?? '—' }} · {{ $baris['pasien']?->satker?->nama ?? '—' }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3">
                                    <p class="font-semibold text-slate-800">{{ $baris['tanggal']?->translatedFormat('d M Y') }}</p>
                                    <p class="text-xs text-slate-400">{{ $baris['jumlahPoli'] }} poli dikunjungi</p>
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
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $baris['sumber'] === 'realisasi' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200/70' : 'bg-teal-50 text-teal-700 ring-teal-200/70' }}">
                                        {{ $baris['sumberLabel'] }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-right">
                                    <a href="{{ $baris['detailUrl'] }}"
                                       class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-200">
                                        Detail
                                    </a>
                                </td>
                            </tr>
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