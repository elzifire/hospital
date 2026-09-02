@extends('layouts.app')

@section('title', 'Template Pesan')
@section('page-title', 'Template Pesan')

@section('content')
@php
    $highlight = fn (string $t) => preg_replace(
        '/\{([a-zA-Z_]+)\}/',
        '<span class="rounded-md bg-sky-100 px-1.5 py-0.5 font-mono text-[11px] font-bold text-sky-700">$0</span>',
        e($t)
    );

    $variables = [
        ['var' => '{nama}',       'desc' => 'Nama lengkap PNPP'],
        ['var' => '{nip}',        'desc' => 'NIP / NRP'],
        ['var' => '{satker}',     'desc' => 'Satuan kerja'],
        ['var' => '{poli}',       'desc' => 'Instalasi / poli'],
        ['var' => '{tanggal}',    'desc' => 'Tanggal kunjungan / kontrol'],
        ['var' => '{jam}',        'desc' => 'Jam kunjungan'],
        ['var' => '{obat}',       'desc' => 'Nama obat'],
        ['var' => '{dokter}',     'desc' => 'Nama dokter'],
    ];

    $templates = [
        [
            'judul'   => 'Pengingat Kontrol',
            'channel' => ['WhatsApp'],
            'teks'    => 'Halo {nama}, jadwal kontrol Anda di {poli} pada {tanggal} pukul {jam}. Mohon hadir tepat waktu. Terima kasih — RS Bhayangkara Bogor.',
            'dipakai' => '2 jam lalu',
        ],
        [
            'judul'   => 'Pengingat Minum Obat',
            'channel' => ['WhatsApp'],
            'teks'    => 'Halo {nama}, jangan lupa minum obat {obat} sesuai jadwal dari {dokter}. Semoga lekas sehat.',
            'dipakai' => 'Kemarin',
        ],
        [
            'judul'   => 'Hasil Pemeriksaan',
            'channel' => ['WhatsApp'],
            'teks'    => 'Kepada {nama}, hasil pemeriksaan Anda sudah tersedia. Silakan konsultasi ke {poli} dengan {dokter}.',
            'dipakai' => '3 hari lalu',
        ],
        [
            'judul'   => 'Follow Up Pasca Rawat',
            'channel' => ['WhatsApp'],
            'teks'    => 'Halo {nama}, kami dari RS Bhayangkara Bogor menindaklanjuti kondisi kesehatan Anda pasca kunjungan {tanggal}. Mohon balas untuk konfirmasi.',
            'dipakai' => '1 minggu lalu',
        ],
        [
            'judul'   => 'Pengingat Vaksinasi',
            'channel' => ['WhatsApp'],
            'teks'    => 'Pengingat: jadwal vaksinasi {nama} di {poli} pada {tanggal}. Jangan lupa membawa kartu identitas.',
            'dipakai' => '2 minggu lalu',
        ],
        [
            'judul'   => 'Sapaan Hari Besar',
            'channel' => ['WhatsApp'],
            'teks'    => 'Selamat hari raya, {nama}. Keluarga besar RS Bhayangkara Bogor mengucapkan selamat berbahagia.',
            'dipakai' => '1 bulan lalu',
        ],
    ];

    $channelStyle = [
        'WhatsApp' => 'bg-emerald-50 text-emerald-700 ring-emerald-200/70',
    ];
@endphp
<div class="space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.digital-reminder.index') }}" class="rounded transition hover:text-sky-600">Digital Reminder</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">Template Pesan</span>
        </nav>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-slate-900">Template Pesan</h2>
                <p class="mt-0.5 text-sm text-slate-500">Kelola template pesan yang dipakai untuk pengingat otomatis.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.digital-reminder.index') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                    Kembali
                </a>
                <a href="#" class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Tambah Template
                </a>
            </div>
        </div>
    </div>

    {{-- ===== Info Variabel ===== --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-start gap-3">
            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" /></svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-bold text-slate-900">Variabel yang tersedia</p>
                <p class="mt-0.5 text-xs text-slate-500">Gunakan variabel di bawah dalam teks pesan; nilai otomatis diisi dari data PNPP saat dikirim.</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($variables as $v)
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 px-2.5 py-1.5 ring-1 ring-slate-200" title="{{ $v['desc'] }}">
                            <code class="font-mono text-[11px] font-bold text-sky-700">{{ $v['var'] }}</code>
                            <span class="text-[11px] text-slate-400">{{ $v['desc'] }}</span>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Grid Template ===== --}}
    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($templates as $t)
            <div class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition-all hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 p-5">
                    <div class="min-w-0">
                        <h3 class="truncate text-base font-bold text-slate-900">{{ $t['judul'] }}</h3>
                        <p class="mt-0.5 text-xs text-slate-400">Terakhir dipakai {{ $t['dipakai'] }}</p>
                    </div>
                    <div class="flex flex-wrap justify-end gap-1.5">
                        @foreach ($t['channel'] as $ch)
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 ring-inset {{ $channelStyle[$ch] }}">{{ $ch }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="flex-1 p-5">
                    <p class="rounded-xl bg-slate-50 p-4 text-sm leading-relaxed text-slate-600 ring-1 ring-slate-100">
                        {!! $highlight($t['teks']) !!}
                    </p>
                </div>

                <div class="flex items-center justify-between gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-3 opacity-70 transition-opacity group-hover:opacity-100">
                    <button class="inline-flex items-center gap-1.5 rounded-lg bg-sky-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-sky-700">
                        Gunakan
                    </button>
                    <div class="flex items-center gap-1">
                        <button title="Edit" class="rounded-lg p-2 text-slate-400 transition-all hover:bg-sky-50 hover:text-sky-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                        </button>
                        <button title="Hapus" class="rounded-lg p-2 text-slate-400 transition-all hover:bg-rose-50 hover:text-rose-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
