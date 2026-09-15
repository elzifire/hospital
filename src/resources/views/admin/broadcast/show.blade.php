@extends('layouts.app')

@section('title', 'Detail Pesan')
@section('page-title', 'Detail Pesan')

@section('content')
@php
    $statusStyle = [
        'terkirim'   => 'bg-emerald-50 text-emerald-700 ring-emerald-200/70',
        'mengirim'   => 'bg-sky-50 text-sky-700 ring-sky-200/70',
        'menunggu'   => 'bg-amber-50 text-amber-700 ring-amber-200/70',
        'gagal'      => 'bg-rose-50 text-rose-700 ring-rose-200/70',
        'dibatalkan' => 'bg-slate-100 text-slate-500 ring-slate-200/70',
    ];

    $statusText = [
        'terkirim'   => 'text-emerald-600',
        'mengirim'   => 'text-sky-600',
        'menunggu'   => 'text-amber-600',
        'gagal'      => 'text-rose-600',
        'dibatalkan' => 'text-slate-500',
    ];

    $ruleLabel = [
        'h-7' => '7 Hari Sebelumnya', 'h-1' => '1 Hari Sebelumnya', 'h' => 'Hari Periksa',
        'tidak_datang' => 'Tidak Hadir', 'manual' => 'Dikirim Manual', 'balasan' => 'Balasan',
    ];

    $jenisLabel  = ['outreach' => 'Pengingat', 'follow_up' => 'Tindak Lanjut', 'respon' => 'Respon'];
    $statusLabel = \App\Models\MessageLog::LABEL_STATUS;

    $indeksRiwayat = $log->jenis === 'outreach' ? 'admin.outreach.index' : ($log->jenis === 'respon' ? 'admin.respon.index' : 'admin.follow-up.index');

    $inicial = strtoupper(substr(trim((string) $log->penerima_nama), 0, 1)) ?: '?';
    $final = in_array($log->status, ['terkirim', 'gagal', 'dibatalkan']);

    $barisParam = [];
    foreach ((array) $log->template_params as $i => $nilai) {
        $barisParam[] = ['label' => '{'.($i + 1).'}', 'nilai' => (string) $nilai];
    }

    $tahapan = [
        [
            'Pesan disiapkan',
            true,
            $log->created_at?->translatedFormat('d M Y, H:i'),
            'Pesan dibuat' . ($log->creator ? ' oleh ' . $log->creator->name : '') . ' karena ' . ($ruleLabel[$log->rule] ?? $log->rule),
        ],
        [
            'Waktu kirim ditentukan',
            (bool) $log->kirim_pada,
            $log->kirim_pada?->translatedFormat('d M Y, H:i'),
            $log->kirim_pada
                ? 'Pesan akan dikirim otomatis sesuai jadwal'
                : 'Pesan akan dikirim segera karena tidak ada jadwal khusus',
        ],
        [
            'Sedang mengirim pesan',
            in_array($log->status, ['mengirim', 'terkirim']),
            null,
            'Sistem sedang mengirim pesan lewat WhatsApp',
        ],
        [
            $log->status === 'gagal' ? 'Gagal terkirim' : ($log->status === 'dibatalkan' ? 'Dibatalkan' : 'Berhasil terkirim'),
            $final,
            $log->sent_at?->translatedFormat('d M Y, H:i'),
            $log->status === 'gagal'
                ? 'Pesan tidak sampai: ' . ($log->error ?? 'terjadi masalah teknis')
                : ($log->status === 'dibatalkan' ? 'Pesan dibatalkan sebelum terkirim' : 'Pesan sudah diterima oleh WhatsApp'),
        ],
    ];
@endphp

<div class="space-y-6">
    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-xl font-bold tracking-tight text-slate-900">Detail Pesan</h2>
                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $statusStyle[$log->status] ?? '' }}">
                    @if ($log->status === 'terkirim')
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    @elseif ($log->status === 'gagal')
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    @endif
                    {{ $statusLabel[$log->status] ?? $log->status }}
                </span>
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-200/70">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                    {{ $ruleLabel[$log->rule] ?? $log->rule }}
                </span>
            </div>
            <p class="mt-1.5 text-xs text-slate-400">
                Dibuat {{ $log->created_at?->translatedFormat('d M Y, H:i') }}
                @if ($log->creator) &middot; oleh {{ $log->creator->name }} @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($log->status === 'gagal')
                <button type="button"
                        onclick="confirmSubmit('{{ route('admin.broadcast.kirim-ulang', $log->id) }}', {
                            title: 'Kirim ulang pesan?',
                            html: 'Pesan ke <strong>{{ $log->penerima_nama }}</strong> akan dikirim sekarang.',
                            confirmText: 'Ya, kirim sekarang',
                            method: 'POST'
                        })"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                    Kirim Ulang Sekarang
                </button>
            @endif
            @if ($log->status === 'menunggu')
                <button type="button"
                        onclick="confirmSubmit('{{ route('admin.broadcast.batalkan', $log->id) }}', {
                            title: 'Batalkan pengiriman?',
                            html: 'Pesan ke <strong>{{ $log->penerima_nama }}</strong> tidak akan dikirim.',
                            confirmText: 'Ya, batalkan'
                        })"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-rose-200 bg-white px-5 py-2.5 text-sm font-semibold text-rose-600 shadow-xs transition hover:bg-rose-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    Batalkan Pengiriman
                </button>
            @endif
            <a href="{{ route($indeksRiwayat) }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                Kembali
            </a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- ===== Kolom kiri: isi pesan, jadwal, balasan ===== --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Isi Pesan --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-linear-to-br from-sky-500 to-indigo-500 text-sm font-bold uppercase text-white shadow-sm ring-2 ring-white">{{ $inicial }}</div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-slate-900">{{ $log->penerima_nama }}</p>
                            <p class="text-xs text-slate-500">
                                {{ $log->penerima_no_hp }}
                                @if ($log->pnpp?->nip) &middot; NIP {{ $log->pnpp->nip }} @endif
                                @if ($log->pnpp?->satker?->nama) &middot; {{ $log->pnpp->satker->nama }} @endif
                            </p>
                        </div>
                    </div>
                    <div class="text-left sm:text-right">
                        <p class="text-sm font-semibold text-slate-700">{{ $log->template?->judul ?? 'Pesan Tanpa Template' }}</p>
                        @if ($log->template?->kode)
                            <p class="text-[11px] text-slate-400">Kode: {{ $log->template->kode }}</p>
                        @endif
                    </div>
                </div>
                <div class="px-5 py-4">
                    <div class="mx-auto max-w-2xl">
                        <div class="rounded-2xl bg-[#dcf8c6] p-4 shadow-sm ring-1 ring-inset ring-emerald-200/50">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-emerald-800/70">RS Bhayangkara Bogor</p>
                                <span class="inline-flex items-center gap-1 text-xs {{ $statusText[$log->status] ?? 'text-slate-400' }}">
                                    @if ($log->status === 'terkirim')
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    @elseif (in_array($log->status, ['gagal', 'dibatalkan']))
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                    @else
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                    @endif
                                    {{ $statusLabel[$log->status] ?? $log->status }}
                                </span>
                            </div>
                            <p class="whitespace-pre-line text-sm leading-relaxed text-slate-800">{{ $log->konten }}</p>
                        </div>
                        <p class="mt-2 text-center text-[11px] text-slate-400">
                            Dikirim lewat {{ $log->provider ?? 'WhatsApp' }}
                            @if ($log->sent_at) &middot; diterima {{ $log->sent_at?->translatedFormat('d M Y, H:i') }} @endif
                        </p>
                    </div>
                    @if ($log->status === 'gagal' && $log->error)
                        <div class="mx-auto mt-4 max-w-2xl rounded-xl bg-rose-50 px-4 py-3 ring-1 ring-inset ring-rose-200">
                            <div class="flex items-start gap-2.5">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                                <div>
                                    <p class="text-xs font-bold text-rose-600">Pesan gagal terkirim</p>
                                    <p class="mt-0.5 text-sm text-rose-700">{{ $log->error }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Jadwal Terkait --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Jadwal Konsultasi</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Informasi jadwal kunjungan pasien yang terkait dengan pesan ini.</p>
                </div>
                @if ($log->reminder)
                    @php($r = $log->reminder)
                    <div class="px-5 py-4">
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                            <div class="rounded-xl bg-slate-50 px-3.5 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Hari &amp; Tanggal</p>
                                <p class="mt-1 text-sm font-bold text-slate-800">{{ $r->tanggal?->locale('id')->translatedFormat('l, d F Y') ?: '—' }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3.5 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Jam Periksa</p>
                                <p class="mt-1 text-sm font-bold text-slate-800">{{ $r->jam?->format('H:i') ?: '—' }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3.5 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Ruang Poli</p>
                                <p class="mt-1 text-sm font-bold text-slate-800">{{ $r->poli?->nama ?? '—' }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3.5 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Dokter</p>
                                <p class="mt-1 text-sm font-bold text-slate-800">{{ $r->dokter?->nama ?? '—' }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3.5 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Kunjungan ke Rumah</p>
                                <p class="mt-1 text-sm font-bold text-slate-800">{{ $r->home_visit ? 'Ya, dokter datang' : 'Tidak' }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3.5 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Kondisi Janji</p>
                                <p class="mt-1 text-sm font-bold text-slate-800">
                                    @if ($r->status === 'dijadwalkan')
                                        Dijadwalkan
                                    @elseif ($r->status === 'terkonfirmasi')
                                        Sudah Dikonfirmasi
                                    @elseif ($r->status === 'selesai')
                                        Selesai
                                    @elseif ($r->status === 'dibatalkan')
                                        Dibatalkan
                                    @else
                                        {{ ucfirst($r->status) }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        @if ($r->catatan)
                            <div class="mt-3 rounded-xl bg-amber-50/70 px-4 py-3 ring-1 ring-amber-100">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-amber-600">Catatan Tambahan</p>
                                <p class="mt-0.5 text-sm text-amber-800">{{ $r->catatan }}</p>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="flex items-start gap-3 px-5 py-6">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-slate-100">
                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Tidak ada jadwal terkait</p>
                            <p class="mt-0.5 text-xs text-slate-500">Pesan ini dikirim manual, bukan dari jadwal konsultasi tertentu.</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Balasan Pasien --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Balasan dari Pasien</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Pesan yang dikirim balik oleh pasien melalui WhatsApp.</p>
                </div>
                @if ($balasan->isEmpty())
                    <div class="flex items-start gap-3 px-5 py-6">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-slate-100">
                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 9.555 9.555 0 0 0 1.233-1.735A3.001 3.001 0 0 1 3 15.75V4.5A.75.75 0 0 1 3.75 3h16.5a.75.75 0 0 1 .75 1.5v11.25Z" /></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Belum ada balasan</p>
                            <p class="mt-0.5 text-xs text-slate-500">Pasien belum mengirim pesan balasan untuk pesan ini.</p>
                        </div>
                    </div>
                @else
                    <ul class="max-h-80 divide-y divide-slate-100 overflow-y-auto">
                        @foreach ($balasan as $b)
                            <li class="flex gap-3 px-5 py-3">
                                <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 9.555 9.555 0 0 0 1.233-1.735A3.001 3.001 0 0 1 3 15.75V4.5A.75.75 0 0 1 3.75 3h16.5a.75.75 0 0 1 .75 1.5v11.25Z" /></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-700">
                                        {{ $b->nama ?? $log->penerima_nama }}
                                        <span class="ml-1 font-normal text-slate-400">{{ $b->waktu_masuk?->translatedFormat('d M Y, H:i') }}</span>
                                    </p>
                                    <p class="mt-0.5 text-sm text-slate-600">{{ $b->isi_pesan }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    @can('manage respon')
                        <div class="border-t border-slate-100 px-5 py-3">
                            <a href="{{ route('admin.respon.index') }}" class="text-xs font-semibold text-sky-600 hover:text-sky-700">Lihat semua balasan di modul Respon &rarr;</a>
                        </div>
                    @endcan
                @endif
            </div>
        </div>

        {{-- ===== Kolom kanan: timeline + info ===== --}}
        <div class="space-y-6">
            {{-- Timeline --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Perjalanan Pesan</h3>
                </div>
                <ol class="relative space-y-6 px-5 py-5">
                    @foreach ($tahapan as $i => $tahap)
                        @php($selesai = $tahap[1])
                        <li class="relative pl-8">
                            @if ($i < count($tahapan) - 1)
                                <span class="absolute left-[11px] top-6 h-full w-0.5 {{ $selesai ? 'bg-emerald-300' : 'bg-slate-200' }}"></span>
                            @endif
                            <span class="absolute left-0 top-0.5 flex h-6 w-6 items-center justify-center rounded-full {{ $selesai ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-400' }}">
                                @if ($selesai)
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                @else
                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                @endif
                            </span>
                            <p class="text-sm font-semibold {{ $selesai ? 'text-slate-800' : 'text-slate-400' }}">{{ $tahap[0] }}</p>
                            @if ($tahap[2])
                                <p class="text-xs text-slate-400">{{ $tahap[2] }}</p>
                            @endif
                            <p class="mt-0.5 text-xs {{ $selesai ? 'text-slate-500' : 'text-slate-400' }}">{{ $tahap[3] }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>

            {{-- Info Pengiriman --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Ringkasan Kirim</h3>
                </div>
                <dl class="divide-y divide-slate-100 text-sm">
                    <div class="flex items-center justify-between gap-4 px-5 py-3">
                        <dt class="shrink-0 text-slate-500">Dibuat</dt>
                        <dd class="text-right font-medium text-slate-700">{{ $log->created_at?->translatedFormat('d M Y, H:i') ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-5 py-3">
                        <dt class="shrink-0 text-slate-500">Dijadwalkan</dt>
                        <dd class="text-right font-medium text-slate-700">{{ $log->kirim_pada?->translatedFormat('d M Y, H:i') ?? 'Tidak dijadwalkan' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-5 py-3">
                        <dt class="shrink-0 text-slate-500">Terkirim</dt>
                        <dd class="text-right font-medium text-slate-700">{{ $log->sent_at?->translatedFormat('d M Y, H:i') ?? 'Belum terkirim' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-5 py-3">
                        <dt class="shrink-0 text-slate-500">Layanan Kirim</dt>
                        <dd class="text-right font-medium text-slate-700">{{ $log->provider ?? '—' }}</dd>
                    </div>
                    @if ($log->provider_message_id)
                        <div class="flex items-center justify-between gap-4 px-5 py-3">
                            <dt class="shrink-0 text-slate-500">Nomor Referensi</dt>
                            <dd class="max-w-[180px] truncate text-right font-mono text-xs text-slate-500" title="{{ $log->provider_message_id }}">{{ $log->provider_message_id }}</dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between gap-4 px-5 py-3">
                        <dt class="shrink-0 text-slate-500">Dikirim oleh</dt>
                        <dd class="text-right font-medium text-slate-700">{{ $log->creator?->name ?? 'Otomatis oleh Sistem' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Detail Template WhatsApp --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Template WhatsApp</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Template resmi yang digunakan untuk mengirim pesan.</p>
                </div>
                <div class="space-y-4 px-5 py-4">
                    @if ($log->meta_template_name)
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Nama Template</p>
                            <p class="mt-1 rounded-lg bg-slate-50 px-3 py-2 font-mono text-xs text-slate-700">{{ $log->meta_template_name }}</p>
                        </div>
                    @endif
                    @if ($log->meta_language)
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Bahasa Template</p>
                            <p class="mt-1 rounded-lg bg-slate-50 px-3 py-2 font-mono text-xs text-slate-700">{{ $log->meta_language }}</p>
                        </div>
                    @endif
                    @if ($barisParam !== [])
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Data yang Diisi ke Template</p>
                            <p class="mt-1 text-xs text-slate-500">Bagian pesan yang otomatis diganti datanya.</p>
                            <div class="mt-2 overflow-hidden rounded-xl ring-1 ring-inset ring-slate-200">
                                <table class="min-w-full divide-y divide-slate-100 text-xs">
                                    <thead class="bg-slate-50/70">
                                        <tr>
                                            <th class="w-20 px-3 py-2 text-left font-semibold uppercase tracking-wide text-slate-400">Bagian</th>
                                            <th class="px-3 py-2 text-left font-semibold uppercase tracking-wide text-slate-400">Isi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach ($barisParam as $baris)
                                            <tr>
                                                <td class="px-3 py-2 font-mono font-semibold text-slate-500">{{ $baris['label'] }}</td>
                                                <td class="px-3 py-2 text-slate-700">{{ $baris['nilai'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                    @if (!$log->meta_template_name && $barisParam === [])
                        <p class="text-xs text-slate-400">Pesan ini tidak menggunakan template resmi WhatsApp.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
