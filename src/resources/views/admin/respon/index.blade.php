@extends('layouts.app')

@section('title', 'Respon')
@section('page-title', 'Respon Pasien')

@section('content')
@php
    use App\Jobs\PreviewImportJob;
    use App\Support\MasterRegistry;

    $responHeaders = MasterRegistry::config('respon')['headers'];
@endphp

@if ($tab === 'balasan')
@php
    $stats = [
        ['label' => 'Total Balasan',     'value' => number_format($total),       'tone' => 'sky',     'icon' => 'M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-2.029 2.115 2.115 0 0 0-1.661-.586 48.744 48.744 0 0 0-8.983 0 2.115 2.115 0 0 0-1.661.586 2.126 2.126 0 0 0-.476 2.029c.172.714.308 1.44.41 2.174m3.923-2.174a41.03 41.03 0 0 0-.41 2.174c-.058.35-.088.706-.088 1.066v4.286c0 .36.03.716.088 1.066'],
        ['label' => 'Balasan Hari Ini',  'value' => number_format($hariIni),     'tone' => 'emerald', 'icon' => 'M4.5 12.75l6 6 9-13.5'],
        ['label' => 'Pasien Terdaftar',  'value' => number_format($pasienUnik),  'tone' => 'violet',  'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
        ['label' => 'Nomor Tak Dikenal', 'value' => number_format($takTerdaftar), 'tone' => 'rose',   'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
    ];

    $toneColor = [
        'sky'     => ['bg' => 'bg-sky-50',     'text' => 'text-sky-600'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
        'violet'  => ['bg' => 'bg-violet-50',  'text' => 'text-violet-600'],
        'rose'    => ['bg' => 'bg-rose-50',    'text' => 'text-rose-600'],
    ];
@endphp
@endif

<div class="space-y-6">
    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Respon</h2>
            <p class="mt-0.5 text-sm text-slate-500">Balasan pasien yang masuk otomatis, input manual, atau import dari Excel.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-2xl bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-2xl bg-rose-50 px-5 py-4 text-sm font-medium text-rose-700 ring-1 ring-rose-200">{{ session('error') }}</div>
    @endif

    {{-- ===== Tab navigasi ===== --}}
    <div class="flex flex-wrap items-center gap-1 border-b border-slate-200">
        @php($tabs = ['balasan' => 'Balasan WhatsApp', 'data' => 'Data Respon', 'manual' => 'Input Manual', 'import' => 'Import Excel'])
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.respon.index', $tab === $key ? [] : ['tab' => $key]) }}"
               class="inline-flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-semibold transition
                      {{ $tab === $key ? 'border-sky-500 text-sky-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    @if ($tab === 'balasan')
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
    @elseif ($tab === 'data')
        {{-- ===== Index terpisah: balasan manual / import ===== --}}
        <div class="space-y-4">
            <div class="flex items-start gap-3 rounded-2xl bg-sky-50 px-5 py-4 text-sm text-sky-800 ring-1 ring-sky-200">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-.118 5.118h.007v.007h-.007v-.007ZM3 13.125A9.375 9.375 0 0 1 12.375 3.75h1.5A6.375 6.375 0 0 1 20.25 10.125v1.5a9.375 9.375 0 0 1-9.375 9.375H9.375a6.375 6.375 0 0 1-6.375-6.375Z" /></svg>
                <p>Data balasan yang dicatat manual atau diimpor dari Excel — <span class="font-semibold">terpisah</span> dari balasan otomatis WhatsApp (webhook).</p>
            </div>

            <div class="grid grid-cols-3 gap-3">
                @foreach ([
                    ['label' => 'Total Data', 'value' => number_format($totalRespon), 'cls' => 'bg-sky-50 text-sky-600'],
                    ['label' => 'Input Manual', 'value' => number_format($totalManual), 'cls' => 'bg-emerald-50 text-emerald-600'],
                    ['label' => 'Dari Import', 'value' => number_format($totalImport), 'cls' => 'bg-violet-50 text-violet-600'],
                ] as $s)
                    <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-xs ring-1 ring-slate-200">
                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg {{ $s['cls'] }}">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-lg font-extrabold leading-tight tabular-nums text-slate-900">{{ $s['value'] }}</p>
                            <p class="truncate text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ $s['label'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Data Respon (Manual & Import)</h3>
                </div>

                <form method="GET" action="{{ route('admin.respon.index') }}"
                      class="flex flex-wrap items-end gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4">
                    <input type="hidden" name="tab" value="data">
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Cari</label>
                        <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Nama / NRP-NIP / nomor / satker / isi…"
                               class="w-64 rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">Cari</button>
                        <a href="{{ route('admin.respon.index', ['tab' => 'data']) }}" class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-300">Reset</a>
                    </div>
                </form>

                @if ($dataRespon->isEmpty())
                    <div class="px-5 py-12 text-center">
                        <p class="text-sm font-medium text-slate-500">Belum ada data. Tambah via tab "Input Manual" atau unduh template di tab "Import Excel".</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                <tr>
                                    <th class="px-5 py-3">Waktu</th>
                                    <th class="px-5 py-3">Sumber</th>
                                    <th class="px-5 py-3">Data</th>
                                    <th class="px-5 py-3">Isi / Satker</th>
                                    <th class="px-5 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($dataRespon as $d)
                                    <tr class="hover:bg-slate-50/60 align-top">
                                        <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-500">
                                            {{ $d->waktu?->format('d M Y, H:i') }}
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-3">
                                            @if ($d->sumber === \App\Models\ResponManual::SUMBER_MANUAL)
                                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-600">MANUAL</span>
                                            @else
                                                <span class="rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-semibold text-violet-600">IMPORT</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3">
                                            <p class="font-semibold text-slate-800">{{ $d->nama ?? '—' }}</p>
                                            @if ($d->nrp_nip)
                                                <p class="font-mono text-xs text-slate-400">NRP/NIP: {{ $d->nrp_nip }}</p>
                                            @endif
                                            <p class="font-mono text-xs text-slate-400">{{ $d->no_hp }}</p>
                                        </td>
                                        <td class="max-w-[300px] px-5 py-3">
                                            @if ($d->satker)
                                                <p class="text-[11px] font-semibold uppercase tracking-wide text-sky-600">{{ $d->satker }}</p>
                                            @endif
                                            <p class="text-xs text-slate-600" title="{{ $d->isi }}">{{ \Illuminate\Support\Str::limit($d->isi, 110) }}</p>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-3 text-right">
                                            <form method="POST" action="{{ route('admin.respon.manual-destroy', $d) }}"
                                                  onsubmit="return confirm('Hapus data respon ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-100">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-100 px-5 py-3">
                        {{ $dataRespon->links() }}
                    </div>
                @endif
            </div>
        </div>
    @elseif ($tab === 'manual')
        {{-- ===== Input Manual ===== --}}
        <div class="mx-auto max-w-2xl space-y-4">
            @if ($errors->any())
                <div class="rounded-2xl bg-rose-50 px-5 py-4 text-sm text-rose-700 ring-1 ring-rose-200">
                    Harap perbaiki: {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.respon.manual-store') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                <h3 class="mb-4 text-base font-bold text-slate-900">Catat Balasan Manual</h3>

                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Nama</label>
                        <input type="text" name="nama" value="{{ old('nama') }}" placeholder="Nama pasien"
                               class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">NRP/NIP</label>
                        <input type="text" name="nrp_nip" value="{{ old('nrp_nip') }}" placeholder="NRP / NIP pasien"
                               class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">No. HP <span class="text-rose-500">*</span></label>
                        <input type="text" name="no_hp" value="{{ old('no_hp') }}" placeholder="081234567890" required
                               class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Satker</label>
                        <input type="text" name="satker" value="{{ old('satker') }}" placeholder="Satuan Kerja"
                               class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Isi <span class="text-rose-500">*</span></label>
                        <textarea name="isi" rows="4" required placeholder="Isi balasan pasien…"
                                  class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('isi') }}</textarea>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Waktu Masuk</label>
                        <input type="datetime-local" name="waktu" value="{{ old('waktu') }}"
                               class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        <p class="mt-1 text-xs text-slate-400">Kosongkan untuk memakai waktu sekarang.</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75 6 14.25l3.75-3.75M4.5 18.75l9.75-9.75 3 3" /></svg>
                        Simpan Balasan
                    </button>
                </div>
            </form>
        </div>
    @else
        {{-- ===== Import Excel/CSV ===== --}}
        <div class="mx-auto max-w-6xl space-y-5">
            @if ($errors->any())
                <div class="rounded-2xl bg-rose-50 px-5 py-4 text-sm text-rose-700 ring-1 ring-rose-200">
                    {{ $errors->first('file') ?? $errors->first() }}
                </div>
            @endif

            {{-- Status import (dari queue) --}}
            @if ($importStatus)
                @php($s = $importStatus)
                @if (in_array($s['status'], ['pending', 'processing', 'preview_pending', 'preview_processing'], true))
                    <div class="flex items-center gap-4 rounded-2xl bg-sky-50 p-5 ring-1 ring-sky-200">
                        <svg class="h-8 w-8 animate-spin text-sky-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <div>
                            <p class="text-sm font-bold text-sky-800">Sedang diproses...</p>
                            <p class="text-xs text-sky-600">
                                Data diproses per batch. Halaman ini akan diperbarui otomatis.
                                @if (isset($s['processed'], $s['total']) && $s['total'] > 0)
                                    <span class="font-semibold">{{ $s['processed'] }} / {{ $s['total'] }} baris</span>
                                @endif
                            </p>
                        </div>
                    </div>
                @elseif ($s['status'] === 'completed')
                    <div class="rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-200">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </span>
                            <div>
                                <p class="text-sm font-bold text-slate-900">Import selesai</p>
                                <p class="text-xs text-slate-500">
                                    <span class="font-semibold text-emerald-600">{{ $s['created'] }}</span> ditambahkan ·
                                    <span class="font-semibold text-sky-600">{{ $s['updated'] }}</span> diperbarui ·
                                    <span class="font-semibold text-rose-600">{{ $s['failed'] }}</span> gagal
                                </p>
                            </div>
                        </div>

                        @if (! empty($s['errors']))
                            <div class="mt-4 rounded-xl bg-rose-50 p-4 ring-1 ring-rose-100">
                                <p class="mb-2 text-xs font-bold uppercase tracking-wide text-rose-600">Baris yang gagal</p>
                                <ul class="space-y-2 text-xs text-rose-700">
                                    @foreach ($s['errors'] as $err)
                                        <li class="rounded-lg bg-white/60 p-2.5">
                                            <p class="font-mono text-[10px] text-slate-400">{{ implode(', ', $err['row']) }}</p>
                                            <p class="mt-0.5 font-medium">{{ implode(' · ', $err['errors']) }}</p>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @elseif (in_array($s['status'], ['failed', 'preview_failed'], true))
                    <div class="flex items-start gap-3 rounded-2xl bg-rose-50 p-5 ring-1 ring-rose-200">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                        <div>
                            <p class="text-sm font-bold text-rose-800">Proses gagal</p>
                            <p class="mt-0.5 text-xs text-rose-600">{{ $s['message'] ?? 'Terjadi kesalahan.' }}</p>
                        </div>
                    </div>
                @endif
            @endif

            {{-- Preview (editable) / Form upload --}}
            @if ($preview)
                <form action="{{ route('admin.respon.import-confirm') }}" method="POST" x-data="responImportPreview()" x-cloak>
                    @csrf
                    <input type="hidden" name="token" value="{{ $importToken }}">
                    @if ($preview['total'] <= PreviewImportJob::PREVIEW_LIMIT)
                        <input type="hidden" name="rows" :value="rowsJson">
                    @endif

                    <div class="space-y-4">
                        <div class="flex flex-col gap-3 rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-200 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Pratinjau & Edit Data</h3>
                                <p class="mt-0.5 text-sm text-slate-500">
                                    @if ($preview['total'] <= PreviewImportJob::PREVIEW_LIMIT)
                                        Klik sel tabel untuk mengedit langsung sebelum diproses.
                                    @else
                                        File besar diproses dari backend per batch; preview hanya menampilkan {{ PreviewImportJob::PREVIEW_LIMIT }} baris pertama.
                                    @endif
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 ring-1 ring-slate-200">{{ $preview['total'] }} baris</span>
                                @if ($preview['total'] > count($preview['rows']))
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 ring-1 ring-sky-200">Preview {{ count($preview['rows']) }} baris pertama</span>
                                @endif
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200/70">{{ $preview['valid'] }} valid</span>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 ring-1 ring-rose-200/70">{{ $preview['invalid'] }} perlu diperiksa</span>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[720px] text-left">
                                    <thead>
                                        <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                                            <th class="px-3 py-3 font-semibold" style="width: 44px; min-width: 44px">#</th>
                                            @foreach ($responHeaders as $header)
                                                <th class="px-3 py-3 font-semibold" style="width: 170px; min-width: 110px">{{ $header }}</th>
                                            @endforeach
                                            <th class="px-3 py-3 font-semibold" style="width: 200px; min-width: 160px">Status Awal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <template x-for="(r, i) in rows" :key="i">
                                            <tr :class="r.errors.length > 0 ? 'bg-rose-50/40' : ''">
                                                <td class="px-4 py-2 font-mono text-xs text-slate-400" x-text="i + 2"></td>
                                                <template x-for="h in headers" :key="h">
                                                    <td class="px-1.5 py-1.5 align-top">
                                                        <input type="text" x-model="rows[i].values[h]"
                                                               class="w-full rounded-lg border-0 bg-slate-50 px-2.5 py-2 text-xs text-slate-800 ring-1 ring-inset ring-slate-200 transition focus:bg-white focus:ring-2 focus:ring-sky-500">
                                                    </td>
                                                </template>
                                                <td class="px-4 py-2">
                                                    <span x-show="r.errors.length === 0" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                                        Valid
                                                    </span>
                                                    <span x-show="r.errors.length > 0" class="inline-flex items-center gap-1 text-xs font-semibold text-rose-600" :title="r.errors.join(' · ')">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                                                        <span class="max-w-[220px] truncate" x-text="r.errors.join(' · ')"></span>
                                                    </span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-200 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs text-slate-500">Semua baris akan diimport; baris yang tidak valid dilewati otomatis.</p>
                            <div class="flex items-center gap-3">
                                <form method="POST" action="{{ route('admin.respon.import-cancel') }}">
                                    @csrf
                                    <input type="hidden" name="token" value="{{ $importToken }}">
                                    <button type="submit"
                                            class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Batalkan</button>
                                </form>
                                <button type="submit"
                                        class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75 6 14.25l3.75-3.75M4.5 18.75l9.75-9.75 3 3" /></svg>
                                    Proses Import
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            @else
                <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="border-b border-slate-100 p-6">
                        <h3 class="text-base font-bold text-slate-900">Import Balasan dari File</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Mendukung file Excel (.xlsx/.xls) dan CSV dengan header sesuai template. Data diproses per batch di queue agar tidak memberatkan server.</p>
                    </div>

                    <div class="p-6">
                        <form action="{{ route('admin.respon.import-upload') }}" method="POST" enctype="multipart/form-data" x-data="{ saving: false }" @submit="saving = true">
                            @csrf
                            <div class="space-y-5">
                                <div>
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">File (.xlsx / .xls / .csv) <span class="text-rose-500">*</span></label>
                                    <input type="file" name="file" accept=".xlsx,.xls,.csv,text/csv" required
                                           class="block w-full cursor-pointer rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition">
                                </div>

                                <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                    <p class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-400">Kolom yang diharapkan</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($responHeaders as $header)
                                            <code class="rounded-md bg-white px-2 py-1 font-mono text-[11px] text-slate-600 ring-1 ring-slate-200">{{ $header }}</code>
                                        @endforeach
                                    </div>
                                    <div class="mt-3 flex flex-wrap items-center gap-3">
                                        <a href="{{ route('admin.master.template', ['entity' => 'respon', 'format' => 'xlsx']) }}"
                                           class="inline-flex items-center gap-1.5 text-xs font-bold text-sky-600 hover:underline">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                            Template Excel
                                        </a>
                                        <a href="{{ route('admin.master.template', ['entity' => 'respon', 'format' => 'csv']) }}"
                                           class="inline-flex items-center gap-1.5 text-xs font-bold text-sky-600 hover:underline">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                            Template CSV
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex items-center justify-end gap-3">
                                <a href="{{ route('admin.respon.index', ['tab' => 'balasan']) }}"
                                   class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Kembali</a>
                                <button type="submit" :disabled="saving"
                                        class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-50">
                                    <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                                    <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span x-text="saving ? 'Memeriksa...' : 'Pratinjau'"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>

@if ($tab === 'import')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('responImportPreview', () => ({
                headers: @js($responHeaders),
                rows: @js($preview['rows'] ?? []),
                get rowsJson() {
                    return JSON.stringify(this.rows.map(r => r.values));
                },
            }));
        });
    </script>

    @if ($importStatus && in_array($importStatus['status'], ['pending', 'processing', 'preview_pending', 'preview_processing'], true))
        <script>setTimeout(() => window.location.reload(), 3000);</script>
    @endif
@endif
@endsection