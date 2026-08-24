@extends('layouts.app')

@section('title', 'Tambah Permission')
@section('page-title', 'Tambah Permission')

@section('content')
<div x-data="permissionForm()" class="mx-auto max-w-5xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.permissions.index') }}" class="rounded transition hover:text-sky-600">Manajemen Permission</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">Tambah Baru</span>
        </nav>
        <h2 class="text-xl font-bold tracking-tight text-slate-900">Tambah Permission Baru</h2>
        <p class="mt-0.5 text-sm text-slate-500">Permission adalah izin akses terkecil, cth: lihat data pasien.</p>
    </div>

    <form action="{{ route('admin.permissions.store') }}" method="POST" @submit="saving = true" x-cloak>
        @csrf
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- ===== Form ===== --}}
            <div class="lg:col-span-2">
                <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="border-b border-slate-100 p-6">
                        <h3 class="text-base font-bold text-slate-900">Informasi Permission</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Gunakan format <code class="rounded bg-slate-100 px-1 font-mono text-xs">verb resource</code> agar konsisten.</p>
                    </div>

                    <div class="space-y-5 p-6">
                        <div>
                            <label for="name" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Nama Permission <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" x-model="name" required placeholder="cth. view pasien"
                                   class="block w-full rounded-xl border-0 py-2.5 px-3.5 font-mono text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('name') ring-rose-300 focus:ring-rose-500 @enderror">
                            @error('name')
                                <p class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-rose-600">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9 .75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                                    {{ $message }}
                                </p>
                            @enderror

                            {{-- Live preview --}}
                            <div x-show="name.trim() !== ''" x-cloak class="mt-4 flex items-center gap-2.5 rounded-xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pratinjau:</span>
                                <span class="inline-flex items-center gap-1.5 rounded-md bg-white px-2 py-1 font-mono text-xs font-semibold text-violet-700 shadow-xs ring-1 ring-violet-200">
                                    <svg class="h-3 w-3 text-violet-400" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" /></svg>
                                    <span x-text="name"></span>
                                </span>
                                <span class="ml-auto text-[10px] text-slate-400"
                                      :class="isValidFormat ? 'text-emerald-600' : 'text-amber-600'"
                                      x-text="isValidFormat ? '✓ Format konsisten' : '⚠ Disarankan: kata kecil + spasi'"></span>
                            </div>
                        </div>

                        <label class="flex items-start gap-3 rounded-xl bg-sky-50/60 p-4 ring-1 ring-sky-100">
                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-sky-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                            </svg>
                            <span class="text-xs leading-relaxed text-sky-800">
                                Setelah dibuat, permission belum otomatis aktif — assign ke salah satu role lewat halaman
                                <a href="{{ route('admin.roles.index') }}" class="font-bold underline decoration-sky-300 underline-offset-2 hover:text-sky-900">Manajemen Role</a>.
                            </span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 rounded-b-2xl border-t border-slate-100 bg-slate-50/70 p-4">
                        <a href="{{ route('admin.permissions.index') }}"
                           class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Batal</a>
                        <button type="submit" :disabled="saving || name.trim() === ''"
                                class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="saving ? 'Menyimpan...' : 'Simpan Permission'"></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ===== Panduan Penamaan ===== --}}
            <div class="lg:col-span-1">
                <div class="sticky top-20 space-y-4">
                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                        <div class="border-b border-slate-100 px-5 py-3">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Panduan Penamaan</p>
                        </div>
                        <div class="space-y-4 p-5 text-xs">
                            <div>
                                <p class="mb-2 font-bold uppercase tracking-wide text-[10px] text-emerald-600">✓ Benar</p>
                                <div class="space-y-1.5 font-mono">
                                    <p class="rounded-lg bg-emerald-50 px-2.5 py-1.5 text-emerald-700 ring-1 ring-emerald-100">view pasien</p>
                                    <p class="rounded-lg bg-emerald-50 px-2.5 py-1.5 text-emerald-700 ring-1 ring-emerald-100">create jadwal</p>
                                    <p class="rounded-lg bg-emerald-50 px-2.5 py-1.5 text-emerald-700 ring-1 ring-emerald-100">manage apotek</p>
                                </div>
                            </div>
                            <div>
                                <p class="mb-2 font-bold uppercase tracking-wide text-[10px] text-rose-500">✗ Hindari</p>
                                <div class="space-y-1.5 font-mono">
                                    <p class="rounded-lg bg-rose-50 px-2.5 py-1.5 text-rose-600 line-through ring-1 ring-rose-100">ViewPasien</p>
                                    <p class="rounded-lg bg-rose-50 px-2.5 py-1.5 text-rose-600 line-through ring-1 ring-rose-100">view_pasien</p>
                                    <p class="rounded-lg bg-rose-50 px-2.5 py-1.5 text-rose-600 line-through ring-1 ring-rose-100">akses semua data</p>
                                </div>
                            </div>
                            <p class="leading-relaxed text-slate-500 border-t border-slate-100 pt-3">
                                Konsistensi penamaan memudahkan pencarian & pengelompokan permission saat dikonfigurasi ke role.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('permissionForm', () => ({
            name: @js(old('name')),
            saving: false,

            get isValidFormat() {
                return /^[a-z]+(\s[a-z0-9]+)*$/.test(this.name.trim());
            }
        }));
    });
</script>
@endsection
