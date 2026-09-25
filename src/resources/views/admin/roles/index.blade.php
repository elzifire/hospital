@extends('layouts.app')

@section('title', 'Manajemen Role')
@section('page-title', 'Manajemen Role')

@section('content')
@php
    $totalSystemPerms = \Spatie\Permission\Models\Permission::count();
    $rolesWithPerms = $roles->filter(fn ($r) => $r->permissions_count > 0)->count();
    $rolesWithoutPerms = $roles->count() - $rolesWithPerms;
    $maxPerms = max(1, $roles->max('permissions_count'));

    $gradients = [
        'from-sky-500 to-indigo-600',
        'from-emerald-500 to-teal-600',
        'from-violet-500 to-purple-600',
        'from-amber-500 to-orange-600',
        'from-rose-500 to-pink-600',
        'from-cyan-500 to-blue-600',
    ];
@endphp

<div x-data="roleManager()" class="space-y-6">

    {{-- ===== Header & Aksi ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-600 text-white shadow-md shadow-sky-600/20">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-slate-900">Manajemen Role & Hak Akses</h2>
                    <p class="text-xs text-slate-500">Kelola kelompok peran pengguna dan distribusi izin (permissions) pada aplikasi.</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.permissions.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-xs transition hover:bg-slate-50 hover:text-sky-600">
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                </svg>
                Daftar Permission
            </a>
            <a href="{{ route('admin.roles.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm shadow-sky-600/30 transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Role Baru
            </a>
        </div>
    </div>

    {{-- ===== Statistik Ringkas ===== --}}
    <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Total Role --}}
        <div class="relative overflow-hidden rounded-2xl bg-white p-4.5 shadow-xs ring-1 ring-slate-200 transition-all hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Role</p>
                    <p class="mt-1 text-2xl font-extrabold tabular-nums text-slate-900">{{ $roles->count() }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-[11px] text-slate-400"><span class="font-semibold text-sky-600">1</span> role sistem terproteksi</p>
        </div>

        {{-- Role Berizin --}}
        <div class="relative overflow-hidden rounded-2xl bg-white p-4.5 shadow-xs ring-1 ring-slate-200 transition-all hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Punya Permission</p>
                    <p class="mt-1 text-2xl font-extrabold tabular-nums text-emerald-600">{{ $rolesWithPerms }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-[11px] text-slate-400">Memiliki minimal 1 hak akses aktif</p>
        </div>

        {{-- Role Tanpa Izin --}}
        <div class="relative overflow-hidden rounded-2xl bg-white p-4.5 shadow-xs ring-1 ring-slate-200 transition-all hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Tanpa Permission</p>
                    <p class="mt-1 text-2xl font-extrabold tabular-nums {{ $rolesWithoutPerms > 0 ? 'text-amber-600' : 'text-slate-700' }}">{{ $rolesWithoutPerms }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-[11px] text-slate-400">Akses tertutup default</p>
        </div>

        {{-- Total Pengguna Terikat --}}
        <div class="relative overflow-hidden rounded-2xl bg-white p-4.5 shadow-xs ring-1 ring-slate-200 transition-all hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">User Terdaftar</p>
                    <p class="mt-1 text-2xl font-extrabold tabular-nums text-violet-600">{{ $totalUsersAssigned }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z" />
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-[11px] text-slate-400">Total akun yang terasosiasi</p>
        </div>
    </div>

    {{-- ===== Toolbar Pencarian & Filter ===== --}}
    <div class="flex flex-col gap-3 rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-200 lg:flex-row lg:items-center lg:justify-between">
        {{-- Search Input --}}
        <div class="relative w-full lg:max-w-md">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>
            <input x-model.debounce.250ms="search" type="text"
                   class="block w-full rounded-xl border-0 bg-slate-50/70 py-2.5 pl-10 pr-9 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-sky-500 transition"
                   placeholder="Cari nama role, permission, atau guard...">
            <button x-show="search" x-cloak @click="search = ''" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>

        {{-- Filter, Sorting, & View Mode Switcher --}}
        <div class="flex flex-wrap items-center gap-2.5">
            {{-- Filter Status --}}
            <select x-model="filter" class="rounded-xl border-0 bg-slate-50/70 py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                <option value="all">Semua Role ({{ $roles->count() }})</option>
                <option value="has_perms">Ada Permission ({{ $rolesWithPerms }})</option>
                <option value="no_perms">Tanpa Permission ({{ $rolesWithoutPerms }})</option>
                <option value="system">Role Sistem</option>
                <option value="custom">Role Kustom</option>
            </select>

            {{-- Sorting --}}
            <select x-model="sortBy" class="rounded-xl border-0 bg-slate-50/70 py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                <option value="az">Nama A–Z</option>
                <option value="za">Nama Z–A</option>
                <option value="perms_desc">Permission Terbanyak</option>
                <option value="perms_asc">Permission Tersedikit</option>
                <option value="users_desc">User Terbanyak</option>
            </select>

            {{-- View Switcher --}}
            <div class="inline-flex rounded-xl bg-slate-100 p-0.5 ring-1 ring-slate-200/70">
                <button type="button" @click="viewMode = 'grid'"
                        :class="viewMode === 'grid' ? 'bg-white text-sky-600 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                        class="flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold transition cursor-pointer" title="Tampilan Kartu">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" /></svg>
                    <span class="hidden sm:inline">Grid</span>
                </button>
                <button type="button" @click="viewMode = 'table'"
                        :class="viewMode === 'table' ? 'bg-white text-sky-600 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                        class="flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold transition cursor-pointer" title="Tampilan Tabel">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" /></svg>
                    <span class="hidden sm:inline">Tabel</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ===== Tampilan Utama (Grid vs Tabel) ===== --}}
    <div>
        {{-- VIEW MODE: GRID (KARTU INFORMATIF) --}}
        <div x-show="viewMode === 'grid'" class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
            <template x-for="r in paginatedRoles" :key="r.id">
                <div class="group flex flex-col justify-between rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-200 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:ring-sky-300">
                    <div>
                        {{-- Card Header --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl text-white shadow-sm"
                                     :class="'bg-linear-to-br ' + r.gradient">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="truncate text-base font-bold capitalize text-slate-900" x-text="r.displayName"></h3>
                                    <div class="mt-0.5 flex items-center gap-1.5">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 ring-inset"
                                              :class="badgeClass(r.name)">
                                            <span class="h-1 w-1 rounded-full" :class="dotClass(r.name)"></span>
                                            <span x-text="r.name"></span>
                                        </span>
                                        <span x-show="r.protected" class="inline-flex items-center gap-1 rounded-full bg-slate-900 px-2 py-0.5 text-[10px] font-bold text-white shadow-xs">
                                            <svg class="h-2.5 w-2.5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                            Sistem
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Users Count Badge --}}
                            <div class="flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 ring-1 ring-slate-200" title="Jumlah user yang menggunakan role ini">
                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                                <span class="tabular-nums" x-text="r.usersCount"></span>
                            </div>
                        </div>

                        {{-- Permissions Meter & Summary --}}
                        <div class="mt-4 rounded-xl bg-slate-50 p-3.5 ring-1 ring-slate-100">
                            <div class="mb-1.5 flex items-center justify-between text-xs">
                                <span class="font-medium text-slate-500">Cakupan Izin Akses</span>
                                <span class="font-bold tabular-nums text-slate-800">
                                    <span x-text="r.permsCount"></span> / {{ $totalSystemPerms }}
                                    <span class="text-[10px] font-normal text-slate-400" x-text="'(' + Math.round((r.permsCount / {{ max(1, $totalSystemPerms) }}) * 100) + '%)'"></span>
                                </span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                                <div class="h-full rounded-full bg-linear-to-r from-sky-500 to-indigo-600 transition-all duration-500"
                                     :style="'width:' + Math.min(100, Math.max(4, (r.permsCount / {{ max(1, $totalSystemPerms) }}) * 100)) + '%'"></div>
                            </div>

                            {{-- Permission Chips Preview --}}
                            <div class="mt-3">
                                <template x-if="r.perms.length > 0">
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="perm in r.perms.slice(0, 4)" :key="perm">
                                            <span class="inline-flex items-center gap-1 rounded-md bg-white px-2 py-0.5 text-[10px] font-medium text-slate-700 ring-1 ring-slate-200/80">
                                                <svg class="h-2.5 w-2.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                                <span x-text="perm"></span>
                                            </span>
                                        </template>
                                        <span x-show="r.perms.length > 4"
                                              @click="openModal(r)"
                                              class="cursor-pointer inline-flex items-center rounded-md bg-sky-100/70 px-2 py-0.5 text-[10px] font-bold text-sky-700 transition hover:bg-sky-200">
                                            +<span x-text="r.perms.length - 4"></span> lainnya
                                        </span>
                                    </div>
                                </template>
                                <template x-if="r.perms.length === 0">
                                    <p class="text-[11px] italic text-slate-400">Belum ada permission yang diberikan.</p>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Card Footer --}}
                    <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-3 text-xs">
                        <span class="text-[11px] text-slate-400">
                            Guard: <code class="rounded bg-slate-100 px-1 font-mono text-[10px] text-slate-600" x-text="r.guard"></code>
                        </span>

                        <div class="flex items-center gap-1">
                            <button type="button" @click="openModal(r)"
                                    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 cursor-pointer"
                                    title="Lihat semua permission">
                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                Detail
                            </button>
                            <a :href="r.editUrl"
                               class="inline-flex items-center gap-1 rounded-lg bg-sky-50 px-2.5 py-1.5 text-xs font-bold text-sky-700 ring-1 ring-sky-200/70 transition hover:bg-sky-100 hover:text-sky-800">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                Edit
                            </a>
                            <button type="button" @click="askDelete(r)"
                                    :disabled="r.protected"
                                    :class="r.protected ? 'opacity-30 cursor-not-allowed text-slate-300' : 'text-slate-400 hover:bg-rose-50 hover:text-rose-600 cursor-pointer'"
                                    class="rounded-lg p-1.5 transition-colors"
                                    :title="r.protected ? 'Role sistem tidak dapat dihapus' : 'Hapus Role'">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- VIEW MODE: TABEL LIST --}}
        <div x-show="viewMode === 'table'" class="overflow-hidden rounded-2xl bg-white shadow-xs ring-1 ring-slate-200">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-5 py-3.5">Role</th>
                            <th class="px-4 py-3.5">Guard</th>
                            <th class="px-4 py-3.5 text-center">User Terikat</th>
                            <th class="px-4 py-3.5">Cakupan Permission</th>
                            <th class="px-4 py-3.5">Preview Permission</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <template x-for="r in paginatedRoles" :key="r.id">
                            <tr class="transition-colors hover:bg-sky-50/40">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl text-white shadow-xs"
                                             :class="'bg-linear-to-br ' + r.gradient">
                                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-bold capitalize text-slate-900" x-text="r.displayName"></span>
                                                <span x-show="r.protected" class="inline-flex items-center gap-0.5 rounded-full bg-slate-900 px-1.5 py-0.5 text-[9px] font-bold text-white">
                                                    Sistem
                                                </span>
                                            </div>
                                            <span class="text-[11px] text-slate-400">Dibuat <span x-text="r.createdAt"></span></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] text-slate-600" x-text="r.guard"></code>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">
                                        <span class="tabular-nums" x-text="r.usersCount"></span> user
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="w-36">
                                        <div class="mb-1 flex items-center justify-between text-[11px]">
                                            <span class="font-bold text-slate-800" x-text="r.permsCount"></span>
                                            <span class="text-slate-400" x-text="'/ ' + {{ $totalSystemPerms }}"></span>
                                        </div>
                                        <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full bg-sky-500" :style="'width:' + Math.min(100, (r.permsCount / {{ max(1, $totalSystemPerms) }}) * 100) + '%'"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex max-w-xs flex-wrap gap-1">
                                        <template x-for="perm in r.perms.slice(0, 3)" :key="perm">
                                            <span class="inline-flex items-center gap-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-700">
                                                <span x-text="perm"></span>
                                            </span>
                                        </template>
                                        <span x-show="r.perms.length > 3" @click="openModal(r)" class="cursor-pointer text-[10px] font-bold text-sky-600 hover:underline">
                                            +<span x-text="r.perms.length - 3"></span> lagi
                                        </span>
                                        <span x-show="r.perms.length === 0" class="text-[11px] italic text-slate-400">Tidak ada</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" @click="openModal(r)"
                                                class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 cursor-pointer" title="Lihat Detail">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                        </button>
                                        <a :href="r.editUrl"
                                           class="rounded-lg p-1.5 text-sky-600 hover:bg-sky-50" title="Edit Role">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                        </a>
                                        <button type="button" @click="askDelete(r)"
                                                :disabled="r.protected"
                                                :class="r.protected ? 'opacity-25 cursor-not-allowed' : 'text-slate-400 hover:bg-rose-50 hover:text-rose-600 cursor-pointer'"
                                                class="rounded-lg p-1.5" title="Hapus Role">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Kosong / Filter Tidak Menemukan Hasil --}}
        <div x-show="filteredRoles.length === 0" x-cloak class="rounded-2xl bg-white p-12 text-center shadow-xs ring-1 ring-slate-200">
            <div class="mx-auto flex max-w-sm flex-col items-center">
                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 ring-8 ring-slate-50">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">Tidak ada role ditemukan</h3>
                <p class="mt-1 text-xs text-slate-500">Tidak ada data yang cocok dengan kriteria pencarian atau filter yang dipilih.</p>
                <button type="button" @click="search = ''; filter = 'all'"
                        class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-800 cursor-pointer">
                    Reset Filter
                </button>
            </div>
        </div>

        {{-- Pagination --}}
        <div x-show="filteredRoles.length > 0" class="mt-5 flex flex-col items-center justify-between gap-3 rounded-2xl bg-white px-5 py-3.5 shadow-xs ring-1 ring-slate-200 sm:flex-row">
            <p class="text-xs text-slate-500">
                Menampilkan <span class="font-bold text-slate-800" x-text="startIndex + 1"></span>–<span class="font-bold text-slate-800" x-text="endIndex"></span> dari <span class="font-bold text-slate-800" x-text="filteredRoles.length"></span> role
            </p>
            <div class="flex items-center gap-1">
                <button type="button" @click="prevPage" :disabled="currentPage === 1"
                        class="rounded-lg border border-slate-200 bg-white p-2 text-slate-600 shadow-xs transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 cursor-pointer">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                </button>
                <template x-for="page in totalPages" :key="page">
                    <button type="button" @click="currentPage = page"
                            class="h-8 min-w-8 rounded-lg px-2 text-xs font-bold tabular-nums transition cursor-pointer"
                            :class="currentPage === page ? 'bg-sky-600 text-white shadow-xs' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                            x-text="page"></button>
                </template>
                <button type="button" @click="nextPage" :disabled="currentPage === totalPages"
                        class="rounded-lg border border-slate-200 bg-white p-2 text-slate-600 shadow-xs transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 cursor-pointer">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- ===== MODAL DETAIL PERMISSION ===== --}}
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         role="dialog" aria-modal="true">
        {{-- Backdrop --}}
        <div x-show="modalOpen"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="modalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>

        {{-- Modal Dialog --}}
        <div x-show="modalOpen"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             class="relative flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200">
            
            {{-- Modal Header --}}
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl text-white shadow-xs"
                         :class="'bg-linear-to-br ' + (activeRole?.gradient || 'from-sky-500 to-indigo-600')">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold capitalize text-slate-900" x-text="activeRole?.displayName"></h3>
                            <span x-show="activeRole?.protected" class="rounded-full bg-slate-900 px-2 py-0.5 text-[9px] font-bold text-white">Sistem</span>
                        </div>
                        <p class="text-xs text-slate-400">
                            Memiliki <span class="font-bold text-sky-600" x-text="activeRole?.permsCount"></span> dari {{ $totalSystemPerms }} permission · Digunakan oleh <span class="font-semibold text-slate-700" x-text="activeRole?.usersCount"></span> user
                        </p>
                    </div>
                </div>
                <button type="button" @click="modalOpen = false" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 cursor-pointer">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            {{-- Modal Body (Searchable Permissions List) --}}
            <div class="flex-1 overflow-y-auto p-6 space-y-4">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    </div>
                    <input x-model="modalSearch" type="text"
                           placeholder="Filter daftar permission role ini..."
                           class="block w-full rounded-xl border-0 bg-slate-50 py-2 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-sky-500">
                </div>

                <template x-if="modalFilteredPerms.length > 0">
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <template x-for="perm in modalFilteredPerms" :key="perm">
                            <div class="flex items-center gap-2.5 rounded-xl bg-slate-50/80 p-3 ring-1 ring-slate-100 transition hover:bg-white hover:ring-sky-200">
                                <div class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-bold text-slate-800" x-text="perm"></p>
                                    <p class="text-[10px] text-slate-400 capitalize" x-text="perm.replace(/[-_.]/g, ' ')"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="activeRole?.perms.length === 0">
                    <div class="py-8 text-center">
                        <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-amber-50 text-amber-500">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                        </div>
                        <p class="text-xs font-bold text-slate-700">Role ini belum memiliki permission</p>
                        <p class="text-[11px] text-slate-400">Klik "Edit Role Ini" untuk menambahkan hak akses.</p>
                    </div>
                </template>

                <template x-if="activeRole?.perms.length > 0 && modalFilteredPerms.length === 0">
                    <p class="py-6 text-center text-xs text-slate-400">Tidak ada permission yang cocok dengan kata kunci.</p>
                </template>
            </div>

            {{-- Modal Footer --}}
            <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50/70 px-6 py-3.5">
                <span class="text-[11px] text-slate-400">Guard: <code class="font-mono text-slate-600" x-text="activeRole?.guard"></code></span>
                <div class="flex items-center gap-2">
                    <button type="button" @click="modalOpen = false" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 cursor-pointer">Tutup</button>
                    <a :href="activeRole?.editUrl" class="inline-flex items-center gap-1.5 rounded-xl bg-sky-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-sky-700">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                        Edit Role Ini
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Info Box RBAC ===== --}}
    <div class="flex items-start gap-3.5 rounded-2xl bg-sky-50/80 p-4.5 text-xs leading-relaxed text-sky-900 ring-1 ring-sky-200/70">
        <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg bg-sky-600 text-white">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
            </svg>
        </div>
        <div>
            <p class="font-bold text-sky-950">Konsep Role-Based Access Control (RBAC):</p>
            <p class="mt-0.5 text-sky-800">
                Setiap pengguna diberikan 1 atau lebih <strong>Role</strong>, dan tiap role memuat daftar <strong>Permission</strong>. Hak akses fitur diatur secara dinamis tanpa perlu mengubah kode sumber aplikasi.
            </p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('roleManager', () => ({
            search: '',
            filter: 'all',
            sortBy: 'az',
            viewMode: 'grid',
            currentPage: 1,
            perPage: 9,
            modalOpen: false,
            activeRole: null,
            modalSearch: '',

            roles: [
                @foreach ($roles as $role)
                {
                    id: {{ $role->id }},
                    name: @js(strtolower($role->name)),
                    displayName: @js($role->name),
                    guard: @js($role->guard_name),
                    gradient: "{{ $gradients[$loop->index % count($gradients)] }}",
                    perms: @js($role->permissions->pluck('name')->values()),
                    permsCount: {{ $role->permissions_count }},
                    usersCount: {{ $role->users_count }},
                    protected: {{ strtolower($role->name) === 'superadmin' ? 'true' : 'false' }},
                    createdAt: "{{ $role->created_at ? $role->created_at->translatedFormat('d M Y') : '-' }}",
                    editUrl: "{{ route('admin.roles.edit', $role->id) }}",
                    deleteUrl: "{{ route('admin.roles.destroy', $role->id) }}",
                }{{ ! $loop->last ? ',' : '' }}
                @endforeach
            ],

            get filteredRoles() {
                let result = this.roles.filter(r => {
                    const q = this.search.toLowerCase().trim();
                    const matchSearch = !q
                        || r.name.includes(q)
                        || r.guard.includes(q)
                        || r.perms.some(p => p.toLowerCase().includes(q));

                    const matchFilter = this.filter === 'all'
                        || (this.filter === 'has_perms' && r.permsCount > 0)
                        || (this.filter === 'no_perms' && r.permsCount === 0)
                        || (this.filter === 'system' && r.protected)
                        || (this.filter === 'custom' && !r.protected);

                    return matchSearch && matchFilter;
                });

                if (this.sortBy === 'az')         result.sort((a, b) => a.displayName.localeCompare(b.displayName));
                if (this.sortBy === 'za')         result.sort((a, b) => b.displayName.localeCompare(a.displayName));
                if (this.sortBy === 'perms_desc') result.sort((a, b) => b.permsCount - a.permsCount);
                if (this.sortBy === 'perms_asc')  result.sort((a, b) => a.permsCount - b.permsCount);
                if (this.sortBy === 'users_desc') result.sort((a, b) => b.usersCount - a.usersCount);

                return result;
            },

            get totalPages()     { return Math.max(1, Math.ceil(this.filteredRoles.length / this.perPage)); },
            get startIndex()     { return (this.currentPage - 1) * this.perPage; },
            get endIndex()       { return Math.min(this.startIndex + this.perPage, this.filteredRoles.length); },
            get paginatedRoles() { return this.filteredRoles.slice(this.startIndex, this.endIndex); },

            prevPage() { if (this.currentPage > 1) this.currentPage--; },
            nextPage() { if (this.currentPage < this.totalPages) this.currentPage++; },

            openModal(role) {
                this.activeRole = role;
                this.modalSearch = '';
                this.modalOpen = true;
            },

            get modalFilteredPerms() {
                if (!this.activeRole) return [];
                if (!this.modalSearch) return this.activeRole.perms;
                const q = this.modalSearch.toLowerCase().trim();
                return this.activeRole.perms.filter(p => p.toLowerCase().includes(q));
            },

            badgeClass(name) {
                return {
                    superadmin: 'bg-rose-50 text-rose-700 ring-rose-600/20',
                    admin:      'bg-amber-50 text-amber-700 ring-amber-600/20',
                    user:       'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                    poli:       'bg-violet-50 text-violet-700 ring-violet-600/20',
                }[name] ?? 'bg-sky-50 text-sky-700 ring-sky-600/20';
            },
            dotClass(name) {
                return { superadmin: 'bg-rose-500', admin: 'bg-amber-500', user: 'bg-emerald-500', poli: 'bg-violet-500' }[name] ?? 'bg-sky-500';
            },

            askDelete(role) {
                if (role.protected) {
                    infoDialog(
                        'Role Terproteksi',
                        `Role <strong class="capitalize">${role.displayName}</strong> adalah role inti sistem dan <strong>tidak dapat dihapus</strong>.`,
                        'warning'
                    );
                    return;
                }

                const userWarning = role.usersCount > 0
                    ? `<div class="flex items-start gap-2.5 rounded-xl bg-amber-50 p-3.5 mt-3 ring-1 ring-amber-200 text-left">
                           <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                           <p class="text-xs font-medium text-amber-800"><strong>${role.usersCount} user</strong> saat ini memakai role ini dan akan kehilangan hak aksesnya!</p>
                       </div>`
                    : '';

                confirmSubmit(role.deleteUrl, {
                    title: 'Hapus Role?',
                    html: `<div class="text-left text-xs mt-2">
                              <p class="text-slate-600">Apakah Anda yakin ingin menghapus role <strong class="capitalize text-slate-900">${role.displayName}</strong>?</p>
                              ${userWarning}
                              <p class="mt-3 rounded-lg bg-slate-50 p-2 text-[11px] text-slate-500 ring-1 ring-slate-200">Tindakan ini permanen dan tidak dapat dibatalkan.</p>
                           </div>`,
                    confirmText: 'Ya, hapus role',
                });
            }
        }));
    });
</script>
@endsection

