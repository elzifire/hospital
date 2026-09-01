@extends('layouts.app')

@section('title', 'Import ' . $config['label'])
@section('page-title', 'Import ' . $config['label'])

@section('content')
@php $indexRoute = 'admin.' . $entity . '.index'; @endphp

<style>
    .col-resize-handle {
        position: absolute;
        top: 0;
        right: 0;
        width: 7px;
        height: 100%;
        cursor: col-resize;
        background: transparent;
        transition: background-color .15s ease;
    }
    .col-resize-handle:hover,
    .col-resize-handle.active {
        background: #0284c7;
    }
    body.resizing {
        cursor: col-resize;
        user-select: none;
    }
</style>

<div class="mx-auto max-w-6xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <span class="text-slate-500">Data Master</span>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <a href="{{ route($indexRoute) }}" class="rounded transition hover:text-sky-600">{{ $config['label'] }}</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">Import</span>
        </nav>
        <h2 class="text-xl font-bold tracking-tight text-slate-900">Import {{ $config['label'] }}</h2>
        <p class="mt-0.5 text-sm text-slate-500">Upload file Excel/CSV, pratinjau & edit data, lalu proses import di background.</p>
    </div>

    {{-- ===== Status Import (dari queue) ===== --}}
    @if ($status)
        @php $s = $status; @endphp
        @if (in_array($s['status'], ['pending', 'processing', 'preview_pending', 'preview_processing'], true))
            <div class="flex items-center gap-4 rounded-2xl bg-sky-50 p-5 ring-1 ring-sky-200">
                <svg class="h-8 w-8 animate-spin text-sky-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <div>
                    <p class="text-sm font-bold text-sky-800">Sedang diproses...</p>
                    <p class="text-xs text-sky-600">Data sedang diproses lewat queue (Redis). Halaman ini akan diperbarui otomatis.</p>
                </div>
            </div>
        @elseif ($s['status'] === 'completed')
            <div class="rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-200">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold text-slate-900">Import selesai</p>
                        <p class="text-xs text-slate-500">
                            <span class="font-semibold text-emerald-600">{{ $s['created'] }}</span> ditambahkan ·
                            <span class="font-semibold text-sky-600">{{ $s['updated'] }}</span> diperbarui ·
                            <span class="font-semibold text-rose-600">{{ $s['failed'] }}</span> gagal
                        </p>
                    </div>
                </div>

                @if (! empty($s['errors']))
                    <div class="mt-4 rounded-xl bg-rose-50 p-4 ring-1 ring-rose-100">
                        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-rose-600">Baris yang gagal</p>
                        <ul class="space-y-2 text-xs text-rose-700">
                            @foreach ($s['errors'] as $err)
                                <li class="rounded-lg bg-white/60 p-2.5">
                                    <p class="font-mono text-[10px] text-slate-400">{{ implode(', ', $err['row']) }}</p>
                                    <p class="mt-0.5 font-medium">{{ implode(' · ', $err['errors']) }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @elseif (in_array($s['status'], ['failed', 'preview_failed'], true))
            <div class="flex items-start gap-3 rounded-2xl bg-rose-50 p-5 ring-1 ring-rose-200">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                <div>
                    <p class="text-sm font-bold text-rose-800">Proses gagal</p>
                    <p class="mt-0.5 text-xs text-rose-600">{{ $s['message'] ?? 'Terjadi kesalahan.' }}</p>
                </div>
            </div>
        @endif
    @endif

    {{-- ===== Preview (editable) / Form ===== --}}
    @if ($preview)
        <form action="{{ route('admin.master.import.confirm', $entity) }}" method="POST" x-data="importPreview()" x-cloak>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="rows" :value="rowsJson">

            <div class="space-y-4">
                <div class="flex flex-col gap-3 rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-200 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Pratinjau & Edit Data</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Klik sel tabel untuk mengedit langsung sebelum diproses.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 ring-1 ring-slate-200">{{ $preview['total'] }} baris</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200/70">{{ $preview['valid'] }} valid</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 ring-1 ring-rose-200/70">{{ $preview['invalid'] }} perlu diperiksa</span>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[720px] text-left">
                            <thead>
                                <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                                    <th class="px-3 py-3 font-semibold" style="width: 44px; min-width: 44px">#</th>
                                    @foreach ($config['headers'] as $header)
                                        <th class="resizable-col relative px-3 py-3 font-semibold" style="width: 170px; min-width: 90px">
                                            <span class="block truncate" title="{{ $header }}">{{ $header }}</span>
                                            <span class="col-resize-handle" title="Geser untuk mengatur lebar kolom"></span>
                                        </th>
                                    @endforeach
                                    <th class="px-3 py-3 font-semibold" style="width: 200px; min-width: 160px">Status Awal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <template x-for="(r, i) in rows" :key="i">
                                    <tr :class="r.errors.length > 0 ? 'bg-rose-50/40' : ''">
                                        <td class="px-4 py-2 font-mono text-xs text-slate-400" x-text="i + 2"></td>
                                        <template x-for="h in headers" :key="h">
                                            <td class="px-1.5 py-1.5 align-top">

                                                {{-- Dropdown pilihan tunggal (mis. Satker, Poli, Hari) --}}
                                                <template x-if="fieldType(h) === 'select'">
                                                    <select x-model="rows[i].values[h]"
                                                            class="w-full cursor-pointer rounded-lg border-0 bg-slate-50 px-2.5 py-2 text-xs text-slate-800 ring-1 ring-inset ring-slate-200 transition focus:bg-white focus:ring-2 focus:ring-sky-500">
                                                        <option value="">— Pilih —</option>
                                                        <template x-for="opt in fieldOptions(h)" :key="opt">
                                                            <option :value="opt" x-text="opt"></option>
                                                        </template>
                                                        <option x-show="rows[i].values[h] && !fieldOptions(h).includes(rows[i].values[h])" :value="rows[i].values[h]" x-text="rows[i].values[h]"></option>
                                                    </select>
                                                </template>

                                                {{-- Multi-select mirip select2 (mis. Penyakit Kronis) --}}
                                                <template x-if="fieldType(h) === 'multiselect'">
                                                    <div class="w-full" x-data="{ open: false, q: '' }">
                                                        <button type="button" @click="open = !open"
                                                                class="flex w-full flex-wrap items-center gap-1 rounded-lg bg-slate-50 px-2.5 py-2 text-left ring-1 ring-inset ring-slate-200 transition hover:ring-slate-300">
                                                            <template x-for="tag in splitTags(rows[i].values[h])" :key="tag">
                                                                <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-1.5 py-0.5 text-[10px] font-medium text-amber-700 ring-1 ring-amber-200/70">
                                                                    <span x-text="tag"></span>
                                                                    <span @click.stop="removeTag(i, h, tag)" class="cursor-pointer font-bold text-amber-400 hover:text-amber-700">×</span>
                                                                </span>
                                                            </template>
                                                            <span x-show="splitTags(rows[i].values[h]).length === 0" class="text-xs text-slate-400">Pilih...</span>
                                                            <svg class="ml-auto h-3.5 w-3.5 flex-shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                                                        </button>
                                                        <div x-show="open" x-cloak x-transition class="mt-1 rounded-lg bg-white p-2 shadow-sm ring-1 ring-slate-200">
                                                            <input x-model="q" placeholder="Cari..." class="mb-1.5 w-full rounded-md border-0 bg-slate-50 px-2 py-1.5 text-xs text-slate-800 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500">
                                                            <ul class="max-h-44 space-y-0.5 overflow-y-auto">
                                                                <template x-for="opt in availableOptions(h, i, q)" :key="opt">
                                                                    <li @click="addTag(i, h, opt); q = ''" class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-xs text-slate-700 transition hover:bg-sky-50">
                                                                        <svg class="h-3 w-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                                                        <span x-text="opt"></span>
                                                                    </li>
                                                                </template>
                                                                <li x-show="availableOptions(h, i, q).length === 0" class="px-2 py-1 text-xs italic text-slate-400">Tidak ada yang tersisa</li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </template>

                                                {{-- Input teks bebas --}}
                                                <template x-if="fieldType(h) === 'text'">
                                                    <input type="text" x-model="rows[i].values[h]" :title="cellInvalid(i, h) ? 'Harus berupa angka (format kolom Excel: Text)' : ''"
                                                           class="w-full rounded-lg border-0 bg-slate-50 px-2.5 py-2 text-xs text-slate-800 ring-1 ring-inset ring-slate-200 transition focus:bg-white focus:ring-2 focus:ring-sky-500"
                                                           :class="cellInvalid(i, h) ? 'ring-2 ring-rose-400 bg-rose-50/50' : ''">
                                                </template>
                                            </td>
                                        </template>
                                        <td class="px-4 py-2">
                                            <span x-show="r.errors.length === 0" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                                Valid
                                            </span>
                                            <span x-show="r.errors.length > 0" class="inline-flex items-center gap-1 text-xs font-semibold text-rose-600" :title="r.errors.join(' · ')">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                                                <span class="max-w-[220px] truncate" x-text="r.errors.join(' · ')"></span>
                                            </span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-slate-100 bg-slate-50/60 px-6 py-3 text-xs text-slate-400">
                        Kolom <em>Status Awal</em> adalah hasil validasi awal. Baris yang diedit akan divalidasi ulang saat diproses.
                    </div>
                </div>

                <div class="flex flex-col gap-3 rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-200 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-slate-500">Semua baris akan diimport; baris yang tidak valid dilewati otomatis.</p>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.master.import', $entity) }}"
                           class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Batalkan</a>
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75 6 14.25l3.75-3.75M4.5 18.75l9.75-9.75 3 3" /></svg>
                            Proses Import
                        </button>
                    </div>
                </div>
            </div>
        </form>
    @else
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-100 p-6">
                <h3 class="text-base font-bold text-slate-900">Upload File</h3>
                <p class="mt-0.5 text-sm text-slate-500">Mendukung file Excel (.xlsx/.xls) dan CSV dengan header sesuai template.</p>
            </div>

            <div class="p-6">
                <form action="{{ route('admin.master.import.upload', $entity) }}" method="POST" enctype="multipart/form-data" x-data="{ saving: false }" @submit="saving = true">
                    @csrf
                    <div class="space-y-5">
                        @if ($errors->any())
                            <div class="flex items-start gap-2.5 rounded-xl bg-rose-50 p-4 ring-1 ring-rose-200">
                                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                                <p class="text-sm text-rose-700">{{ $errors->first('file') }}</p>
                            </div>
                        @endif

                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">File (.xlsx / .xls / .csv) <span class="text-rose-500">*</span></label>
                            <input type="file" name="file" accept=".xlsx,.xls,.csv,text/csv" required
                                   class="block w-full cursor-pointer rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition">
                        </div>

                        <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-400">Kolom yang diharapkan</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($config['headers'] as $header)
                                    <code class="rounded-md bg-white px-2 py-1 font-mono text-[11px] text-slate-600 ring-1 ring-slate-200">{{ $header }}</code>
                                @endforeach
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-3">
                                <a href="{{ route('admin.master.template', ['entity' => $entity, 'format' => 'xlsx']) }}"
                                   class="inline-flex items-center gap-1.5 text-xs font-bold text-sky-600 hover:underline">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                    Template Excel
                                </a>
                                <a href="{{ route('admin.master.template', ['entity' => $entity, 'format' => 'csv']) }}"
                                   class="inline-flex items-center gap-1.5 text-xs font-bold text-sky-600 hover:underline">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                    Template CSV
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a href="{{ route($indexRoute) }}"
                           class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Kembali</a>
                        <button type="submit" :disabled="saving"
                                class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-50">
                            <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                            <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="saving ? 'Memeriksa...' : 'Pratinjau'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('importPreview', () => ({
            headers: @js($config['headers']),
            rows: @js($preview['rows'] ?? []),
            fields: @js(\App\Support\MasterRegistry::fields($entity)),

            get rowsJson() {
                return JSON.stringify(this.rows.map(r => r.values));
            },

            fieldType(h) {
                return this.fields[h]?.type ?? 'text';
            },
            fieldOptions(h) {
                return this.fields[h]?.options ?? [];
            },
            splitTags(value) {
                return (value || '').split(',').map(s => s.trim()).filter(Boolean);
            },
            availableOptions(h, i, q) {
                const selected = this.splitTags(this.rows[i].values[h]);
                return this.fieldOptions(h).filter(o => !selected.includes(o) && (!q || o.toLowerCase().includes(q.toLowerCase())));
            },
            addTag(i, h, tag) {
                const tags = this.splitTags(this.rows[i].values[h]);
                if (!tags.includes(tag)) tags.push(tag);
                this.rows[i].values[h] = tags.join(', ');
            },
            removeTag(i, h, tag) {
                this.rows[i].values[h] = this.splitTags(this.rows[i].values[h]).filter(t => t !== tag).join(', ');
            },
            cellInvalid(i, h) {
                if (h !== 'No. BPJS') return false;
                const v = (this.rows[i].values[h] || '').trim();
                return v !== '' && !/^\d+$/.test(v);
            },
        }));
    });
</script>

@if ($preview)
    <script>
        (function () {
            document.querySelectorAll('.col-resize-handle').forEach((handle) => {
                handle.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const th = handle.closest('th');
                    const startX = e.clientX;
                    const startWidth = th.getBoundingClientRect().width;

                    handle.classList.add('active');
                    document.body.classList.add('resizing');

                    const onMove = (ev) => {
                        const width = Math.max(90, startWidth + (ev.clientX - startX));
                        th.style.width = width + 'px';
                        th.style.minWidth = width + 'px';
                    };

                    const onUp = () => {
                        handle.classList.remove('active');
                        document.body.classList.remove('resizing');
                        document.removeEventListener('mousemove', onMove);
                        document.removeEventListener('mouseup', onUp);
                    };

                    document.addEventListener('mousemove', onMove);
                    document.addEventListener('mouseup', onUp);
                });
            });
        })();
    </script>
@endif

@if ($status && in_array($status['status'], ['pending', 'processing', 'preview_pending', 'preview_processing'], true))
    <script>setTimeout(() => window.location.reload(), 3000);</script>
@endif
@endsection
