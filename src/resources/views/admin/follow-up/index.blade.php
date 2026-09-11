@extends('layouts.app')

@section('title', 'Follow Up')
@section('page-title', 'Follow Up')

@section('content')
@php
    $stats = [
        ['label' => 'Total Pesan',      'value' => number_format($total),                 'tone' => 'sky',     'icon' => 'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z'],
        ['label' => 'Dalam Proses',     'value' => number_format((int) ($perStatus['menunggu'] ?? 0) + (int) ($perStatus['mengirim'] ?? 0)), 'tone' => 'amber',   'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['label' => 'Terkirim',         'value' => number_format((int) ($perStatus['terkirim'] ?? 0)),   'tone' => 'emerald', 'icon' => 'M4.5 12.75l6 6 9-13.5'],
        ['label' => 'Gagal',            'value' => number_format((int) ($perStatus['gagal'] ?? 0)),      'tone' => 'rose',    'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
        ['label' => 'Pasien Difollow Up', 'value' => number_format($penerimaUnik),         'tone' => 'violet',  'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
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
    $ruleLabel   = ['h-1' => 'H-1', 'h' => 'Hari-H', 'tidak_datang' => 'Tidak Datang', 'manual' => 'Manual'];
@endphp

<div class="space-y-6">
    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Follow Up</h2>
            <p class="mt-0.5 text-sm text-slate-500">Pesan tindak lanjut jadwal (H-1, hari-H, dan tidak datang) yang dibuat dari penjadwalan.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @can('manage respon')
                <a href="{{ route('admin.respon.index') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
                    Lihat Balasan (Respon)
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
            <h3 class="text-sm font-bold text-slate-900">Riwayat Follow Up</h3>
        </div>

        <form method="GET" action="{{ route('admin.follow-up.index') }}"
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
                <a href="{{ route('admin.follow-up.index') }}" class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-300">Reset</a>
            </div>
        </form>

        @if ($logs->isEmpty())
            <div class="px-5 py-12 text-center">
                <p class="text-sm font-medium text-slate-500">Belum ada pesan follow up. Pesan dibuat otomatis oleh sistem dari jadwal di Digital Reminder.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-5 py-3">Penerima</th>
                            <th class="px-5 py-3">Pesan</th>
                            <th class="px-5 py-3">Template</th>
                            <th class="px-5 py-3">Aturan</th>
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
                                <td class="px-5 py-3">
                                    @if ($log->rule)
                                        <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-200/70">{{ $ruleLabel[$log->rule] ?? $log->rule }}</span>
                                    @else
                                        <span class="text-xs text-slate-300">—</span>
                                    @endif
                                </td>
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
