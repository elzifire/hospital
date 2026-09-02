@extends('layouts.app')

@section('title', 'Tambah Follow Up')
@section('page-title', 'Tambah Follow Up')

@section('content')
@php
    $pasiens = ['Budi Santoso', 'Siti Aminah', 'Agus Wijaya', 'Dewi Lestari', 'Rudi Hartono', 'Lina Marlina'];
    $satkers = ['Dinas Kesehatan', 'BPJS Kesehatan', 'Kementerian Kesehatan', 'Dinas Pendidikan'];
@endphp
<div x-data="{ mode: 'individu', cariP: '', cariS: '', pilih: [], pilihSatker: [] }" class="mx-auto max-w-6xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.follow-up.index') }}" class="rounded transition hover:text-sky-600">Follow Up</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">Tambah Baru</span>
        </nav>
        <h2 class="text-xl font-bold tracking-tight text-slate-900">Tambah Follow Up</h2>
        <p class="mt-0.5 text-sm text-slate-500">Kirim pengingat H-1 kepada PNPP via WhatsApp.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ===== Form ===== --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- Kirim Ke --}}
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 p-6">
                    <h3 class="text-base font-bold text-slate-900">Kirim Ke</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Pilih penerima per individu atau per satker.</p>
                </div>

                <div class="space-y-5 p-6">
                    {{-- Toggle --}}
                    <div class="grid grid-cols-2 gap-2 rounded-xl bg-slate-100 p-1">
                        <button type="button" @click="mode = 'individu'"
                                class="rounded-lg py-2 text-sm font-semibold transition"
                                :class="mode === 'individu' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                            Per Individu
                        </button>
                        <button type="button" @click="mode = 'satker'"
                                class="rounded-lg py-2 text-sm font-semibold transition"
                                :class="mode === 'satker' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                            Per Satker
                        </button>
                    </div>

                    {{-- Per Individu --}}
                    <div x-show="mode === 'individu'" x-cloak>
                        <input type="text" x-model="cariP" placeholder="Cari nama / NIP pasien..."
                               class="mb-3 block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition">
                        <div class="max-h-56 space-y-1 overflow-y-auto rounded-xl border border-slate-100 p-2">
                            @foreach ($pasiens as $p)
                                <label class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 transition hover:bg-sky-50"
                                       x-show="cariP === '' || '{{ $p }}'.toLowerCase().includes(cariP.toLowerCase())">
                                    <input type="checkbox" value="{{ $p }}" x-model="pilih" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold uppercase text-emerald-700">{{ substr($p, 0, 1) }}</span>
                                    <span class="text-sm font-semibold text-slate-700">{{ $p }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-slate-400" x-text="pilih.length + ' pasien dipilih'"></p>
                    </div>

                    {{-- Per Satker --}}
                    <div x-show="mode === 'satker'" x-cloak>
                        <input type="text" x-model="cariS" placeholder="Cari satker..."
                               class="mb-3 block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition">
                        <div class="max-h-56 space-y-1 overflow-y-auto rounded-xl border border-slate-100 p-2">
                            @foreach ($satkers as $s)
                                <label class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 transition hover:bg-sky-50"
                                       x-show="cariS === '' || '{{ $s }}'.toLowerCase().includes(cariS.toLowerCase())">
                                    <input type="checkbox" value="{{ $s }}" x-model="pilihSatker" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18" /></svg>
                                    <span class="text-sm font-semibold text-slate-700">{{ $s }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-slate-400" x-text="pilihSatker.length + ' satker dipilih'"></p>
                    </div>
                </div>
            </div>

            {{-- Pesan --}}
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 p-6">
                    <h3 class="text-base font-bold text-slate-900">Pesan & Jadwal</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Pesan akan dikirim otomatis H-1 sebelum jadwal.</p>
                </div>

                <div class="space-y-5 p-6">
                    <div class="flex items-center gap-2 rounded-xl bg-emerald-50/70 p-3 ring-1 ring-emerald-100">
                        <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" /></svg>
                        <span class="text-xs font-semibold text-emerald-700">Dikirim melalui <strong>WhatsApp</strong> (pengingat H-1).</span>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Isi Pesan <span class="text-rose-500">*</span></label>
                        <textarea rows="3" class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition">Halo {nama}, besok Anda memiliki jadwal kunjungan di {poli}. Mohon hadir tepat waktu.</textarea>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Prioritas</label>
                            <select class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition cursor-pointer">
                                <option>Tinggi</option>
                                <option>Sedang</option>
                                <option>Rendah</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Petugas</label>
                            <select class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition cursor-pointer">
                                <option>Ayu Lestari</option>
                                <option>Bimo Saputra</option>
                                <option>Citra Dewi</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action bar --}}
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.follow-up.index') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Batal</a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    Simpan Follow Up
                </button>
            </div>
        </div>

        {{-- ===== Pratinjau WhatsApp ===== --}}
        <div class="lg:col-span-1">
            <div class="sticky top-20 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 bg-emerald-600 px-5 py-4">
                    <p class="text-sm font-bold text-white">Pratinjau WhatsApp</p>
                </div>
                <div class="space-y-3 bg-[#e5ddd5] p-5">
                    <div class="ml-auto max-w-[85%] rounded-xl rounded-tr-sm bg-[#dcf8c6] p-3 shadow-sm">
                        <p class="text-sm text-slate-800">Halo Budi, besok Anda memiliki jadwal kunjungan di Poli Umum. Mohon hadir tepat waktu.</p>
                        <p class="mt-1 text-right text-[10px] text-slate-400">08:00</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
