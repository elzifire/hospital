@extends('layouts.app')

@section('title', 'Import Template Pesan')
@section('page-title', 'Import Template Pesan')

@section('content')
<div x-data="{
    rows: @js($preview['rows'] ?? []),
    token: '{{ $token }}',
    status: '{{ $status['status'] ?? '' }}',
    filterMode: 'all', // all, valid, invalid

    get filteredRows() {
        if (this.filterMode === 'valid') return this.rows.filter(r => r.valid);
        if (this.filterMode === 'invalid') return this.rows.filter(r => !r.valid);
        return this.rows;
    },

    get validCount() {
        return this.rows.filter(r => r.valid).length;
    },

    get invalidCount() {
        return this.rows.filter(r => !r.valid).length;
    },

    removeRow(index) {
        this.rows.splice(index, 1);
    }
}" class="space-y-6">

    {{-- Breadcrumb --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}" class="rounded transition hover:text-sky-600">Dashboard</a>
            <svg class="h-3.5 w-3.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <a href="{{ route('admin.setting.index') }}" class="rounded transition hover:text-sky-600">Pengaturan Template</a>
            <svg class="h-3.5 w-3.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-700">Import Template</span>
        </nav>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-black tracking-tight text-slate-900">Import Template Pesan</h2>
                <p class="mt-1 text-sm text-slate-500">Unggah file Excel atau CSV untuk memasukkan banyak template sekaligus dengan pratinjau data.</p>
            </div>
            <a href="{{ route('admin.setting.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-xs transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                Kembali ke Daftar
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if (session('success'))
        <div class="flex items-center gap-3 rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-200">
            <svg class="h-5 w-5 flex-shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            <span class="flex-1">{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-2xl bg-rose-50 p-4 ring-1 ring-rose-200 text-rose-800">
            <div class="flex items-center gap-2 font-bold text-sm">
                <svg class="h-5 w-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 8.25h.008v.008H12v-.008Z" /></svg>
                Terjadi kesalahan pada proses unggah:
            </div>
            <ul class="mt-2 list-disc list-inside text-xs space-y-1">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Bagian 1: Status Selesai / Gagal --}}
    @if (($status['status'] ?? null) === 'completed')
        <div class="rounded-2xl bg-emerald-50 p-6 shadow-xs ring-1 ring-emerald-200 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
            </div>
            <h3 class="mt-3 text-lg font-bold text-emerald-900">Import Selesai Diproses!</h3>
            <p class="mt-1 text-xs text-emerald-700">
                Berhasil mengimport <strong>{{ $status['imported'] ?? 0 }}</strong> template pesan.
                @if (($status['failed'] ?? 0) > 0)
                    (Gagal: {{ $status['failed'] }})
                @endif
            </p>
            <div class="mt-4">
                <a href="{{ route('admin.setting.index') }}" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-emerald-700">
                    Lihat Template Pesan
                </a>
            </div>
        </div>
    @endif

    {{-- Bagian 2: Upload File & Unduh Template Contoh (Jika belum ada preview) --}}
    @if (! $preview)
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Upload Form --}}
            <div class="lg:col-span-2 rounded-2xl bg-white p-6 shadow-xs ring-1 ring-slate-200">
                <h3 class="text-base font-bold text-slate-900">Unggah File Dokumen</h3>
                <p class="mt-1 text-xs text-slate-500">Mendukung format file spreadsheet (.xlsx, .xls) atau .csv dengan ukuran maksimal 5 MB.</p>

                <form action="{{ route('admin.setting.import.upload') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
                    @csrf
                    <div class="relative flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 p-8 text-center transition hover:border-sky-500 hover:bg-sky-50/20">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 text-sky-600 ring-1 ring-sky-100">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                        </div>
                        <label for="fileUpload" class="mt-3 block text-xs font-bold text-slate-800 cursor-pointer">
                            <span class="text-sky-600 hover:underline">Pilih file spreadsheet</span> atau seret file ke area ini
                        </label>
                        <p class="mt-1 text-[11px] text-slate-400">XLSX, XLS, CSV (Maks. 5 MB)</p>
                        <input id="fileUpload" type="file" name="file" required accept=".xlsx,.xls,.csv,.txt"
                               class="absolute inset-0 h-full w-full opacity-0 cursor-pointer">
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <div class="flex items-center gap-2 text-xs text-slate-500">
                            <span class="flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span>Queue: <strong>Redis</strong> diproses di latar belakang</span>
                        </div>
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-sky-700">
                            <span>Upload & Pratinjau</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Download Template Guide Card --}}
            <div class="rounded-2xl bg-gradient-to-br from-white to-slate-50 p-6 shadow-xs ring-1 ring-slate-200">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                    </span>
                    <h4 class="text-sm font-bold text-slate-900">Format Kolom Baku</h4>
                </div>
                <p class="mt-2 text-xs leading-relaxed text-slate-500">Gunakan file contoh di bawah agar susunan header kolom sesuai dengan sistem import.</p>

                <div class="mt-4 space-y-2">
                    <a href="{{ route('admin.setting.export.template', ['format' => 'xlsx']) }}"
                       class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-3 text-xs font-semibold text-slate-700 shadow-2xs hover:border-emerald-300 hover:bg-emerald-50/50">
                        <span class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 font-mono text-[10px] font-bold">XLS</span>
                            <span>Unduh Template (.xlsx)</span>
                        </span>
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    </a>
                    <a href="{{ route('admin.setting.export.template', ['format' => 'csv']) }}"
                       class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-3 text-xs font-semibold text-slate-700 shadow-2xs hover:border-sky-300 hover:bg-sky-50/50">
                        <span class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-sky-100 text-sky-700 font-mono text-[10px] font-bold">CSV</span>
                            <span>Unduh Template (.csv)</span>
                        </span>
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    </a>
                </div>

                <div class="mt-4 rounded-xl bg-slate-100 p-3 text-[11px] text-slate-600">
                    <p class="font-bold">Kolom yang wajib terisi:</p>
                    <ul class="mt-1 list-disc list-inside text-slate-500 space-y-0.5">
                        <li><strong>Judul</strong>: Judul singkat template</li>
                        <li><strong>Isi Pesan</strong>: Konten pesan pengingat</li>
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- Bagian 3: Pratinjau Data (Preview) Sebelum Konfirmasi --}}
    @if ($preview)
        <div class="rounded-2xl bg-white shadow-xs ring-1 ring-slate-200 overflow-hidden">
            {{-- Header Pratinjau --}}
            <div class="border-b border-slate-100 p-5 sm:flex sm:items-center sm:justify-between bg-slate-50/60">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-bold text-slate-900">Pratinjau Data Import</h3>
                        <span class="rounded-full bg-slate-200 px-2.5 py-0.5 text-xs font-bold text-slate-700" x-text="rows.length + ' baris'"></span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Periksa dan koreksi data sebelum disimpan secara permanen ke database.</p>
                </div>

                <div class="mt-4 sm:mt-0 flex flex-wrap items-center gap-2">
                    {{-- Filter Tabs --}}
                    <div class="flex rounded-xl bg-slate-200/80 p-1 text-xs font-bold">
                        <button type="button" @click="filterMode = 'all'"
                                :class="filterMode === 'all' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                class="rounded-lg px-3 py-1 transition">
                            Semua (<span x-text="rows.length"></span>)
                        </button>
                        <button type="button" @click="filterMode = 'valid'"
                                :class="filterMode === 'valid' ? 'bg-emerald-600 text-white shadow-xs' : 'text-emerald-700 hover:text-emerald-900'"
                                class="rounded-lg px-3 py-1 transition">
                            Valid (<span x-text="validCount"></span>)
                        </button>
                        <button type="button" @click="filterMode = 'invalid'"
                                :class="filterMode === 'invalid' ? 'bg-rose-600 text-white shadow-xs' : 'text-rose-700 hover:text-rose-900'"
                                class="rounded-lg px-3 py-1 transition">
                            Error (<span x-text="invalidCount"></span>)
                        </button>
                    </div>

                    {{-- Form Batal --}}
                    <form action="{{ route('admin.setting.import.cancel') }}" method="POST">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <button type="submit" class="rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50">
                            Batal
                        </button>
                    </form>

                    {{-- Form Konfirmasi --}}
                    <form action="{{ route('admin.setting.import.confirm') }}" method="POST" @submit="$refs.rowsInput.value = JSON.stringify(rows)">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <input type="hidden" name="rows" x-ref="rowsInput">
                        <button type="submit"
                                :disabled="validCount === 0"
                                class="rounded-xl bg-emerald-600 px-4 py-1.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            Konfirmasi Import (<span x-text="validCount"></span> baris)
                        </button>
                    </form>
                </div>
            </div>

            {{-- Table Preview --}}
            <div class="overflow-x-auto max-h-[500px]">
                <table class="w-full text-left text-xs">
                    <thead class="sticky top-0 bg-slate-100 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4 w-12 text-center">No</th>
                            <th class="py-3 px-4 w-28">Status</th>
                            <th class="py-3 px-4 w-36">Kategori</th>
                            <th class="py-3 px-4 w-48">Judul Template</th>
                            <th class="py-3 px-4 w-28">Channel</th>
                            <th class="py-3 px-4">Isi Pesan</th>
                            <th class="py-3 px-4 w-12 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <template x-for="(r, idx) in filteredRows" :key="r.row_number">
                            <tr :class="r.valid ? 'hover:bg-slate-50/60' : 'bg-rose-50/40 hover:bg-rose-50/70'">
                                <td class="py-3 px-4 text-center font-mono text-slate-400" x-text="r.row_number"></td>
                                <td class="py-3 px-4">
                                    <template x-if="r.valid">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-600/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Valid
                                        </span>
                                    </template>
                                    <template x-if="!r.valid">
                                        <div>
                                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700 ring-1 ring-rose-600/20">
                                                Error
                                            </span>
                                            <p class="mt-1 text-[10px] text-rose-600" x-text="r.errors.join(', ')"></p>
                                        </div>
                                    </template>
                                </td>
                                <td class="py-3 px-4">
                                    <input type="text" x-model="r.kategori" class="w-full rounded-lg border-0 py-1 px-2 text-xs ring-1 ring-slate-200 focus:ring-2 focus:ring-sky-500">
                                </td>
                                <td class="py-3 px-4">
                                    <input type="text" x-model="r.judul" class="w-full rounded-lg border-0 py-1 px-2 text-xs ring-1 ring-slate-200 focus:ring-2 focus:ring-sky-500">
                                </td>
                                <td class="py-3 px-4">
                                    <select x-model="r.channel" class="w-full rounded-lg border-0 py-1 px-2 text-xs ring-1 ring-slate-200 focus:ring-2 focus:ring-sky-500">
                                        <option value="WhatsApp">WhatsApp</option>
                                        <option value="SMS">SMS</option>
                                        <option value="Email">Email</option>
                                    </select>
                                </td>
                                <td class="py-3 px-4">
                                    <textarea rows="2" x-model="r.konten" class="w-full rounded-lg border-0 py-1 px-2 text-xs ring-1 ring-slate-200 focus:ring-2 focus:ring-sky-500"></textarea>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <button type="button" @click="removeRow(idx)" class="text-slate-400 hover:text-rose-600" title="Hapus baris">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection
