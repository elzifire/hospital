@extends('layouts.app')

@section('title', 'Tong Sampah — Digital Reminder')
@section('page-title', 'Tong Sampah')

@section('content')
@php
    $statusStyle = [
        'terjadwal'    => 'bg-sky-50 text-sky-700 ring-sky-200/70',
        'selesai'      => 'bg-emerald-50 text-emerald-700 ring-emerald-200/70',
        'tidak_datang' => 'bg-rose-50 text-rose-700 ring-rose-200/70',
        'dibatalkan'   => 'bg-slate-100 text-slate-500 ring-slate-200/70',
        'jadwal_ulang' => 'bg-indigo-50 text-indigo-700 ring-indigo-200/70',
        'tercatat'     => 'bg-teal-50 text-teal-700 ring-teal-200/70',
    ];

    $statusLabel = ['terjadwal' => 'Terjadwal', 'selesai' => 'Selesai', 'tidak_datang' => 'Tidak Datang', 'dibatalkan' => 'Dibatalkan', 'jadwal_ulang' => 'Jadwal Ulang', 'tercatat' => 'Tercatat'];
    $ctl = 'h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500';
    $lbl = 'mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500';
@endphp

<div class="space-y-6">
    {{-- ===== Header + breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.digital-reminder.index') }}" class="rounded transition hover:text-sky-600">Digital Reminder</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">Tong Sampah</span>
        </nav>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-slate-900">Tong Sampah</h2>
                <p class="mt-0.5 text-sm text-slate-500">Penjadwalan yang dihapus (soft delete) tetap tersimpan di sini sampai dipulihkan kembali ke daftar sesi.</p>
            </div>
            <a href="{{ route('admin.digital-reminder.index') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" /></svg>
                Kembali ke Daftar Sesi
            </a>
        </div>
    </div>

    {{-- ===== Kartu Ringkasan ===== --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-2">
        <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-xs ring-1 ring-slate-200">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
            </div>
            <div class="min-w-0">
                <p class="text-lg font-extrabold leading-tight tabular-nums text-slate-900">{{ number_format($totalTrashed) }}</p>
                <p class="truncate text-[11px] font-medium uppercase tracking-wide text-slate-400">Penjadwalan Dihapus</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-xs ring-1 ring-slate-200">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z" /></svg>
            </div>
            <div class="min-w-0">
                <p class="text-lg font-extrabold leading-tight tabular-nums text-slate-900">{{ number_format($totalPasien) }}</p>
                <p class="truncate text-[11px] font-medium uppercase tracking-wide text-slate-400">Pasien Terdampak</p>
            </div>
        </div>
    </div>

    {{-- ===== Daftar penjadwalan terhapus ===== --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Item Dihapus</h3>
                <p class="mt-0.5 text-xs text-slate-500">
                    Hapus memindahkan penjadwalan ke sini. Gunakan <strong>Pulihkan</strong> untuk mengembalikannya ke daftar sesi bila terhapus karena tidak sengaja.
                </p>
            </div>
        </div>

        <form method="GET" action="{{ request()->url() }}"
              class="flex flex-wrap items-end gap-x-3 gap-y-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4">
            <div class="w-56">
                <label class="{{ $lbl }}">Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Nama / NIP / catatan…"
                       class="{{ $ctl }}">
            </div>
            <div class="w-40">
                <label class="{{ $lbl }}">Status</label>
                <select name="status" class="{{ $ctl }}">
                    <option value="">Semua</option>
                    @foreach ($statusLabel as $key => $label)
                        <option value="{{ $key }}" {{ $filters['status'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if ($batasiPoli)
                <div class="w-48">
                    <label class="{{ $lbl }}">Poli</label>
                    <span class="inline-flex h-10 w-full items-center gap-1.5 rounded-lg bg-violet-50 px-3 text-sm font-semibold text-violet-700 ring-1 ring-violet-200/70">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        <span class="truncate">{{ $polis->first()?->nama }}</span>
                    </span>
                </div>
            @else
                <div class="w-44">
                    <label class="{{ $lbl }}">Poli</label>
                    <select name="poli" class="{{ $ctl }}">
                        <option value="">Semua</option>
                        @foreach ($polis as $po)
                            <option value="{{ $po->id }}" {{ $filters['poli'] == $po->id ? 'selected' : '' }}>{{ $po->nama }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="flex gap-2">
                <button type="submit" class="h-10 rounded-lg bg-sky-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">Filter</button>
                <a href="{{ request()->url() }}" class="h-10 rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-300">Reset</a>
            </div>
        </form>

        @if ($items->isEmpty())
            <div class="px-5 py-12 text-center">
                <p class="text-sm font-medium text-slate-500">Tong sampah kosong — tidak ada penjadwalan yang dihapus.</p>
                @if (($filters['q'] ?? '') !== '' || ($filters['status'] ?? '') !== '' || ($filters['poli'] ?? '') !== '')
                    <p class="mt-1 text-xs text-slate-400">Coba kosongkan filter pencarian di atas.</p>
                @endif
                <div class="mt-4">
                    <a href="{{ route('admin.digital-reminder.index') }}" class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" /></svg>
                        Kembali ke Daftar Sesi
                    </a>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-5 py-3 text-center">No</th>
                            <th class="px-5 py-3">Pasien</th>
                            <th class="px-5 py-3">Jadwal</th>
                            <th class="px-5 py-3">Poli</th>
                            <th class="px-5 py-3">Jenis</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Dihapus Pada</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($items as $item)
                            <tr class="hover:bg-slate-50/60">
                                <td class="whitespace-nowrap px-5 py-3 text-center tabular-nums text-slate-400">
                                    {{ $loop->iteration + ($items->currentPage() - 1) * $items->perPage() }}
                                </td>
                                <td class="px-5 py-3">
                                    <p class="font-semibold text-slate-800">{{ $item->pnpp?->nama ?? '—' }}</p>
                                    <p class="text-xs text-slate-400">NIP {{ $item->pnpp?->nip ?? '—' }} · {{ $item->pnpp?->satker?->nama ?? '—' }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3">
                                    <p class="font-semibold text-slate-800">{{ $item->tanggal?->translatedFormat('d M Y') }}</p>
                                    <p class="text-xs text-slate-400">{{ $item->jam?->format('H:i') }} WIB</p>
                                </td>
                                <td class="px-5 py-3">
                                    <p class="text-xs font-medium text-slate-600">{{ $item->poli?->nama ?? '—' }}</p>
                                    <p class="text-xs text-slate-400">{{ $item->dokter?->nama ?? 'Dokter belum ditentukan' }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $item->home_visit ? 'bg-teal-50 text-teal-700 ring-teal-200/70' : 'bg-slate-100 text-slate-500 ring-slate-200/70' }}">
                                        {{ $item->home_visit ? 'Home Visit' : 'Kunjungan RS' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $statusStyle[$item->status] ?? '' }}">
                                        {{ $statusLabel[$item->status] ?? $item->status }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3">
                                    <p class="font-semibold text-slate-700">{{ $item->deleted_at?->translatedFormat('d M Y') }}</p>
                                    <p class="text-xs text-slate-400">{{ $item->deleted_at?->format('H:i') }} WIB</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-right">
                                    @if ($item->poli_id)
                                        <a href="{{ route('admin.pnpp.kunjungan', $item->pnpp_id) }}"
                                           class="mr-1 inline-block rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-200">
                                            Detail
                                        </a>
                                    @endif
                                    <button type="button"
                                            onclick="confirmSubmit('{{ route('admin.digital-reminder.restore', $item->id) }}', {
                                                title: 'Pulihkan penjadwalan?',
                                                html: 'Jadwal <strong>{{ $item->pnpp?->nama }}</strong> pada {{ $item->tanggal?->translatedFormat('d M Y') }} akan <strong>dikembalikan</strong> ke daftar sesi.',
                                                confirmText: 'Ya, pulihkan',
                                                danger: false,
                                                method: 'POST'
                                            })"
                                            class="rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200 transition hover:bg-emerald-100">
                                        <svg class="mr-1 inline h-3.5 w-3.5 align-[-2px]" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" /></svg>
                                        Pulihkan
                                    </button>
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