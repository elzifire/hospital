@extends('layouts.app')

@section('title', 'Import Excel')
@section('page-title', 'Respon Pasien')

@section('content')
@php
    use App\Jobs\PreviewImportJob;
    use App\Support\MasterRegistry;

    $responHeaders = MasterRegistry::config('respon')['headers'];
@endphp

<div class="space-y-6">
    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Import Excel</h2>
            <p class="mt-0.5 text-sm text-slate-500">Import balasan pasien dari file Excel/CSV.</p>
        </div>
    </div>

    @include('admin.respon._flash')

    @include('admin.respon._subnav', ['active' => 'import'])

    <div class="mx-auto max-w-6xl space-y-5">
        @if ($errors->any())
            <div class="rounded-2xl bg-rose-50 px-5 py-4 text-sm text-rose-700 ring-1 ring-rose-200">
                {{ $errors->first('file') ?? $errors->first() }}
            </div>
        @endif

        @if ($importStatus)
            @php($s = $importStatus)
            @if (in_array($s['status'], ['pending', 'processing', 'preview_pending', 'preview_processing'], true))
                <div class="flex items-center gap-4 rounded-2xl bg-sky-50 p-5 ring-1 ring-sky-200">
                    <svg class="h-8 w-8 animate-spin text-sky-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <div>
                        <p class="text-sm font-bold text-sky-800">Sedang diproses...</p>
                        <p class="text-xs text-sky-600">
                            Data diproses per batch. Halaman ini akan diperbarui otomatis.
                            @if (isset($s['processed'], $s['total']) && $s['total'] > 0)
                                <span class="font-semibold">{{ $s['processed'] }} / {{ $s['total'] }} baris</span>
                            @endif
                        </p>
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

        @if ($preview)
            <form action="{{ route('admin.respon.import-confirm') }}" method="POST" x-data="responImportPreview()" x-cloak>
                @csrf
                <input type="hidden" name="token" value="{{ $importToken }}">
                @if ($preview['total'] <= PreviewImportJob::PREVIEW_LIMIT)
                    <input type="hidden" name="rows" :value="rowsJson">
                @endif

                <div class="space-y-4">
                    <div class="flex flex-col gap-3 rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-200 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Pratinjau & Edit Data</h3>
                            <p class="mt-0.5 text-sm text-slate-500">
                                @if ($preview['total'] <= PreviewImportJob::PREVIEW_LIMIT)
                                    Klik sel tabel untuk mengedit langsung sebelum diproses.
                                @else
                                    File besar diproses dari backend per batch; preview hanya menampilkan {{ PreviewImportJob::PREVIEW_LIMIT }} baris pertama.
                                @endif
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 ring-1 ring-slate-200">{{ $preview['total'] }} baris</span>
                            @if ($preview['total'] > count($preview['rows']))
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 ring-1 ring-sky-200">Preview {{ count($preview['rows']) }} baris pertama</span>
                            @endif
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
                                        @foreach ($responHeaders as $header)
                                            <th class="px-3 py-3 font-semibold" style="width: 170px; min-width: 110px">{{ $header }}</th>
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
                                                    <input type="text" x-model="rows[i].values[h]"
                                                           class="w-full rounded-lg border-0 bg-slate-50 px-2.5 py-2 text-xs text-slate-800 ring-1 ring-inset ring-slate-200 transition focus:bg-white focus:ring-2 focus:ring-sky-500">
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
                    </div>

                    <div class="flex flex-col gap-3 rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-200 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-slate-500">Semua baris akan diimport; baris yang tidak valid dilewati otomatis.</p>
                        <div class="flex items-center gap-3">
                            <form method="POST" action="{{ route('admin.respon.import-cancel') }}">
                                @csrf
                                <input type="hidden" name="token" value="{{ $importToken }}">
                                <button type="submit"
                                        class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Batalkan</button>
                            </form>
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
                    <h3 class="text-base font-bold text-slate-900">Import Balasan dari File</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Mendukung file Excel (.xlsx/.xls) dan CSV dengan header sesuai template. Data diproses per batch di queue agar tidak memberatkan server.</p>
                </div>

                <div class="p-6">
                    <form action="{{ route('admin.respon.import-upload') }}" method="POST" enctype="multipart/form-data" x-data="{ saving: false }" @submit="saving = true">
                        @csrf
                        <div class="space-y-5">
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">File (.xlsx / .xls / .csv) <span class="text-rose-500">*</span></label>
                                <input type="file" name="file" accept=".xlsx,.xls,.csv,text/csv" required
                                       class="block w-full cursor-pointer rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition">
                            </div>

                            <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                <p class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-400">Kolom yang diharapkan</p>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($responHeaders as $header)
                                        <code class="rounded-md bg-white px-2 py-1 font-mono text-[11px] text-slate-600 ring-1 ring-slate-200">{{ $header }}</code>
                                    @endforeach
                                </div>
                                <div class="mt-3 flex flex-wrap items-center gap-3">
                                    <a href="{{ route('admin.master.template', ['entity' => 'respon', 'format' => 'xlsx']) }}"
                                       class="inline-flex items-center gap-1.5 text-xs font-bold text-sky-600 hover:underline">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                        Template Excel
                                    </a>
                                    <a href="{{ route('admin.master.template', ['entity' => 'respon', 'format' => 'csv']) }}"
                                       class="inline-flex items-center gap-1.5 text-xs font-bold text-sky-600 hover:underline">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                        Template CSV
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-end gap-3">
                            <a href="{{ route('admin.respon.index') }}"
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
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('responImportPreview', () => ({
            headers: @js($responHeaders),
            rows: @js($preview['rows'] ?? []),
            get rowsJson() {
                return JSON.stringify(this.rows.map(r => r.values));
            },
        }));
    });
</script>

@if ($importStatus && in_array($importStatus['status'], ['pending', 'processing', 'preview_pending', 'preview_processing'], true))
    <script>setTimeout(() => window.location.reload(), 3000);</script>
@endif
@endsection