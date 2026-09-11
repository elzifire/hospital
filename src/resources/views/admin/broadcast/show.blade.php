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

    $ruleLabel = [
        'h-7' => 'H-7', 'h-1' => 'H-1', 'h' => 'Hari-H',
        'tidak_datang' => 'Tidak Datang', 'manual' => 'Manual',
    ];

    $jenisLabel  = ['outreach' => 'Outreach', 'follow_up' => 'Follow Up'];
    $statusLabel = \App\Models\MessageLog::LABEL_STATUS;

    $indeksRiwayat = $log->jenis === 'outreach' ? 'admin.outreach.index' : 'admin.follow-up.index';

    // Baris parameter template (label param dibentuk di PHP agar tidak
    // bentrok dengan sintaks Blade).
    $barisParam = [];
    foreach ((array) $log->template_params as $i => $nilai) {
        $barisParam[] = ['label' => '{'.($i + 1).'}', 'nilai' => (string) $nilai];
    }

    // Tahapan timeline status: [label, selesai?, waktu, catatan]
    $final = in_array($log->status, ['terkirim', 'gagal', 'dibatalkan']);
    $tahapan = [
        ['Pesan dibuat', true, $log->created_at?->translatedFormat('d M Y, H:i'), 'Dari '.($ruleLabel[$log->rule] ?? $log->rule).($log->creator ? ' · oleh '.$log->creator->name : '')],
        ['Dijadwalkan', (bool) $log->kirim_pada, $log->kirim_pada?->translatedFormat('d M Y, H:i'), $log->kirim_pada ? 'Menunggu waktu pengiriman tiba' : 'Menunggu giliran untuk dikirim'],
        ['Sedang dikirim', in_array($log->status, ['mengirim', 'terkirim']), null, 'Sistem sedang mengirim pesan ke WhatsApp'],
        [$log->status === 'gagal' ? 'Gagal terkirim' : 'Terkirim', $final, $log->sent_at?->translatedFormat('d M Y, H:i'), $log->status === 'gagal' ? ($log->error ?? 'Gagal dikirim') : ($log->status === 'dibatalkan' ? 'Dibatalkan sebelum terkirim' : 'Pesan diterima WhatsApp')],
    ];
@endphp

<div class="space-y-6">
    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-xl font-bold tracking-tight text-slate-900">Detail Pesan</h2>
                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $statusStyle[$log->status] ?? '' }}">{{ $statusLabel[$log->status] ?? $log->status }}</span>
                <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-200/70">{{ $ruleLabel[$log->rule] ?? $log->rule }}</span>
                <span class="rounded-full bg-sky-50 px-2.5 py-0.5 text-[11px] font-semibold text-sky-700 ring-1 ring-inset ring-sky-200/70">{{ $jenisLabel[$log->jenis] ?? $log->jenis }}</span>
            </div>
            <p class="mt-1 font-mono text-xs text-slate-400">#LOG-{{ str_pad((string) $log->id, 6, '0', STR_PAD_LEFT) }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($log->status === 'gagal')
                <button type="button"
                        onclick="confirmSubmit('{{ route('admin.broadcast.kirim-ulang', $log->id) }}', {
                            title: 'Kirim ulang pesan?',
                            html: 'Pesan ke <strong>{{ $log->penerima_nama }}</strong> akan dikirim sekarang.',
                            confirmText: 'Ya, kirim ulang',
                            method: 'POST'
                        })"
                        class="inline-flex items-center gap-2 rounded-xl bg-amber-50 px-4 py-2.5 text-sm font-bold text-amber-700 ring-1 ring-inset ring-amber-200 transition hover:bg-amber-100">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                    Kirim Ulang
                </button>
            @endif
            @if ($log->status === 'menunggu')
                <button type="button"
                        onclick="confirmSubmit('{{ route('admin.broadcast.batalkan', $log->id) }}', {
                            title: 'Batalkan pesan?',
                            html: 'Pesan ke <strong>{{ $log->penerima_nama }}</strong> tidak akan dikirim.',
                            confirmText: 'Ya, batalkan'
                        })"
                        class="inline-flex items-center gap-2 rounded-xl bg-rose-50 px-4 py-2.5 text-sm font-bold text-rose-600 ring-1 ring-inset ring-rose-200 transition hover:bg-rose-100">
                    Batalkan
                </button>
            @endif
            <a href="{{ route($indeksRiwayat) }}"
               class="inline-flex items-center gap-2 rounded-xl bg-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-300">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                Kembali ke Riwayat
            </a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- ===== Kolom kiri: isi pesan, penjadwalan, balasan ===== --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Isi pesan --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Isi Pesan</h3>
                    <p class="mt-0.5 text-xs text-slate-500">{{ $log->template?->judul ?? '—' }}{{ $log->template?->kode ? ' · '.$log->template->kode : '' }}</p>
                </div>
                <div class="px-5 py-4">
                    <div class="rounded-2xl bg-[#dcf8c6] p-4 ring-1 ring-inset ring-emerald-200/60">
                        <p class="whitespace-pre-line text-sm leading-relaxed text-slate-800">{{ $log->konten }}</p>
                    </div>
                    @if ($log->status === 'gagal' && $log->error)
                        <div class="mt-4 rounded-xl bg-rose-50 px-4 py-3 ring-1 ring-inset ring-rose-200">
                            <p class="text-xs font-semibold uppercase tracking-wide text-rose-500">Pesan gagal terkirim</p>
                            <p class="mt-1 text-sm text-rose-700">{{ $log->error }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Penjadwalan terkait --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Penjadwalan Terkait</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Relasi pesan ke data Digital Reminder.</p>
                </div>
                @if ($log->reminder)
                    @php($r = $log->reminder)
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 px-5 py-4 text-sm sm:grid-cols-3">
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tanggal</dt>
                            <dd class="mt-0.5 font-semibold text-slate-800">{{ $r->tanggal?->locale('id')->translatedFormat('l, d F Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Jam</dt>
                            <dd class="mt-0.5 font-semibold text-slate-800">{{ $r->jam?->format('H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Poli</dt>
                            <dd class="mt-0.5 text-slate-700">{{ $r->poli?->nama ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Dokter</dt>
                            <dd class="mt-0.5 text-slate-700">{{ $r->dokter?->nama ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Home Visit</dt>
                            <dd class="mt-0.5 text-slate-700">{{ $r->home_visit ? 'Ya' : 'Tidak' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Status Jadwal</dt>
                            <dd class="mt-0.5 text-slate-700">{{ ucfirst($r->status) }}</dd>
                        </div>
                        @if ($r->catatan)
                            <div class="col-span-2 sm:col-span-3">
                                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Catatan</dt>
                                <dd class="mt-0.5 text-sm text-slate-600">{{ $r->catatan }}</dd>
                            </div>
                        @endif
                    </dl>
                @else
                    <p class="px-5 py-6 text-sm text-slate-400">
                        Pesan manual tanpa referensi jadwal — dibuat dari form kirim manual.
                    </p>
                @endif
            </div>

            {{-- Balasan pasien --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Balasan Pasien</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Balasan WhatsApp terakhir dari nomor penerima.</p>
                </div>
                @if ($balasan->isEmpty())
                    <p class="px-5 py-6 text-sm text-slate-400">Belum ada balasan dari pasien.</p>
                @else
                    <ul class="divide-y divide-slate-100">
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
                            <a href="{{ route('admin.respon.index') }}" class="text-xs font-semibold text-sky-600 hover:text-sky-700">Lihat semua balasan di modul Respon →</a>
                        </div>
                    @endcan
                @endif
            </div>
        </div>

        {{-- ===== Kolom kanan: timeline + info ===== --}}
        <div class="space-y-6">
            {{-- Timeline status --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Status Pengiriman</h3>
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

            {{-- Pemetaan template Meta --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Pemetaan Template Meta</h3>
                    <p class="mt-0.5 text-xs text-slate-500">WhatsApp official — parameter posisi template.</p>
                </div>
                <div class="space-y-3 px-5 py-4 text-sm">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Template Meta</p>
                        <p class="mt-0.5 font-mono text-xs text-slate-700">{{ $log->meta_template_name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Bahasa</p>
                        <p class="mt-0.5 font-mono text-xs text-slate-700">{{ $log->meta_language ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Parameter terkirim</p>
                        @if ($barisParam !== [])
                            <div class="overflow-hidden rounded-xl ring-1 ring-inset ring-slate-200">
                                <table class="min-w-full divide-y divide-slate-100 text-xs">
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach ($barisParam as $baris)
                                            <tr>
                                                <td class="w-14 px-3 py-1.5 font-mono font-semibold text-slate-500">{{ '{'.$baris['label'].'}' }}</td>
                                                <td class="px-3 py-1.5 text-slate-700">{{ $baris['nilai'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-xs text-slate-400">Tidak ada parameter (template statis).</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Info pengiriman --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Info Pengiriman</h3>
                </div>
                <dl class="divide-y divide-slate-100 text-sm">
                    <div class="flex items-center justify-between px-5 py-3">
                        <dt class="text-slate-400">Dibuat</dt>
                        <dd class="font-medium text-slate-700">{{ $log->created_at?->translatedFormat('d M Y, H:i') }}</dd>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3">
                        <dt class="text-slate-400">Dijadwalkan</dt>
                        <dd class="font-medium text-slate-700">{{ $log->kirim_pada?->translatedFormat('d M Y, H:i') ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3">
                        <dt class="text-slate-400">Terkirim</dt>
                        <dd class="font-medium text-slate-700">{{ $log->sent_at?->translatedFormat('d M Y, H:i') ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3">
                        <dt class="text-slate-400">Provider</dt>
                        <dd class="font-medium text-slate-700">{{ $log->provider ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3">
                        <dt class="text-slate-400">ID Pesan WhatsApp</dt>
                        <dd class="max-w-[180px] truncate font-mono text-xs text-slate-700" title="{{ $log->provider_message_id }}">{{ $log->provider_message_id ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3">
                        <dt class="text-slate-400">Dibuat oleh</dt>
                        <dd class="font-medium text-slate-700">{{ $log->creator?->name ?? 'Sistem' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Penerima --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Penerima</h3>
                </div>
                <div class="space-y-2 px-5 py-4">
                    <p class="text-sm font-bold text-slate-800">{{ $log->penerima_nama }}</p>
                    <p class="text-xs text-slate-500">NIP/NRP {{ $log->pnpp?->nip ?? '—' }}</p>
                    <p class="text-xs text-slate-500">{{ $log->pnpp?->satker?->nama ?? '—' }}</p>
                    <p class="font-mono text-xs text-slate-600">{{ $log->penerima_no_hp }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
