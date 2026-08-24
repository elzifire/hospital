@extends('layouts.app')

@section('title', 'Manage Roles')
@section('page-title', 'Manage Roles')

@section('content')
@php
    $rolesWithPerms = $roles->filter(fn ($r) => $r->permissions_count > 0)->count();
    $rolesWithoutPerms = $roles->count() - $rolesWithPerms;
    $totalPermsGranted = $roles->sum('permissions_count');
    $maxPerms = max(1, $roles->max('permissions_count'));

    $roleColor = fn ($name) => match (strtolower($name)) {
        'superadmin' => ['bg' => 'bg-rose-50',   'text' => 'text-rose-600',   'ring' => 'ring-rose-600/20',   'dot' => 'bg-rose-500'],
        'admin'      => ['bg' => 'bg-amber-50',  'text' => 'text-amber-600',  'ring' => 'ring-amber-600/20',  'dot' => 'bg-amber-500'],
        'user'       => ['bg' => 'bg-emerald-50','text' => 'text-emerald-600','ring' => 'ring-emerald-600/20','dot' => 'bg-emerald-500'],
        default      => ['bg' => 'bg-sky-50',    'text' => 'text-sky-600',    'ring' => 'ring-sky-600/20',    'dot' => 'bg-sky-500'],
    };
@endphp

<div x-data="roleTable()" class="space-y-6">

    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Manajemen Role</h2>
            <p class="mt-0.5 text-sm text-slate-500">Role adalah kumpulan permission yang menentukan hak akses pengguna.</p>
        </div>
        <a href="{{ route('admin.roles.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Tambah Role
        </a>
    </div>

    {{-- ===== Statistik Ringkas ===== --}}
    @php
        $stats = [
            ['label' => 'Total Role',       'value' => $roles->count(),     'color' => 'sky'],
            ['label' => 'Punya Permission', 'value' => $rolesWithPerms,     'color' => 'emerald'],
            ['label' => 'Tanpa Permission', 'value' => $rolesWithoutPerms,  'color' => 'amber'],
            ['label' => 'User Terdaftar',   'value' => $totalUsersAssigned, 'color' => 'violet'],
        ];
        $statStyles = [
            'sky'     => ['bg-sky-50',    'text-sky-600',    'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z'],
            'emerald' => ['bg-emerald-50','text-emerald-600','m4.5 12.75 6 6 9-13.5'],
            'amber'   => ['bg-amber-50',  'text-amber-600',  'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
            'violet'  => ['bg-violet-50', 'text-violet-600', 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z'],
        ];
    @endphp
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($stats as $stat)
            <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-xs ring-1 ring-slate-200 transition-all hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg {{ $statStyles[$stat['color']][0] }} {{ $statStyles[$stat['color']][1] }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $statStyles[$stat['color']][2] }}" /></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-lg font-extrabold leading-tight tabular-nums text-slate-900">{{ $stat['value'] }}</p>
                    <p class="truncate text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ $stat['label'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== Tabel ===== --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">

        {{-- Toolbar --}}
        <div class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/60 p-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="relative w-full lg:max-w-sm">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                </div>
                <input x-model.debounce.300ms="search" type="text"
                       class="block w-full rounded-xl border-0 bg-white py-2.5 pl-10 pr-9 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition"
                       placeholder="Cari role...">
                <button x-show="search" x-cloak @click="search = ''" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <select x-model="filter" class="rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                    <option value="all">Semua Role</option>
                    <option value="has_perms">Ada Permission</option>
                    <option value="no_perms">Tanpa Permission</option>
                </select>
                <select x-model="sortBy" class="rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                    <option value="az">Nama A–Z</option>
                    <option value="perms_desc">Permission Terbanyak</option>
                    <option value="users_desc">User Terbanyak</option>
                </select>
            </div>
        </div>

        {{-- Rows --}}
        <ul class="divide-y divide-slate-100">
            <template x-for="r in paginatedRoles" :key="r.id">
                <li class="group transition-colors hover:bg-sky-50/30" :class="expanded === r.id ? 'bg-sky-50/40' : ''">
                    <div class="flex cursor-pointer items-center gap-4 px-6 py-4" @click="toggleExpand(r.id)">
                        {{-- Icon --}}
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl"
                             :class="'bg-linear-to-br ' + r.gradient + ' text-white shadow-sm'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                        </div>

                        {{-- Nama & meta --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="truncate text-sm font-bold capitalize text-slate-900" x-text="r.name"></span>
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 ring-inset"
                                      :class="badgeClass(r.name)">
                                    <span class="h-1 w-1 rounded-full" :class="dotClass(r.name)"></span>
                                    <span x-text="r.name"></span>
                                </span>
                                <span x-show="r.protected" class="inline-flex items-center gap-1 rounded-full bg-slate-800 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">
                                    <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                    Sistem
                                </span>
                            </div>
                            <p class="mt-0.5 truncate text-xs text-slate-400">
                                Dibuat <span x-text="r.createdAt"></span> · guard <code class="rounded bg-slate-100 px-1 font-mono text-[10px]" x-text="r.guard"></code>
                            </p>
                        </div>

                        {{-- Permissions progress --}}
                        <div class="hidden w-44 md:block">
                            <div class="mb-1 flex items-center justify-between text-[11px]">
                                <span class="font-semibold text-slate-500">Permissions</span>
                                <span class="font-bold tabular-nums text-slate-700" x-text="r.permsCount"></span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-linear-to-r from-sky-400 to-indigo-500 transition-all duration-500" :style="'width:' + Math.min(100, (r.permsCount / {{ $maxPerms }}) * 100) + '%'"></div>
                            </div>
                        </div>

                        {{-- Users --}}
                        <div class="hidden items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 sm:flex">
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z" /></svg>
                            <span class="text-xs font-bold tabular-nums text-slate-600" x-text="r.usersCount"></span>
                            <span class="text-[10px] font-medium text-slate-400">user</span>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-1.5" @click.stop>
                            <a :href="r.editUrl" title="Edit Role"
                               class="rounded-lg p-2 text-slate-400 transition-all hover:bg-sky-50 hover:text-sky-600">
                                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                            </a>
                            <button type="button" title="Hapus Role" @click="askDelete(r)"
                                    class="rounded-lg p-2 transition-all"
                                    :class="r.protected ? 'cursor-not-allowed text-slate-200 hover:bg-transparent' : 'text-slate-400 hover:bg-rose-50 hover:text-rose-600'">
                                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                            </button>
                            <button type="button" class="rounded-lg p-2 text-slate-300 transition-colors hover:text-slate-500" title="Detail">
                                <svg class="h-4 w-4 transition-transform duration-200" :class="expanded === r.id ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Expanded detail --}}
                    <div x-show="expanded === r.id" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="border-t border-sky-100/70 bg-sky-50/40 px-6 py-5">
                        <p class="mb-2.5 text-[11px] font-bold uppercase tracking-wider text-sky-700">Detail Permission (<span x-text="r.permsCount"></span>)</p>
                        <template x-if="r.perms.length > 0">
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="perm in r.perms" :key="perm">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-white px-2.5 py-1 text-xs font-medium text-slate-700 shadow-xs ring-1 ring-slate-200">
                                        <svg class="h-3 w-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                        <span x-text="perm"></span>
                                    </span>
                                </template>
                            </div>
                        </template>
                        <template x-if="r.perms.length === 0">
                            <p class="text-sm italic text-slate-400">Role ini belum memiliki permission — semua akses ditolak kecuali yang diizinkan default.</p>
                        </template>
                    </div>
                </li>
            </template>

            {{-- Empty --}}
            <li x-show="filteredRoles.length === 0" x-cloak class="px-6 py-16 text-center">
                <div class="mx-auto flex max-w-sm flex-col items-center">
                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 ring-8 ring-slate-50">
                        <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">Tidak ada role ditemukan</h3>
                    <p class="mt-1 text-sm text-slate-500">Ubah kata kunci atau filter, atau buat role baru.</p>
                    <button type="button" @click="search = ''; filter = 'all'" class="mt-5 rounded-lg bg-slate-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-700">Reset Filter</button>
                </div>
            </li>
        </ul>

        {{-- Pagination --}}
        <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:flex-row" x-show="filteredRoles.length > 0" x-cloak>
            <p class="text-xs font-medium text-slate-500">
                Menampilkan <span class="font-bold text-slate-800" x-text="startIndex + 1"></span>–<span class="font-bold text-slate-800" x-text="endIndex"></span>
                dari <span class="font-bold text-slate-800" x-text="filteredRoles.length"></span> role
            </p>
            <div class="flex items-center gap-1">
                <button type="button" @click="prevPage" :disabled="currentPage === 1" class="rounded-lg bg-white p-2 text-slate-500 shadow-xs ring-1 ring-slate-200 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                </button>
                <template x-for="page in totalPages" :key="page">
                    <button type="button" @click="currentPage = page" class="h-8 min-w-8 rounded-lg px-2 text-xs font-bold tabular-nums transition"
                            :class="currentPage === page ? 'bg-sky-600 text-white shadow-sm' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'" x-text="page"></button>
                </template>
                <button type="button" @click="nextPage" :disabled="currentPage === totalPages" class="rounded-lg bg-white p-2 text-slate-500 shadow-xs ring-1 ring-slate-200 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Info strip --}}
    <div class="flex items-start gap-3 rounded-xl bg-sky-50/70 p-4 text-xs leading-relaxed text-sky-800 ring-1 ring-sky-100">
        <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-sky-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
        </svg>
        <p><strong>Tips:</strong> Klik baris untuk melihat detail permission yang dimiliki setiap role. Role <strong>Superadmin</strong> berstatus <em>terproteksi sistem</em> dan tidak dapat dihapus untuk mencegah kehilangan akses administratif.</p>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        const GRADIENTS = [
            'from-sky-500 to-indigo-500',
            'from-emerald-500 to-teal-500',
            'from-violet-500 to-purple-500',
            'from-rose-500 to-pink-500',
            'from-amber-500 to-orange-500',
        ];

        Alpine.data('roleTable', () => ({
            search: '',
            filter: 'all',
            sortBy: 'az',
            currentPage: 1,
            perPage: 10,
            expanded: null,

            roles: [
                @php $gradients = ['from-sky-500 to-indigo-500', 'from-emerald-500 to-teal-500', 'from-violet-500 to-purple-500', 'from-rose-500 to-pink-500', 'from-amber-500 to-orange-500']; @endphp
                @foreach ($roles as $role)
                {
                    id: {{ $role->id }},
                    name: @js(strtolower($role->name)),
                    displayName: @js($role->name),
                    guard: @js($role->guard_name),
                    gradient: "{{ $gradients[$loop->index % count($gradients)] }}",
                    perms: @js($role->permissions->pluck('name')),
                    permsCount: {{ $role->permissions_count }},
                    usersCount: {{ $role->users_count }},
                    protected: {{ strtolower($role->name) === 'superadmin' ? 'true' : 'false' }},
                    createdAt: "{{ $role->created_at->translatedFormat('d M Y') }}",
                    editUrl: "{{ route('admin.roles.edit', $role->id) }}",
                    deleteUrl: "{{ route('admin.roles.destroy', $role->id) }}",
                }{{ ! $loop->last ? ',' : '' }}
                @endforeach
            ],

            get filteredRoles() {
                let result = this.roles.filter(r => {
                    const matchSearch = r.name.includes(this.search.toLowerCase());
                    const matchFilter = this.filter === 'all'
                        || (this.filter === 'has_perms' && r.permsCount > 0)
                        || (this.filter === 'no_perms' && r.permsCount === 0);
                    return matchSearch && matchFilter;
                });

                if (this.sortBy === 'az')          result.sort((a, b) => a.name.localeCompare(b.name));
                if (this.sortBy === 'perms_desc')  result.sort((a, b) => b.permsCount - a.permsCount);
                if (this.sortBy === 'users_desc')  result.sort((a, b) => b.usersCount - a.usersCount);

                return result;
            },

            get totalPages()     { return Math.max(1, Math.ceil(this.filteredRoles.length / this.perPage)); },
            get startIndex()     { return (this.currentPage - 1) * this.perPage; },
            get endIndex()       { return Math.min(this.startIndex + this.perPage, this.filteredRoles.length); },
            get paginatedRoles() { return this.filteredRoles.slice(this.startIndex, this.endIndex); },

            toggleExpand(id) { this.expanded = this.expanded === id ? null : id; },
            prevPage() { if (this.currentPage > 1) this.currentPage--; },
            nextPage() { if (this.currentPage < this.totalPages) this.currentPage++; },

            badgeClass(name) {
                return {
                    superadmin: 'bg-rose-50 text-rose-700 ring-rose-600/20',
                    admin:      'bg-amber-50 text-amber-700 ring-amber-600/20',
                    user:       'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                }[name] ?? 'bg-sky-50 text-sky-700 ring-sky-600/20';
            },
            dotClass(name) {
                return { superadmin: 'bg-rose-500', admin: 'bg-amber-500', user: 'bg-emerald-500' }[name] ?? 'bg-sky-500';
            },

            askDelete(role) {
                if (role.protected) {
                    infoDialog(
                        'Role Terproteksi',
                        `Role <strong class="capitalize">${role.displayName}</strong> adalah role inti sistem dan <strong>tidak dapat dihapus</strong>.` +
                        `<p class="mt-2 text-sm text-slate-500 text-left">Menghapus role superadmin dapat membuat tidak ada lagi akun dengan akses penuh ke sistem.</p>`,
                        'warning'
                    );
                    return;
                }

                const userWarning = role.usersCount > 0
                    ? `<div class="flex items-start gap-2.5 rounded-xl bg-amber-50 p-3.5 mt-3 ring-1 ring-amber-200 text-left">
                           <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                           <p class="text-sm font-medium text-amber-800"><strong>${role.usersCount} user</strong> masih menggunakan role ini dan akan kehilangan hak aksesnya!</p>
                       </div>`
                    : '';

                confirmSubmit(role.deleteUrl, {
                    title: 'Hapus Role?',
                    html: `<div class="text-left text-sm mt-2">
                              <p class="text-slate-600">Apakah Anda yakin ingin menghapus role <strong class="capitalize text-slate-900">${role.displayName}</strong>?</p>
                              ${userWarning}
                              <p class="mt-3 rounded-lg bg-slate-50 p-2.5 text-xs text-slate-500 ring-1 ring-slate-200">Tindakan ini permanen dan tidak dapat dibatalkan.</p>
                           </div>`,
                    confirmText: 'Ya, hapus role',
                });
            }
        }));
    });
</script>
@endsection
