@extends('layouts.app')

@section('title', 'Outreach')
@section('page-title', 'Outreach')

@section('content')
@php
    $stats = [
        ['label' => 'Total Pesan',      'value' => number_format($total),                 'tone' => 'sky',     'icon' => 'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46'],
        ['label' => 'Dalam Proses',     'value' => number_format((int) ($perStatus['menunggu'] ?? 0) + (int) ($perStatus['mengirim'] ?? 0)), 'tone' => 'amber',   'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['label' => 'Terkirim',         'value' => number_format((int) ($perStatus['terkirim'] ?? 0)),   'tone' => 'emerald', 'icon' => 'M4.5 12.75l6 6 9-13.5'],
        ['label' => 'Gagal',            'value' => number_format((int) ($perStatus['gagal'] ?? 0)),      'tone' => 'rose',    'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
        ['label' => 'Penerima Unik',    'value' => number_format($penerimaUnik),           'tone' => 'violet',  'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
    ];

    $toneColor = [
        'sky'     => ['bg' => 'bg-sky-50',     'text' => 'text-sky-600'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
        'amber'   => ['bg' => 'bg-amber-50',   'text' => 'text-amber-600'],
        'rose'    => ['bg' => 'bg-rose-50',    'text' => 'text-rose-600'],
        'violet'  => ['bg' => 'bg-violet-50',  'text' => 'text-violet-600'],
    ];

    $statusStyle = [
        'terkirim'   => 'bg-emerald-50 text-emerald-700 ring-emerald-200/70',
        'mengirim'   => 'bg-sky-50 text-sky-700 ring-sky-200/70',
        'menunggu'   => 'bg-amber-50 text-amber-700 ring-amber-200/70',
        'gagal'      => 'bg-rose-50 text-rose-700 ring-rose-200/70',
        'dibatalkan' => 'bg-slate-100 text-slate-500 ring-slate-200/70',
    ];

    $statusLabel = \App\Models\MessageLog::LABEL_STATUS;
@endphp

<div class="space-y-6">
    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Outreach</h2>
            <p class="mt-0.5 text-sm text-slate-500">Pesan WhatsApp Blast</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @can('manage outreach')
                <a href="{{ route('admin.outreach.create') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-slate-300 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487 18.549 2.8a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                    Kirim Pesan
                </a>
            @endcan
        </div>
    </div>

    {{-- ===== Kartu Statistik ===== --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
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

    {{-- ===== Riwayat + filter ===== --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-bold text-slate-900">Riwayat Pesan</h3>
        </div>

        <form method="GET" action="{{ route('admin.outreach.index') }}"
              class="flex flex-wrap items-end gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4">
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Nama / nomor / isi pesan…"
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
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">Filter</button>
                <a href="{{ route('admin.outreach.index') }}" class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-300">Reset</a>
            </div>
        </form>

        @if ($logs->isEmpty())
            <div class="px-5 py-12 text-center">
                <p class="text-sm font-medium text-slate-500">Belum ada pesan outreach. Buat jadwal di Digital Reminder — pesan dibuat otomatis oleh sistem setiap pagi.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-5 py-3">Penerima</th>
                            <th class="px-5 py-3">Pesan</th>
                            <th class="px-5 py-3">Template</th>
                            <th class="px-5 py-3">Waktu</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($logs as $log)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-5 py-3">
                                    <p class="font-semibold text-slate-800">{{ $log->penerima_nama }}</p>
                                    <p class="font-mono text-xs text-slate-400">{{ $log->penerima_no_hp }}</p>
                                </td>
                                <td class="max-w-[300px] px-5 py-3">
                                    <p class="truncate text-xs text-slate-600" title="{{ $log->konten }}">{{ \Illuminate\Support\Str::limit($log->konten, 90) }}</p>
                                </td>
                                <td class="px-5 py-3 text-xs text-slate-500">{{ $log->template?->judul ?? '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-500">
                                    {{ $log->created_at->format('d M Y, H:i') }}
                                    @if ($log->status === 'menunggu' && $log->kirim_pada)
                                        <span class="block text-[10px] text-sky-500">dijadwalkan {{ $log->kirim_pada->format('d M H:i') }}</span>
                                    @endif
                                    @if ($log->sent_at)
                                        <span class="block text-[10px] text-emerald-500">terkirim {{ $log->sent_at->format('d M H:i') }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $statusStyle[$log->status] ?? '' }}">
                                        {{ $statusLabel[$log->status] ?? $log->status }}
                                    </span>
                                    @if ($log->status === 'gagal' && $log->error)
                                        <span class="mt-0.5 block max-w-[180px] truncate text-[10px] text-rose-400" title="{{ $log->error }}">{{ $log->error }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('admin.broadcast.show', $log->id) }}"
                                           class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-200">Detail</a>
                                        @if ($log->status === 'menunggu')
                                            @if ($log->kirim_group)
                                                <a href="{{ route('admin.outreach.edit', $log->kirim_group) }}"
                                                   class="rounded-lg bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-600 ring-1 ring-inset ring-sky-200 transition hover:bg-sky-100">
                                                    Ubah
                                                </a>
                                            @endif
                                            <button type="button"
                                                    onclick="confirmSubmit('{{ route('admin.broadcast.batalkan', $log->id) }}', {
                                                        title: 'Batalkan pesan?',
                                                        html: 'Pesan ke <strong>{{ $log->penerima_nama }}</strong> tidak akan dikirim.',
                                                        confirmText: 'Ya, batalkan'
                                                    })"
                                                    class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 ring-1 ring-inset ring-rose-200 transition hover:bg-rose-100">
                                                Batalkan
                                            </button>
                                        @elseif ($log->status === 'gagal')
                                            <button type="button"
                                                    onclick="confirmSubmit('{{ route('admin.broadcast.kirim-ulang', $log->id) }}', {
                                                        title: 'Kirim ulang pesan?',
                                                        html: 'Pesan ke <strong>{{ $log->penerima_nama }}</strong> akan dikirim sekarang.',
                                                        confirmText: 'Ya, kirim ulang',
                                                        method: 'POST'
                                                    })"
                                                    class="rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-600 ring-1 ring-inset ring-amber-200 transition hover:bg-amber-100">
                                                Kirim Ulang
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-3">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
