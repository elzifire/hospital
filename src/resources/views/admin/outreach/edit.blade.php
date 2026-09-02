@extends('layouts.app')

@section('title', 'Edit Pesan Masuk')
@section('page-title', 'Edit Pesan Masuk')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.outreach.index') }}" class="rounded transition hover:text-sky-600">Outreach</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">Edit Pesan</span>
        </nav>
        <h2 class="text-xl font-bold tracking-tight text-slate-900">Edit Pesan Masuk</h2>
        <p class="mt-0.5 text-sm text-slate-500">Perbarui data balasan WhatsApp.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ===== Form ===== --}}
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 p-6">
                    <h3 class="text-base font-bold text-slate-900">Detail Pesan</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Data pengirim dan isi balasan.</p>
                </div>

                <div class="space-y-5 p-6">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Pengirim (PNPP) <span class="text-rose-500">*</span></label>
                        <select class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition cursor-pointer">
                            <option>Budi Santoso</option>
                            <option>Siti Aminah</option>
                            <option>Agus Wijaya</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">No. WhatsApp</label>
                        <input type="text" value="081234567890" class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Pertanyaan / Kampanye</label>
                        <select class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition cursor-pointer">
                            <option>Konfirmasi kehadiran kontrol</option>
                            <option>Konfirmasi vaksinasi</option>
                            <option>Follow up pasca rawat</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Isi Balasan <span class="text-rose-500">*</span></label>
                        <textarea rows="3" class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition">Ya, saya akan datang.</textarea>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Kategori Balasan</label>
                            <select class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition cursor-pointer">
                                <option>Hadir (Ya)</option>
                                <option>Tidak Hadir (Tidak)</option>
                                <option>Perlu Ditinjau</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Waktu Diterima</label>
                            <input type="datetime-local" value="2026-09-02T07:32" class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action bar --}}
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.outreach.index') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Batal</a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    Simpan Perubahan
                </button>
            </div>
        </div>

        {{-- ===== Pratinjau WhatsApp ===== --}}
        <div class="lg:col-span-1">
            <div class="sticky top-20 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 bg-emerald-600 px-5 py-4">
                    <p class="text-sm font-bold text-white">Pratinjau Percakapan</p>
                </div>
                <div class="space-y-3 bg-[#e5ddd5] p-5">
                    <div class="ml-auto max-w-[80%] rounded-xl rounded-tr-sm bg-[#dcf8c6] p-3 shadow-sm">
                        <p class="text-sm text-slate-800">Apakah Anda akan datang untuk kontrol besok?</p>
                        <p class="mt-1 text-right text-[10px] text-slate-400">07:30</p>
                    </div>
                    <div class="max-w-[80%] rounded-xl rounded-tl-sm bg-white p-3 shadow-sm">
                        <p class="text-sm text-slate-800">Ya, saya akan datang.</p>
                        <p class="mt-1 text-right text-[10px] text-slate-400">07:32 ✓✓</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
