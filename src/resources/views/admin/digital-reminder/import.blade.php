@extends('layouts.app')

@section('title', 'Import Reminder')
@section('page-title', 'Import Reminder')

@section('content')
@php
    $headers = ['Nama', 'No. HP', 'Pesan', 'Jadwal Kirim'];
    $rows = [
        ['Budi Santoso', '081234567890', 'Apakah Anda akan datang untuk kontrol besok?', '2026-09-03 07:30'],
        ['Siti Aminah',  '081298765432', 'Jadwal kontrol asma Anda besok pukul 09:00.',   '2026-09-03 08:00'],
        ['Agus Wijaya',  '081377445566', 'Hasil pemeriksaan jantung sudah tersedia.',     '2026-09-03 09:15'],
    ];
@endphp
<div class="mx-auto max-w-5xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.digital-reminder.index') }}" class="rounded transition hover:text-sky-600">Digital Reminder</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">Import</span>
        </nav>
        <h2 class="text-xl font-bold tracking-tight text-slate-900">Import Reminder</h2>
        <p class="mt-0.5 text-sm text-slate-500">Unggah file Excel/CSV untuk membuat banyak reminder sekaligus.</p>
    </div>

    {{-- ===== Upload Card ===== --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 p-6">
            <h3 class="text-base font-bold text-slate-900">Upload File</h3>
            <p class="mt-0.5 text-sm text-slate-500">Mendukung file Excel (.xlsx/.xls) dan CSV dengan header sesuai template.</p>
        </div>

        <div class="p-6">
            <div class="space-y-5">
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">File (.xlsx / .xls / .csv) <span class="text-rose-500">*</span></label>
                    <input type="file" accept=".xlsx,.xls,.csv,text/csv"
                           class="block w-full cursor-pointer rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition">
                </div>

                <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-400">Kolom yang diharapkan</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($headers as $h)
                            <code class="rounded-md bg-white px-2 py-1 font-mono text-[11px] text-slate-600 ring-1 ring-slate-200">{{ $h }}</code>
                        @endforeach
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <a href="#" class="inline-flex items-center gap-1.5 text-xs font-bold text-sky-600 hover:underline">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                            Template Excel
                        </a>
                        <a href="#" class="inline-flex items-center gap-1.5 text-xs font-bold text-sky-600 hover:underline">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                            Template CSV
                        </a>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('admin.digital-reminder.index') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Kembali</a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                    Pratinjau
                </button>
            </div>
        </div>
    </div>

    {{-- ===== Preview (contoh) ===== --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h3 class="text-base font-bold text-slate-900">Pratinjau Data</h3>
            <div class="flex flex-wrap gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 ring-1 ring-slate-200">{{ count($rows) }} baris</span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200/70">3 valid</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                        <th class="px-4 py-3 font-semibold" style="width:44px">#</th>
                        @foreach ($headers as $h)
                            <th class="px-4 py-3 font-semibold">{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($rows as $i => $r)
                        <tr class="bg-white">
                            <td class="px-4 py-2 font-mono text-xs text-slate-400">{{ $i + 2 }}</td>
                            @foreach ($r as $cell)
                                <td class="px-4 py-2 text-xs text-slate-700">{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 bg-slate-50/60 px-6 py-3 text-xs text-slate-400">
            Data akan diproses di background (queue Redis) setelah konfirmasi.
        </div>
    </div>
</div>
@endsection
