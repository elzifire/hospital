@extends('layouts.app')

@section('title', 'Biaya Template Pesan')
@section('page-title', 'Pengaturan')

@section('content')
@php
    $rp = fn ($nilai) => 'Rp ' . number_format((int) $nilai, 0, ',', '.');

    $badgeMeta = [
        'MARKETING' => ['text' => 'bg-amber-50 text-amber-700 ring-amber-200/70', 'label' => 'MARKETING'],
        'UTILITY' => ['text' => 'bg-sky-50 text-sky-700 ring-sky-200/70', 'label' => 'UTILITY'],
        'SERVICE' => ['text' => 'bg-sky-50 text-sky-700 ring-sky-200/70', 'label' => 'SERVICE'],
        'AUTHENTICATION' => ['text' => 'bg-emerald-50 text-emerald-700 ring-emerald-200/70', 'label' => 'AUTHENTICATION'],
        'OTP' => ['text' => 'bg-emerald-50 text-emerald-700 ring-emerald-200/70', 'label' => 'OTP'],
        '' => ['text' => 'bg-slate-100 text-slate-500 ring-slate-200/70', 'label' => 'Tanpa Kategori'],
    ];
@endphp

<div class="space-y-6">
    {{-- ===== Breadcrumb ===== --}}
    <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
        <a href="{{ route('admin.dashboard') }}" class="rounded transition hover:text-sky-600">Dashboard</a>
        <svg class="h-3.5 w-3.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
        <a href="{{ route('admin.setting.index') }}" class="rounded transition hover:text-sky-600">Pengaturan</a>
        <svg class="h-3.5 w-3.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
        <span class="font-semibold text-slate-700">Biaya Template Pesan</span>
    </nav>

    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-black tracking-tight text-slate-900">Biaya Template Pesan</h2>
            <p class="mt-1 text-sm text-slate-500">
                Estimasi biaya yang dibayar per template — jumlah pesan terpakai dihitung dari database
                (pesan berstatus terkirim), harga satuan tipe template (marketing / utility / dll).
            </p>
        </div>
        <a href="{{ route('admin.setting.index') }}"
           class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-xs transition hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
            Kembali ke Template
        </a>
    </div>

    {{-- ===== Statistik ===== --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <div class="flex items-center gap-3.5 rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-200/80">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600 ring-1 ring-sky-100">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.269Z" /></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xl font-black text-slate-900 tabular-nums">{{ number_format($totalPesan, 0, ',', '.') }}</p>
                <p class="truncate text-[11px] font-bold uppercase tracking-wider text-slate-400">Pesan Terpakai</p>
            </div>
        </div>
        <div class="flex items-center gap-3.5 rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-200/80">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600 ring-1 ring-violet-100">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xl font-black text-slate-900 tabular-nums">{{ $templateTerpakai }} <span class="text-sm font-bold text-slate-400">/ {{ $totalTemplate }}</span></p>
                <p class="truncate text-[11px] font-bold uppercase tracking-wider text-slate-400">Template Terpakai</p>
            </div>
        </div>
        <div class="flex items-center gap-3.5 rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-200/80">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600 ring-1 ring-purple-100">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xl font-black text-slate-900 tabular-nums">{{ $perKategori->count() }}</p>
                <p class="truncate text-[11px] font-bold uppercase tracking-wider text-slate-400">Kategori Terpakai</p>
            </div>
        </div>
        <div class="flex items-center gap-3.5 rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-200/80">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xl font-black text-slate-900 tabular-nums">{{ $rp($totalBiaya) }}</p>
                <p class="truncate text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Biaya</p>
            </div>
        </div>
    </div>

    {{-- ===== Ringkasan per Tipe Template ===== --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-xs ring-1 ring-slate-200">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-bold text-slate-900">Ringkasan per Tipe Template</h3>
            <p class="mt-0.5 text-xs text-slate-500">Jumlah pesan terpakai per tipe template Meta (marketing, utility, dst.) dikali harga satuan.</p>
        </div>
        @forelse ($perKategori as $g)
            @php($badge = $badgeMeta[$g['kategori']] ?? $badgeMeta[''])
            <div class="flex flex-col gap-3 px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between border-t border-slate-50 first:border-t-0">
                <div class="flex items-center gap-3">
                    <span class="rounded-full px-3 py-1 text-[11px] font-extrabold tracking-wide ring-1 ring-inset {{ $badge['text'] }}">{{ $g['kategori'] }}</span>
                    <span class="text-xs text-slate-400">{{ number_format($g['terpakai'], 0, ',', '.') }} pesan terpakai · {{ $rp($g['harga']) }}/pesan</span>
                </div>
                <p class="text-sm font-black text-slate-900 tabular-nums">{{ $rp($g['subtotal']) }}</p>
            </div>
        @empty
            <div class="px-5 py-10 text-center text-sm text-slate-500">Belum ada pesan terkirim yang memakai template.</div>
        @endforelse
    </div>

    {{-- ===== Rincian per Template ===== --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-xs ring-1 ring-slate-200">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-bold text-slate-900">Rincian per Template</h3>
            <p class="mt-0.5 text-xs text-slate-500">Semua template pesan; kolom jumlah diambil dari database (hanya status terkirim).</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                    <tr>
                        <th class="px-5 py-3">No</th>
                        <th class="px-5 py-3">Template</th>
                        <th class="px-5 py-3">Tipe Meta</th>
                        <th class="px-5 py-3 text-right">Pesan Terpakai</th>
                        <th class="px-5 py-3 text-right">Harga Satuan</th>
                        <th class="px-5 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($baris as $i => $b)
                        @php($t = $b['template'])
                        @php($badge = $badgeMeta[(string) $t->meta_category] ?? $badgeMeta[''])
                        <tr class="{{ $b['terpakai'] === 0 ? 'opacity-60' : 'hover:bg-slate-50/60' }}">
                            <td class="px-5 py-3 text-xs font-semibold text-slate-400">{{ $i + 1 }}</td>
                            <td class="px-5 py-3">
                                <p class="font-semibold text-slate-800">{{ $t->judul }}</p>
                                <p class="text-xs text-slate-400">
                                    {{ $t->kode }} · {{ $t->category?->nama ?? 'Tanpa kategori aplikasi' }}
                                </p>
                            </td>
                            <td class="px-5 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold ring-1 ring-inset {{ $badge['text'] }}">{{ $badge['label'] }}</span>
                            </td>
                            <td class="px-5 py-3 text-right font-bold text-slate-800 tabular-nums">{{ number_format($b['terpakai'], 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-right text-slate-600 tabular-nums">{{ $rp($b['harga']) }}</td>
                            <td class="px-5 py-3 text-right font-black text-slate-900 tabular-nums">{{ $rp($b['subtotal']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada template pesan.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-50/70">
                    <tr>
                        <td colspan="3" class="px-5 py-3 text-xs font-bold uppercase tracking-wide text-slate-500">Total</td>
                        <td class="px-5 py-3 text-right font-black text-slate-900 tabular-nums">{{ number_format($totalPesan, 0, ',', '.') }}</td>
                        <td class="px-5 py-3"></td>
                        <td class="px-5 py-3 text-right font-black text-slate-900 tabular-nums">{{ $rp($totalBiaya) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- ===== Keterangan Tarif ===== --}}
   
</div>
@endsection