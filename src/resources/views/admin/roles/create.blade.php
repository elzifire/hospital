@extends('layouts.app')

@section('title', 'Tambah Role Baru')
@section('page-title', 'Tambah Role')

@section('content')
@php
    $getModule = function($permName) {
        $name = strtolower(trim($permName));
        if (str_contains($name, 'dashboard')) return 'Dashboard';
        if (str_contains($name, 'user')) return 'Pengguna & Akun';
        if (str_contains($name, 'role') || str_contains($name, 'permission')) return 'Role & Hak Akses';
        if (str_contains($name, 'pnpp') || str_contains($name, 'register')) return 'Database PNPP';
        if (str_contains($name, 'satker')) return 'Satuan Kerja (Satker)';
        if (str_contains($name, 'penyakit')) return 'Penyakit (Kronis & Menahun)';
        if (str_contains($name, 'poli')) return 'Instalasi Poli';
        if (str_contains($name, 'outreach')) return 'Outreach & Siaran';
        if (str_contains($name, 'reminder')) return 'Digital Reminder';
        if (str_contains($name, 'kunjungan')) return 'Kunjungan Pasien';
        if (str_contains($name, 'respon')) return 'Respon Pasien';
        if (str_contains($name, 'follow-up') || str_contains($name, 'followup')) return 'Follow Up';
        if (str_contains($name, 'auto-reply') || str_contains($name, 'template') || str_contains($name, 'setting')) return 'Pengaturan & Template';
        if (str_contains($name, 'master')) return 'Data Master';
        
        $parts = preg_split('/[\s._-]+/', $permName);
        return count($parts) > 1 ? ucfirst(end($parts)) : ucfirst($parts[0]);
    };

    $groupedPermissions = [];
    foreach ($permissions as $perm) {
        $mod = $getModule($perm->name);
        $groupedPermissions[$mod][] = $perm;
    }
    ksort($groupedPermissions);

    $oldPerms = old('permissions', []);
@endphp

{{-- Select2 CSS --}}
<link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
<style>
    /* Custom Tailwind-styled Select2 */
    .select2-container--default .select2-selection--multiple {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        min-height: 48px;
        padding: 4px 8px;
        transition: all 0.2s ease;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        background-color: #ffffff;
        border-color: #0284c7;
        box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.2);
        outline: none;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 0.5rem;
        color: #0369a1;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 3px 8px 3px 22px;
        margin-top: 4px;
        margin-right: 4px;
        position: relative;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #0284c7;
        border: none;
        background: transparent;
        font-size: 14px;
        font-weight: bold;
        position: absolute;
        left: 5px;
        top: 2px;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #e11d48;
        background: transparent;
    }
    .select2-dropdown {
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        z-index: 50;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 6px 12px;
        font-size: 0.75rem;
        outline: none;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field:focus {
        border-color: #0284c7;
        box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.15);
    }
    .select2-results__group {
        background-color: #f8fafc;
        color: #475569;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        padding: 6px 12px;
        border-top: 1px solid #f1f5f9;
        border-bottom: 1px solid #f1f5f9;
    }
    .select2-container--default .select2-results__option {
        font-size: 0.75rem;
        padding: 6px 14px;
        color: #334155;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #0284c7;
        color: #ffffff;
    }
    .select2-container--default .select2-results__option[aria-selected=true] {
        background-color: #e0f2fe;
        color: #0369a1;
        font-weight: 600;
    }
</style>

<div x-data="roleCreateForm()" class="mx-auto max-w-6xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.roles.index') }}" class="rounded transition hover:text-sky-600">Manajemen Role</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">Tambah Role Baru</span>
        </nav>
        <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-100 text-sky-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            </div>
            <div>
                <h2 class="text-xl font-bold tracking-tight text-slate-900">Tambah Role Baru</h2>
                <p class="text-xs text-slate-500">Tentukan nama role dan pilih permission hak akses menggunakan pencarian Select2.</p>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.roles.store') }}" method="POST" id="roleForm" @submit="saving = true">
        @csrf

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- ===== Kolom Input Utama (2 Kolom) ===== --}}
            <div class="space-y-6 lg:col-span-2">

                {{-- 1. Card Nama Role --}}
                <div class="rounded-2xl bg-white p-6 shadow-xs ring-1 ring-slate-200">
                    <div class="mb-4">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900">1. Informasi Role</h3>
                        <p class="text-xs text-slate-500">Nama identitas role unik yang dipakai saat penugasan hak akses user.</p>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label for="name" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">
                                Nama Role <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" x-model="name" required
                                   placeholder="cth. Kasir, Dokter, Farmasi, Kepala Ruangan"
                                   class="block w-full rounded-xl border-0 bg-slate-50/70 py-2.5 px-3.5 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('name') ring-rose-300 focus:ring-rose-500 @enderror">
                            @error('name')
                                <p class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-rose-600">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9 .75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                                    {{ $message }}
                                </p>
                            @enderror
                            <p class="mt-1.5 text-[11px] text-slate-400">
                                Format disarankan: huruf kecil atau kapital per kata (contoh: <code class="rounded bg-slate-100 px-1 py-0.5 font-mono text-[10px] text-slate-600">kasir</code>, <code class="rounded bg-slate-100 px-1 py-0.5 font-mono text-[10px] text-slate-600">perawat_poli</code>).
                            </p>
                        </div>
                    </div>
                </div>

                {{-- 2. Card Permission Picker dengan Select2 --}}
                <div class="rounded-2xl bg-white p-6 shadow-xs ring-1 ring-slate-200">
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900">2. Hak Akses (Permissions)</h3>
                            <p class="text-xs text-slate-500">Pilih izin modul yang diizinkan untuk role ini.</p>
                        </div>
                        {{-- Action Buttons --}}
                        <div class="flex items-center gap-2">
                            <button type="button" @click="selectAll()"
                                    class="inline-flex items-center gap-1 rounded-lg bg-sky-50 px-2.5 py-1.5 text-xs font-bold text-sky-700 ring-1 ring-sky-200/80 transition hover:bg-sky-100 cursor-pointer">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                Pilih Semua
                            </button>
                            <button type="button" @click="clearAll()"
                                    class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-bold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-200 cursor-pointer">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                Kosongkan
                            </button>
                        </div>
                    </div>

                    {{-- Quick Category Pills --}}
                    <div class="mb-4 rounded-xl bg-slate-50/80 p-3 ring-1 ring-slate-100">
                        <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">Pilih Cepat Berdasarkan Kategori:</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($groupedPermissions as $groupName => $perms)
                                @php
                                    $permNames = collect($perms)->pluck('name')->all();
                                @endphp
                                <button type="button"
                                        @click="toggleCategory(@js($permNames))"
                                        class="inline-flex items-center gap-1 rounded-lg bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700 shadow-2xs ring-1 ring-slate-200 transition hover:bg-sky-50 hover:text-sky-700 hover:ring-sky-300 cursor-pointer">
                                    <span>+ {{ $groupName }}</span>
                                    <span class="rounded-md bg-slate-100 px-1 py-0.2 text-[9px] font-bold text-slate-500">{{ count($perms) }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Select2 Multi-Select Element --}}
                    <div>
                        <label for="permissions_select" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">
                            Cari & Pilih Permission (Select2)
                        </label>
                        <select name="permissions[]" id="permissions_select" multiple="multiple" class="w-full" style="width: 100%;">
                            @foreach ($groupedPermissions as $groupName => $groupPerms)
                                <optgroup label="{{ $groupName }}">
                                    @foreach ($groupPerms as $perm)
                                        <option value="{{ $perm->name }}" {{ in_array($perm->name, $oldPerms) ? 'selected' : '' }}>
                                            {{ $perm->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('permissions')
                            <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

            </div>

            {{-- ===== Kolom Samping (Sticky Preview & Simpan) ===== --}}
            <div class="lg:col-span-1">
                <div class="sticky top-20 space-y-4">

                    {{-- Live Preview Role Card --}}
                    <div class="overflow-hidden rounded-2xl bg-white shadow-xs ring-1 ring-slate-200">
                        <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-3">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Pratinjau Kartu Role</p>
                        </div>
                        <div class="p-5">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-sky-500 to-indigo-600 text-white shadow-sm">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-base font-bold capitalize text-slate-900" x-text="name || 'Nama Role Baru'"></p>
                                    <p class="text-xs text-slate-400">
                                        <span class="font-bold tabular-nums text-sky-600" x-text="selectedCount"></span> dari {{ $permissions->count() }} permission dipilih
                                    </p>
                                </div>
                            </div>

                            {{-- Progress Bar --}}
                            <div class="mt-4">
                                <div class="mb-1 flex items-center justify-between text-[11px]">
                                    <span class="text-slate-400">Cakupan Akses</span>
                                    <span class="font-bold text-slate-700" x-text="Math.round((selectedCount / {{ max(1, $permissions->count()) }}) * 100) + '%'"></span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-linear-to-r from-sky-500 to-indigo-600 transition-all duration-300"
                                         :style="'width:' + Math.min(100, Math.max(4, (selectedCount / {{ max(1, $permissions->count()) }}) * 100)) + '%'"></div>
                                </div>
                            </div>

                            {{-- Selected List Pills Preview --}}
                            <div class="mt-4 border-t border-slate-100 pt-3">
                                <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">Daftar Terpilih (<span x-text="selectedCount"></span>):</p>
                                <div class="max-h-48 overflow-y-auto space-y-1 pr-1">
                                    <template x-for="perm in selectedList" :key="perm">
                                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-2.5 py-1 text-xs text-slate-700 ring-1 ring-slate-100">
                                            <span class="truncate font-medium" x-text="perm"></span>
                                            <button type="button" @click="removePerm(perm)" class="text-slate-400 hover:text-rose-600 cursor-pointer ml-1 text-sm font-bold">×</button>
                                        </div>
                                    </template>
                                    <template x-if="selectedCount === 0">
                                        <p class="py-2 text-center text-xs italic text-slate-400">Belum ada permission yang dipilih.</p>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tips RBAC --}}
                    <div class="rounded-2xl bg-sky-50/70 p-4 text-xs leading-relaxed text-sky-900 ring-1 ring-sky-200/70">
                        <div class="flex items-center gap-1.5 font-bold text-sky-950">
                            <svg class="h-4 w-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                            Tips Pembagian Role
                        </div>
                        <p class="mt-1 text-sky-800">
                            Gunakan prinsip <em>least privilege</em>: berikan hanya permission yang benar-benar dibutuhkan oleh staf sesuai tugasnya di rumah sakit.
                        </p>
                    </div>

                    {{-- Tombol Simpan & Batal --}}
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('admin.roles.index') }}"
                           class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-xs font-bold text-slate-700 shadow-xs transition hover:bg-slate-50 cursor-pointer">
                            Batal
                        </a>
                        <button type="submit" :disabled="saving"
                                class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-xs font-bold text-white shadow-sm shadow-sky-600/30 transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 cursor-pointer">
                            <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="saving ? 'Menyimpan...' : 'Simpan Role'">Simpan Role</span>
                        </button>
                    </div>

                </div>
            </div>

        </div>
    </form>
</div>

{{-- jQuery & Select2 JS --}}
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/select2/select2.min.js') }}"></script>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('roleCreateForm', () => ({
            name: @js(old('name', '')),
            saving: false,
            selectedCount: 0,
            selectedList: [],

            init() {
                const self = this;
                const $select = $('#permissions_select');

                $select.select2({
                    placeholder: 'Ketik untuk mencari atau pilih permission...',
                    allowClear: true,
                    closeOnSelect: false,
                    width: '100%'
                });

                // Update Alpine state when select2 changes
                $select.on('change', function () {
                    const values = $(this).val() || [];
                    self.selectedList = values;
                    self.selectedCount = values.length;
                });

                // Inisialisasi list awal
                const initVal = $select.val() || [];
                this.selectedList = initVal;
                this.selectedCount = initVal.length;
            },

            selectAll() {
                const allVals = [];
                $('#permissions_select option').each(function() {
                    allVals.push($(this).val());
                });
                $('#permissions_select').val(allVals).trigger('change');
            },

            clearAll() {
                $('#permissions_select').val([]).trigger('change');
            },

            toggleCategory(permsArray) {
                let current = $('#permissions_select').val() || [];
                const allSelected = permsArray.every(p => current.includes(p));

                if (allSelected) {
                    current = current.filter(p => !permsArray.includes(p));
                } else {
                    permsArray.forEach(p => {
                        if (!current.includes(p)) current.push(p);
                    });
                }
                $('#permissions_select').val(current).trigger('change');
            },

            removePerm(permName) {
                let current = $('#permissions_select').val() || [];
                current = current.filter(p => p !== permName);
                $('#permissions_select').val(current).trigger('change');
            }
        }));
    });
</script>
@endsection

