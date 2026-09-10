@extends('layouts.app')

@section('title', 'Percakapan')
@section('page-title', 'Percakapan Pasien')

@section('content')
<div class="space-y-5">
    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.respon.index') }}"
               class="rounded-lg bg-slate-100 p-2.5 text-slate-500 transition hover:bg-slate-200 hover:text-slate-700" title="Kembali">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">{{ $pnpp?->nama ?? 'Nomor Tak Dikenal' }}</h1>
                <p class="font-mono text-sm text-slate-500">{{ $noHp }}</p>
            </div>
        </div>
    </div>

    {{-- ===== Garis waktu percakapan ===== --}}
    <div class="rounded-2xl border border-slate-200 bg-[#efeae2] p-4 shadow-sm sm:p-6">
        @if ($timeline->isEmpty())
            <p class="py-12 text-center text-sm text-slate-400">Belum ada pesan tersimpan untuk nomor ini.</p>
        @else
            <div class="space-y-3">
                @foreach ($timeline as $t)
                    @if ($t['arah'] === 'keluar')
                        {{-- Pesan keluar (broadcast RS) --}}
                        <div class="flex justify-end" title="Status: {{ $t['status'] }} · {{ $t['jenis'] }}">
                            <div class="max-w-[80%] rounded-xl rounded-tr-sm bg-[#dcf8c6] px-4 py-2.5 shadow-sm">
                                <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-800">{{ $t['isi'] }}</p>
                                <p class="mt-1 text-right text-[10px] text-slate-400">
                                    {{ $t['waktu']->format('d M H:i') }} · {{ ucfirst($t['status']) }}
                                </p>
                            </div>
                        </div>
                    @else
                        {{-- Balasan pasien --}}
                        <div class="flex justify-start">
                            <div class="max-w-[80%] rounded-xl rounded-tl-sm bg-white px-4 py-2.5 shadow-sm">
                                <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-800">{{ $t['isi'] }}</p>
                                <p class="mt-1 text-left text-[10px] text-slate-400">{{ $t['waktu']->format('d M H:i') }}</p>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    @if ($pnpp)
        <p class="text-xs text-slate-400">
            Pasien terdaftar: <strong class="text-slate-600">{{ $pnpp->nama }}</strong>
            @if ($pnpp->nip) · NIP {{ $pnpp->nip }} @endif
            — pesan hijau = keluar dari RS (broadcast), putih = balasan pasien.
        </p>
    @endif
</div>
@endsection
