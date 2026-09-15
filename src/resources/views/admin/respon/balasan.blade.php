@extends('layouts.app')

@section('title', 'Balasan WhatsApp')
@section('page-title', 'Respon Pasien')

@section('content')
@php
    $stats = [
        ['label' => 'Total Balasan',    'value' => number_format($total),       'tone' => 'sky',     'icon' => 'M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-2.029 2.115 2.115 0 0 0-1.661-.586 48.744 48.744 0 0 0-8.983 0 2.115 2.115 0 0 0-1.661.586 2.126 2.126 0 0 0-.476 2.029c.172.714.308 1.44.41 2.174m3.923-2.174a41.03 41.03 0 0 0-.41 2.174c-.058.35-.088.706-.088 1.066v4.286c0 .36.03.716.088 1.066'],
        ['label' => 'Belum Dibaca',     'value' => number_format($belumDibaca), 'tone' => 'rose',   'icon' => 'M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'],
        ['label' => 'Balasan Hari Ini', 'value' => number_format($hariIni),     'tone' => 'emerald', 'icon' => 'M4.5 12.75l6 6 9-13.5'],
        ['label' => 'Pasien Terdaftar', 'value' => number_format($pasienUnik),  'tone' => 'violet',  'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
        ['label' => 'Nomor Tak Dikenal','value' => number_format($takTerdaftar), 'tone' => 'amber',   'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
    ];

    $toneColor = [
        'sky'     => ['bg' => 'bg-sky-50',     'text' => 'text-sky-600'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
        'violet'  => ['bg' => 'bg-violet-50',  'text' => 'text-violet-600'],
        'rose'    => ['bg' => 'bg-rose-50',    'text' => 'text-rose-600'],
        'amber'   => ['bg' => 'bg-amber-50',   'text' => 'text-amber-600'],
    ];

    $avatarColors = [
        'bg-emerald-100 text-emerald-700',
        'bg-sky-100 text-sky-700',
        'bg-violet-100 text-violet-700',
        'bg-amber-100 text-amber-700',
        'bg-rose-100 text-rose-700',
        'bg-teal-100 text-teal-700',
        'bg-indigo-100 text-indigo-700',
        'bg-orange-100 text-orange-700',
    ];
@endphp

<div class="space-y-6">
    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Balasan WhatsApp</h2>
            <p class="mt-0.5 text-sm text-slate-500">Pilih nomor / target penerima dulu, lalu lihat & balas percakapannya.</p>
        </div>
        <a href="{{ route('admin.respon.manual') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Catat Manual
        </a>
    </div>

    @include('admin.respon._flash')

    @include('admin.respon._subnav', ['active' => 'balasan'])

    {{-- ===== Kartu Statistik ===== --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        @foreach ($stats as $s)
            @php
                $c = $toneColor[$s['tone']];
            @endphp
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

    {{-- ===== Chat list ala WhatsApp ===== --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        {{-- Header chat list --}}
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-[#008069] px-5 py-4">
            <div class="flex items-center gap-2.5">
                <svg class="h-5 w-5 text-white/90" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347Z"/><path d="M12 1.93a10.07 10.07 0 0 1 8.57 4.93 10 10 0 0 1-2.43 13.13l2.34 1.9a.5.5 0 0 1-.31.9H12A10.07 10.07 0 1 1 12 1.93Zm0 1.9A8.17 8.17 0 1 0 12 20.13h7.68l-1.67-1.35-.6-.49.4-.67A8.17 8.17 0 0 0 12 3.83Z"/></svg>
                <h3 class="text-sm font-bold text-white">Daftar Percakapan</h3>
            </div>
            <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white">{{ number_format($total) }} pesan</span>
        </div>

        {{-- Pencarian --}}
        <form method="GET" action="{{ route('admin.respon.index') }}"
              class="flex flex-wrap items-end gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4">
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Cari kontak / pesan</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Nama / nomor / isi pesan…"
                       class="w-64 rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">Cari</button>
                @if (filled($filters['q']))
                    <a href="{{ route('admin.respon.index') }}" class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-300">Reset</a>
                @endif
            </div>
        </form>

        @if ($konversasi->isEmpty())
            <div class="px-5 py-14 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-2.029 2.115 2.115 0 0 0-1.661-.586 48.744 48.744 0 0 0-8.983 0 2.115 2.115 0 0 0-1.661.586 2.126 2.126 0 0 0-.476 2.029c.172.714.308 1.44.41 2.174m3.923-2.174a41.03 41.03 0 0 0-.41 2.174c-.058.35-.088.706-.088 1.066v4.286c0 .36.03.716.088 1.066" /></svg>
                <p class="mt-2 text-sm font-medium text-slate-500">Belum ada percakapan.</p>
            </div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($konversasi as $c)
                    @php
                        $namaTampil = $c->pnpp?->nama ?? $c->nama_pengirim ?? null;
                        $inisial = mb_substr($namaTampil ?? $c->no_hp, 0, 1, 'UTF-8');
                        $adaBelum = $c->belum_dibaca > 0;
                        $warna = $avatarColors[crc32($c->no_hp) % count($avatarColors)];
                    @endphp
                    <li>
                        <a href="{{ route('admin.respon.show', $c->no_hp) }}"
                           class="flex items-center gap-3.5 px-5 py-3.5 transition {{ $adaBelum ? 'bg-emerald-50/60 hover:bg-emerald-50' : 'hover:bg-slate-50' }}">
                            {{-- Avatar (inisial, warna tetap per kontak) --}}
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full {{ $adaBelum ? $warna . ' font-bold' : 'bg-slate-200 text-slate-500 font-semibold' }}">
                                <span class="text-sm">{{ strtoupper($inisial) }}</span>
                            </div>

                            {{-- Nama + pratinjau --}}
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="{{ $adaBelum ? 'font-extrabold text-slate-900' : 'font-semibold text-slate-700' }} truncate text-sm">
                                        {{ $namaTampil ?? 'Nomor Tak Dikenal' }}
                                    </p>
                                    @if (! $c->pnpp)
                                        <span class="inline-flex flex-shrink-0 rounded-full bg-rose-50 px-1.5 py-0.5 text-[10px] font-semibold text-rose-500 ring-1 ring-rose-100">tak terdaftar</span>
                                    @endif
                                </div>
                                <div class="mt-0.5 flex items-center gap-1.5">
                                    <p class="truncate text-xs {{ $adaBelum ? 'font-medium text-slate-600' : 'text-slate-400' }}">
                                        {{ $c->isi_terakhir ?? '—' }}
                                    </p>
                                </div>
                                <p class="mt-0.5 font-mono text-[11px] text-slate-400">{{ $c->no_hp }}</p>
                            </div>

                            {{-- Waktu + badge belum dibaca --}}
                            <div class="flex flex-col items-end gap-1.5">
                                @if ($c->waktu_terakhir)
                                    <p class="whitespace-nowrap text-[11px] {{ $adaBelum ? 'font-bold text-slate-700' : 'text-slate-400' }}">
                                        @if (optional($c->waktu_terakhir)->isToday())
                                            {{ optional($c->waktu_terakhir)->format('H:i') }}
                                        @elseif (optional($c->waktu_terakhir)->isYesterday())
                                            Kemarin
                                        @else
                                            {{ optional($c->waktu_terakhir)->format('d/m') }}
                                        @endif
                                    </p>
                                @endif
                                @if ($adaBelum)
                                    <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-emerald-500 px-1.5 text-[10px] font-bold text-white">
                                        {{ $c->belum_dibaca > 99 ? '99+' : number_format($c->belum_dibaca) }}
                                    </span>
                                @endif
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="border-t border-slate-100 px-5 py-3">
                {{ $konversasi->links() }}
            </div>
        @endif
    </div>
</div>
@endsection