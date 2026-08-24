@extends('layouts.app')

@section('title', 'Tambah Role')
@section('page-title', 'Tambah Role')

@section('content')
@php
    $groups = [];
    foreach ($permissions as $permission) {
        $parts = preg_split('/[\s._-]+/', $permission->name);
        $group = count($parts) > 1 ? ucfirst($parts[0]) : 'Lainnya';
        $groups[$group][] = $permission;
    }
    $groups = collect($groups)->sortKeys();
@endphp

<div x-data="roleForm()" class="mx-auto max-w-7xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.roles.index') }}" class="rounded transition hover:text-sky-600">Manajemen Role</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">Tambah Baru</span>
        </nav>
        <h2 class="text-xl font-bold tracking-tight text-slate-900">Tambah Role Baru</h2>
        <p class="mt-0.5 text-sm text-slate-500">Tentukan nama role dan pilih permission yang bisa diakses.</p>
    </div>

    <form action="{{ route('admin.roles.store') }}" method="POST" @submit="saving = true" x-cloak>
        @csrf

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- ===== Form Utama ===== --}}
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="border-b border-slate-100 p-6">
                        <h3 class="text-base font-bold text-slate-900">Informasi Role</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Nama role akan ditampilkan di seluruh sistem.</p>
                    </div>
                    <div class="p-6">
                        <label for="name" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Nama Role <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" x-model="name" required placeholder="cth. Dokter, Kasir, Perawat"
                               class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('name') ring-rose-300 focus:ring-rose-500 @enderror">
                        @error('name')
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-rose-600">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9 .75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                                {{ $message }}
                            </p>
                        @enderror
                        <p class="mt-2 text-xs text-slate-400">Gunakan satu kata tanpa spasi, misal: <code class="rounded bg-slate-100 px-1 font-mono">kasir</code>, <code class="rounded bg-slate-100 px-1 font-mono">dokter_gigi</code>.</p>
                    </div>
                </div>

                {{-- Permission Picker --}}
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="flex flex-col gap-4 border-b border-slate-100 p-6 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Hak Akses (Permissions)</h3>
                            <p class="mt-0.5 text-sm text-slate-500">Centang modul yang boleh diakses role ini.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="selectAll"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-sky-50 px-3 py-2 text-xs font-bold text-sky-700 ring-1 ring-sky-200 transition hover:bg-sky-100">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                Pilih Semua
                            </button>
                            <button type="button" @click="clearAll"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-100">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                Kosongkan
                            </button>
                        </div>
                    </div>

                    {{-- Search permissions --}}
                    <div class="border-b border-slate-100 bg-slate-50/60 px-6 py-3">
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                            </div>
                            <input x-model.debounce.200ms="search" type="text"
                                   class="block w-full rounded-xl border-0 bg-white py-2 pl-9 pr-3 text-xs text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500"
                                   placeholder="Cari permission...">
                        </div>
                    </div>

                    @error('permissions.*')
                        <p class="mx-6 mt-4 flex items-center gap-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror

                    <div class="divide-y divide-slate-100">
                        @foreach ($groups as $groupName => $groupPerms)
                            <div class="p-6" x-show="groupVisible('{{ strtolower($groupName) }}')" x-cloak>
                                <div class="mb-4 flex items-center justify-between">
                                    <div class="flex items-center gap-2.5">
                                        <span class="rounded-lg bg-slate-800 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest text-white">{{ $groupName }}</span>
                                        <span class="text-[11px] font-medium tabular-nums text-slate-400">
                                            <span x-text="groupSelectedCount('{{ strtolower($groupName) }}')"></span>/{{ count($groupPerms) }} dipilih
                                        </span>
                                    </div>
                                    <button type="button" @click="toggleGroup('{{ strtolower($groupName) }}')"
                                            class="text-[11px] font-bold text-sky-600 transition hover:text-sky-700"
                                            x-text="groupAllSelected('{{ strtolower($groupName) }}') ? 'Batalkan grup' : 'Pilih grup'"></button>
                                </div>

                                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 xl:grid-cols-3">
                                    @foreach ($groupPerms as $permission)
                                        @php $key = strtolower($groupName . '|' . $permission->name); @endphp
                                        <label class="group relative flex cursor-pointer items-start gap-3 rounded-xl p-3.5 transition-all duration-200 perm-tile"
                                               :class="selected.includes(@js($permission->name))
                                                   ? 'bg-sky-50 ring-2 ring-sky-500'
                                                   : 'bg-white ring-1 ring-slate-200 hover:ring-sky-300'"
                                               :data-key="'{{ strtolower($groupName) }}|{{ strtolower($permission->name) }}'"
                                               x-show="matchesSearch({{ strtolower($groupName) }}, @js($permission->name))">
                                            <div class="flex h-5 items-center pt-0.5">
                                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" x-model="selected"
                                                       class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500 transition-colors">
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-slate-800">{{ $permission->name }}</p>
                                                <p class="mt-0.5 text-[11px] leading-snug text-slate-400">Izin untuk {{ str_replace(['.', '-', '_'], ' ', $permission->name) }}</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        {{-- No search result --}}
                        <div x-show="!anyVisible()" x-cloak class="px-6 py-12 text-center">
                            <p class="text-sm text-slate-400">Tidak ada permission yang cocok dengan pencarian <strong x-text="'&quot;' + search + '&quot;'"></strong>.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== Preview Sidebar (sticky) ===== --}}
            <div class="lg:col-span-1">
                <div class="sticky top-20 space-y-4">
                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                        <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-3">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Pratinjau Langsung</p>
                        </div>
                        <div class="flex items-center gap-3.5 px-5 py-4">
                            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-sky-500 to-indigo-500 text-white shadow-sm">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-base font-bold capitalize text-slate-900" x-text="name || 'nama-role'"></p>
                                <p class="text-xs text-slate-400"><span class="font-bold tabular-nums text-sky-600" x-text="selected.length"></span> dari {{ $permissions->count() }} permission</p>
                            </div>
                        </div>
                        <div class="px-5 pb-4">
                            <div class="max-h-44 overflow-y-auto rounded-xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                <template x-if="selected.length === 0">
                                    <p class="py-2 text-center text-xs italic text-slate-400">Belum ada permission dipilih — role ini tidak punya akses apa pun.</p>
                                </template>
                                <template x-for="perm in selected" :key="perm">
                                    <span class="mr-1.5 mb-1.5 inline-flex items-center gap-1 rounded-md bg-white px-2 py-1 text-[11px] font-medium text-slate-700 shadow-xs ring-1 ring-slate-200">
                                        <svg class="h-2.5 w-2.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="3.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                        <span x-text="perm"></span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Tips RBAC --}}
                    <div class="rounded-xl bg-white p-4 text-xs leading-relaxed text-slate-500 shadow-xs ring-1 ring-slate-200">
                        <p class="mb-1.5 flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <svg class="h-3.5 w-3.5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                            Cara Kerja Role
                        </p>
                        Role menjadi "wadah" kumpulan permission. User yang memakai role ini otomatis mendapat semua akses yang dicentang di sini.
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('admin.roles.index') }}"
                           class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Batal</a>
                        <button type="submit" :disabled="saving"
                                class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                            <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="saving ? 'Menyimpan...' : 'Simpan Role'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('roleForm', () => ({
            name: @js(old('name')),
            saving: false,
            search: '',
            selected: @js(old('permissions', [])),
            groups: @js($groups->map(fn ($perms, $g) => [
                'key'   => strtolower($g),
                'perms' => $perms->pluck('name')->all(),
            ])->values()),

            get isDirty() { return this.name !== '' || this.selected.length > 0; },

            matchesSearch(groupKey, permName) {
                if (!this.search) return true;
                return permName.toLowerCase().includes(this.search.toLowerCase())
                    || groupKey.includes(this.search.toLowerCase());
            },
            groupVisible(groupKey) {
                return this.groups.find(g => g.key === groupKey)?.perms.some(p => this.matchesSearch(groupKey, p)) ?? false;
            },
            anyVisible() {
                return this.groups.some(g => this.groupVisible(g.key));
            },
            groupSelectedCount(groupKey) {
                const perms = this.groups.find(g => g.key === groupKey)?.perms ?? [];
                return perms.filter(p => this.selected.includes(p)).length;
            },
            groupAllSelected(groupKey) {
                const perms = this.groups.find(g => g.key === groupKey)?.perms ?? [];
                return perms.length > 0 && perms.every(p => this.selected.includes(p));
            },
            toggleGroup(groupKey) {
                const perms = this.groups.find(g => g.key === groupKey)?.perms ?? [];
                if (this.groupAllSelected(groupKey)) {
                    this.selected = this.selected.filter(p => !perms.includes(p));
                } else {
                    perms.forEach(p => { if (!this.selected.includes(p)) this.selected.push(p); });
                }
            },
            selectAll()   { this.selected = this.groups.flatMap(g => g.perms); },
            clearAll()    { this.selected = []; },

            init() {
                window.addEventListener('beforeunload', (e) => {
                    if (this.isDirty && !this.saving) {
                        e.preventDefault();
                        e.returnValue = '';
                    }
                });
            }
        }));
    });
</script>
@endsection
