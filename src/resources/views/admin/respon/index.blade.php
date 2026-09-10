@extends('layouts.app')

@section('title', 'Respon')
@section('page-title', 'Respon Pasien')

@section('content')
@php
    $stats = [
        ['label' => 'Total Balasan',     'value' => number_format($total),      'tone' => 'sky',     'icon' => 'M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-2.029 2.115 2.115 0 0 0-1.661-.586 48.744 48.744 0 0 0-8.983 0 2.115 2.115 0 0 0-1.661.586 2.126 2.126 0 0 0-.476 2.029c.172.714.308 1.44.41 2.174m3.923-2.174a41.03 41.03 0 0 0-.41 2.174c-.058.35-.088.706-.088 1.066v4.286c0 .36.03.716.088 1.066'],
        ['label' => 'Balasan Hari Ini',  'value' => number_format($hariIni),    'tone' => 'emerald', 'icon' => 'M4.5 12.75l6 6 9-13.5'],
        ['label' => 'Pasien Terdaftar',  'value' => number_format($pasienUnik), 'tone' => 'violet',  'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
        ['label' => 'Nomor Tak Dikenal', 'value' => number_format($takTerdaftar), 'tone' => 'rose',  'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
    ];

    $toneColor = [
        'sky'     => ['bg' => 'bg-sky-50',     'text' => 'text-sky-600'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
        'violet'  => ['bg' => 'bg-violet-50',  'text' => 'text-violet-600'],
        'rose'    => ['bg' => 'bg-rose-50',    'text' => 'text-rose-600'],
    ];
@endphp

<div class="space-y-6">
    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Respon</h2>
            <p class="mt-0.5 text-sm text-slate-500">Balasan pasien yang masuk otomatis.</p>
        </div>
    </div>

    {{-- ===== Kartu Statistik ===== --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
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

    {{-- ===== Tabel balasan + pencarian ===== --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-bold text-slate-900">Balasan Masuk Terbaru</h3>
        </div>

        <form method="GET" action="{{ route('admin.respon.index') }}"
              class="flex flex-wrap items-end gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4">
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Nama / nomor / isi balasan…"
                       class="w-64 rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">Cari</button>
                <a href="{{ route('admin.respon.index') }}" class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-300">Reset</a>
            </div>
        </form>

        @if ($balasan->isEmpty())
            <div class="px-5 py-12 text-center">
                <p class="text-sm font-medium text-slate-500">Belum ada balasan masuk.</p>
                
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-5 py-3">Waktu</th>
                            <th class="px-5 py-3">Pengirim</th>
                            <th class="px-5 py-3">Isi Balasan</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($balasan as $b)
                            <tr class="hover:bg-slate-50/60">
                                <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-500">
                                    {{ $b->waktu_masuk?->format('d M Y, H:i') }}
                                </td>
                                <td class="px-5 py-3">
                                    <p class="font-semibold text-slate-800">
                                        {{ $b->nama ?? 'Nomor Tak Dikenal' }}
                                        @if (! $b->pnpp_id)
                                            <span class="ml-1 rounded-full bg-rose-50 px-1.5 py-0.5 text-[10px] font-semibold text-rose-500">tak terdaftar</span>
                                        @endif
                                    </p>
                                    <p class="font-mono text-xs text-slate-400">{{ $b->no_hp }}</p>
                                </td>
                                <td class="max-w-[280px] px-5 py-3">
                                    <p class="truncate text-xs text-slate-600" title="{{ $b->isi_pesan }}">{{ \Illuminate\Support\Str::limit($b->isi_pesan, 90) }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-right">
                                    <a href="{{ route('admin.respon.show', $b->no_hp) }}"
                                       class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-200">
                                        Percakapan
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-3">
                {{ $balasan->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
