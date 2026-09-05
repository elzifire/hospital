@extends('layouts.app')

@section('title', 'Percakapan Respon')
@section('page-title', 'Percakapan Respon')

@section('content')
@php
    $pasien = [
        'nama'  => 'Budi Santoso',
        'nip'   => '198501012010011001',
        'satker' => 'Dinas Kesehatan',
        'status' => 'Belum Ditindak',
    ];

    // Demo percakapan (read-only). Data riil dikumpulkan dari webhook WhatsApp.
    $pesan = [
        ['arah' => 'keluar', 'teks' => 'Halo Budi, Anda memiliki jadwal kontrol di Poli Umum pada 12/09/2026 pukul 09:00. Apakah Anda akan hadir?', 'waktu' => '07:30', 'dibaca' => true],
        ['arah' => 'masuk',  'teks' => 'Baik, saya cek jadwal dulu ya.',   'waktu' => '07:31', 'dibaca' => null],
        ['arah' => 'masuk',  'teks' => 'Ya, saya akan datang.',            'waktu' => '07:32', 'dibaca' => null],
        ['arah' => 'keluar', 'teks' => 'Terima kasih atas konfirmasinya. Sampai jumpa di RS Bhayangkara Bogor.', 'waktu' => '07:33', 'dibaca' => true],
        ['arah' => 'masuk',  'teks' => 'Siap, terima kasih.',              'waktu' => '07:34', 'dibaca' => null],
    ];
@endphp
<div class="mx-auto max-w-6xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.respon.index') }}" class="rounded transition hover:text-sky-600">Respon</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">{{ $nomor }}</span>
        </nav>
        <h2 class="text-xl font-bold tracking-tight text-slate-900">Percakapan WhatsApp</h2>
        <p class="mt-0.5 text-sm text-slate-500">Riwayat balasan pasien per nomor telepon, terkumpul otomatis via webhook.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ===== Thread Percakapan ===== --}}
        <div class="lg:col-span-2">
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">

                {{-- Chat header --}}
                <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 bg-emerald-600 px-5 py-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/20 text-sm font-bold uppercase text-white">
                        {{ substr($pasien['nama'], 0, 1) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-white">{{ $pasien['nama'] }}</p>
                        <p class="truncate font-mono text-xs text-emerald-100">{{ $nomor }}</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-bold text-white">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                        Online
                    </span>
                </div>

                {{-- Chat body --}}
                <div class="max-h-[520px] space-y-3 overflow-y-auto bg-[#e5ddd5] p-5">
                    <p class="mb-2 text-center">
                        <span class="rounded-full bg-[#dcf8c6]/60 px-3 py-1 text-[11px] font-semibold text-slate-500">Hari ini</span>
                    </p>
                    @foreach ($pesan as $m)
                        @if ($m['arah'] === 'keluar')
                            <div class="ml-auto max-w-[80%] rounded-xl rounded-tr-sm bg-[#dcf8c6] p-3 shadow-sm">
                                <p class="text-sm text-slate-800">{{ $m['teks'] }}</p>
                                <p class="mt-1 text-right text-[10px] text-slate-400">
                                    {{ $m['waktu'] }}
                                    @if ($m['dibaca'])
                                        <span class="text-sky-500">✓✓</span>
                                    @endif
                                </p>
                            </div>
                        @else
                            <div class="max-w-[80%] rounded-xl rounded-tl-sm bg-white p-3 shadow-sm">
                                <p class="text-sm text-slate-800">{{ $m['teks'] }}</p>
                                <p class="mt-1 text-right text-[10px] text-slate-400">{{ $m['waktu'] }}</p>
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- Catatan webhook --}}
                <div class="flex items-center gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-3">
                    <svg class="h-4 w-4 flex-shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.952l-.707.707a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m9.86 2.714.041-.02a.75.75 0 0 1 1.063.952l-.707.707a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757" /></svg>
                    <p class="text-xs text-slate-400">Percakapan bersifat read-only — balasan baru masuk otomatis melalui webhook WhatsApp.</p>
                </div>
            </div>
        </div>

        {{-- ===== Sidebar: Detail Pasien ===== --}}
        <div class="space-y-6 lg:col-span-1">
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-bold text-slate-900">Detail Pasien</h3>
                </div>
                <ul class="divide-y divide-slate-50">
                    <li class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-sm font-medium text-slate-500">Nama</span>
                        <span class="text-sm font-bold text-slate-800">{{ $pasien['nama'] }}</span>
                    </li>
                    <li class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-sm font-medium text-slate-500">NIP</span>
                        <span class="font-mono text-xs font-semibold text-slate-700">{{ $pasien['nip'] }}</span>
                    </li>
                    <li class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-sm font-medium text-slate-500">Satker</span>
                        <span class="max-w-[55%] truncate text-sm font-bold text-slate-800">{{ $pasien['satker'] }}</span>
                    </li>
                    <li class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-sm font-medium text-slate-500">Status</span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700 ring-1 ring-inset ring-amber-200/70">{{ $pasien['status'] }}</span>
                    </li>
                </ul>
            </div>

            {{-- Aksi --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-bold text-slate-900">Aksi</h3>
                </div>
                <div class="space-y-2.5 p-5">
                    <a href="{{ route('admin.follow-up.create') }}" class="flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" /></svg>
                        Buat Follow Up
                    </a>
                    <a href="{{ route('admin.respon.index') }}" class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                        Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
