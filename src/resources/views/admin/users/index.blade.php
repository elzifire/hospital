@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('page-title', 'Manajemen Pengguna')

@section('content')
<div x-data="userTable()" class="space-y-6">

    {{-- ===== Header Halaman ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Manajemen Pengguna</h2>
            <p class="mt-0.5 text-sm text-slate-500">Kelola akun pengguna sistem dan atur role aksesnya.</p>
        </div>
        <a href="{{ route('admin.users.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Tambah User
        </a>
    </div>

    {{-- ===== Kartu Statistik Ringkas (klik untuk filter) ===== --}}
    @php
        $statCards = [
            ['key' => 'all',        'label' => 'Total Pengguna', 'count' => $counts['total'],      'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z', 'color' => 'sky'],
            ['key' => 'superadmin', 'label' => 'Superadmin',     'count' => $counts['superadmin'], 'icon' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z', 'color' => 'rose'],
            ['key' => 'admin',      'label' => 'Admin',          'count' => $counts['admin'],      'icon' => 'M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z', 'color' => 'amber'],
            ['key' => 'no_role',    'label' => 'Tanpa Role',     'count' => $counts['no_role'],    'icon' => 'M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636', 'color' => 'slate'],
        ];
        $statColor = [
            'sky'   => ['bg' => 'bg-sky-50',   'text' => 'text-sky-600',   'activeRing' => 'ring-2 ring-sky-500'],
            'rose'  => ['bg' => 'bg-rose-50',  'text' => 'text-rose-600',  'activeRing' => 'ring-2 ring-rose-500'],
            'amber' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-600', 'activeRing' => 'ring-2 ring-amber-500'],
            'slate' => ['bg' => 'bg-slate-100','text' => 'text-slate-600', 'activeRing' => 'ring-2 ring-slate-500'],
        ];
    @endphp
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($statCards as $card)
            @php $c = $statColor[$card['color']]; @endphp
            <button type="button" @click="setRoleFilter('{{ $card['key'] }}')" title="Klik untuk filter tabel"
                class="group flex items-center gap-3 rounded-xl bg-white px-4 py-3 text-left shadow-xs ring-1 ring-slate-200 transition-all hover:-translate-y-0.5 hover:shadow-md focus:outline-none"
                :class="roleFilter === '{{ $card['key'] }}' ? '{{ $c['activeRing'] }} shadow-md' : ''">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg {{ $c['bg'] }} {{ $c['text'] }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-lg font-extrabold leading-tight tabular-nums text-slate-900">{{ $card['count'] }}</p>
                    <p class="truncate text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ $card['label'] }}</p>
                </div>
            </button>
        @endforeach
    </div>

    {{-- ===== Tabel Utama ===== --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">

        {{-- Toolbar --}}
        <div class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/60 p-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="relative w-full lg:max-w-sm">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>
                <input x-model.debounce.300ms="search" type="text"
                    class="block w-full rounded-xl border-0 bg-white py-2.5 pl-10 pr-9 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition"
                    placeholder="Cari nama atau email...">
                <button x-show="search" x-cloak @click="search = ''" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600" title="Bersihkan pencarian">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span class="hidden sm:inline">Urutkan:</span>
                    <select x-model="sortBy" class="rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                        <option value="az">Nama A–Z</option>
                        <option value="za">Nama Z–A</option>
                        <option value="newest">Terbaru</option>
                        <option value="oldest">Terlama</option>
                    </select>
                </div>
                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span class="hidden sm:inline">Tampil:</span>
                    <select x-model.number="perPage" class="rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                        <th class="px-6 py-3.5 font-semibold">Pengguna</th>
                        <th class="px-6 py-3.5 font-semibold">Role</th>
                        <th class="px-6 py-3.5 font-semibold">Email Terverifikasi</th>
                        <th class="px-6 py-3.5 font-semibold">Bergabung</th>
                        <th class="px-6 py-3.5 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <template x-for="(u, idx) in paginatedUsers" :key="u.id">
                        <tr class="group bg-white transition-colors hover:bg-sky-50/40">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3.5">
                                    <div :class="'bg-linear-to-br ' + u.gradient"
                                         class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full text-sm font-bold uppercase text-white shadow-sm ring-2 ring-white"
                                         x-text="u.initials"></div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="truncate text-sm font-bold text-slate-900" x-text="u.name"></span>
                                            <span x-show="u.isSelf" class="inline-flex items-center rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold text-sky-700">Anda</span>
                                        </div>
                                        <div class="truncate text-xs text-slate-500" x-text="u.email"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1 ring-inset"
                                      :class="badgeClass(u.role)">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="dotClass(u.role)"></span>
                                    <span x-text="u.role === 'no_role' ? 'Tanpa Role' : u.role"></span>
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <template x-if="u.verified">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                        Terverifikasi
                                    </span>
                                </template>
                                <template x-if="!u.verified">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-600">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                        Belum
                                    </span>
                                </template>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="text-sm font-medium text-slate-700" x-text="u.joinedAt"></div>
                                <div class="text-xs text-slate-400" x-text="timeAgo(u.timestamp)"></div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5 opacity-60 transition-opacity group-hover:opacity-100 lg:opacity-0">
                                    <a :href="u.editUrl" title="Atur Role"
                                       class="rounded-lg p-2 text-slate-400 transition-all hover:bg-sky-50 hover:text-sky-600 focus:opacity-100">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </a>
                                    <button type="button" @click="askDelete(u)" title="Hapus Pengguna"
                                            class="rounded-lg p-2 text-slate-400 transition-all hover:bg-rose-50 hover:text-rose-600 focus:opacity-100">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    {{-- Empty State --}}
                    <tr x-show="filteredUsers.length === 0" x-cloak>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="mx-auto flex max-w-sm flex-col items-center">
                                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 ring-8 ring-slate-50">
                                    <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z" />
                                    </svg>
                                </div>
                                <h3 class="text-base font-bold text-slate-900">Tidak ada pengguna ditemukan</h3>
                                <p class="mt-1 text-sm text-slate-500">Coba ubah kata kunci pencarian atau reset filter yang aktif.</p>
                                <button type="button" @click="resetFilters()"
                                        class="mt-5 rounded-lg bg-slate-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-700">
                                    Reset Filter
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:flex-row" x-show="filteredUsers.length > 0" x-cloak>
            <p class="text-xs font-medium text-slate-500">
                Menampilkan <span class="font-bold text-slate-800" x-text="startIndex + 1"></span>–<span class="font-bold text-slate-800" x-text="endIndex"></span>
                dari <span class="font-bold text-slate-800" x-text="filteredUsers.length"></span> pengguna
            </p>
            <div class="flex items-center gap-1">
                <button type="button" @click="prevPage" :disabled="currentPage === 1"
                        class="rounded-lg bg-white p-2 text-slate-500 shadow-xs ring-1 ring-slate-200 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                </button>
                <template x-for="page in totalPages" :key="page">
                    <button type="button" @click="currentPage = page"
                            class="h-8 min-w-8 rounded-lg px-2 text-xs font-bold tabular-nums transition"
                            :class="currentPage === page ? 'bg-sky-600 text-white shadow-sm' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'"
                            x-text="page"></button>
                </template>
                <button type="button" @click="nextPage" :disabled="currentPage === totalPages"
                        class="rounded-lg bg-white p-2 text-slate-500 shadow-xs ring-1 ring-slate-200 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
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
        <p>
            <strong>Tips:</strong> Klik kartu statistik di atas untuk memfilter tabel berdasarkan role.
            Untuk mengubah role pengguna, klik ikon pensil pada baris yang dituju. Akun sendiri tidak dapat dihapus saat sedang login,
            dan penghapusan akun <em>Superadmin</em> akan meminta konfirmasi tambahan demi keamanan sistem.
        </p>
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

        Alpine.data('userTable', () => ({
            search: '',
            roleFilter: 'all',
            sortBy: 'az',
            perPage: 10,
            currentPage: 1,

            users: [
                @php
                    $gradientList = ['from-sky-500 to-indigo-500', 'from-emerald-500 to-teal-500', 'from-violet-500 to-purple-500', 'from-rose-500 to-pink-500', 'from-amber-500 to-orange-500'];
                @endphp
                @foreach ($users as $user)
                {
                    id: {{ $user->id }},
                    name: @json($user->name),
                    email: @json($user->email),
                    initials: "{{ strtoupper(substr($user->name, 0, 1)) }}",
                    gradient: "{{ $gradientList[abs(crc32($user->name)) % count($gradientList)] }}",
                    role: "{{ $user->roles->first()?->name ?? 'no_role' }}",
                    verified: {{ $user->email_verified_at ? 'true' : 'false' }},
                    joinedAt: "{{ $user->created_at->translatedFormat('d M Y') }}",
                    timestamp: {{ $user->created_at->timestamp }},
                    editUrl: "{{ route('admin.users.edit', $user->id) }}",
                    deleteUrl: "{{ route('admin.users.destroy', $user->id) }}",
                    isSuperadmin: {{ $user->hasRole('superadmin') ? 'true' : 'false' }},
                    isSelf: {{ $user->id === auth()->id() ? 'true' : 'false' }}
                }{{ ! $loop->last ? ',' : '' }}
                @endforeach
            ],

            get filteredUsers() {
                let result = this.users.filter(u => {
                    const q = this.search.toLowerCase();
                    const matchSearch = u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q);
                    const matchRole = this.roleFilter === 'all' || u.role === this.roleFilter;
                    return matchSearch && matchRole;
                });

                if (this.sortBy === 'az')       result.sort((a, b) => a.name.localeCompare(b.name));
                if (this.sortBy === 'za')       result.sort((a, b) => b.name.localeCompare(a.name));
                if (this.sortBy === 'newest')   result.sort((a, b) => b.timestamp - a.timestamp);
                if (this.sortBy === 'oldest')   result.sort((a, b) => a.timestamp - b.timestamp);

                return result;
            },

            get totalPages()   { return Math.max(1, Math.ceil(this.filteredUsers.length / this.perPage)); },
            get startIndex()   { return (this.currentPage - 1) * this.perPage; },
            get endIndex()     { return Math.min(this.startIndex + this.perPage, this.filteredUsers.length); },
            get paginatedUsers() { return this.filteredUsers.slice(this.startIndex, this.endIndex); },

            setRoleFilter(key) {
                this.roleFilter = this.roleFilter === key ? 'all' : key;
                this.currentPage = 1;
            },
            resetFilters() {
                this.search = '';
                this.roleFilter = 'all';
                this.currentPage = 1;
            },
            prevPage() { if (this.currentPage > 1) this.currentPage--; },
            nextPage() { if (this.currentPage < this.totalPages) this.currentPage++; },

            badgeClass(role) {
                return {
                    superadmin: 'bg-rose-50 text-rose-700 ring-rose-600/20',
                    admin:      'bg-amber-50 text-amber-700 ring-amber-600/20',
                    user:       'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                    no_role:    'bg-slate-100 text-slate-600 ring-slate-500/20',
                }[role] ?? 'bg-sky-50 text-sky-700 ring-sky-600/20';
            },
            dotClass(role) {
                return {
                    superadmin: 'bg-rose-500',
                    admin:      'bg-amber-500',
                    user:       'bg-emerald-500',
                    no_role:    'bg-slate-400',
                }[role] ?? 'bg-sky-500';
            },  

            timeAgo(ts) {
                const days = Math.floor((Date.now() / 1000 - ts) / 86400);
                if (days <= 0) return 'Hari ini';
                if (days === 1) return 'Kemarin';
                if (days < 30) return `${days} hari lalu`;
                const months = Math.floor(days / 30);
                if (months < 12) return `${months} bulan lalu`;
                return `${Math.floor(months / 12)} tahun lalu`;
            },

            askDelete(user) {
                if (user.isSelf) {
                    infoDialog('Tidak Diizinkan', 'Anda tidak dapat menghapus <strong>akun sendiri</strong> selagi sedang login.', 'error');
                    return;
                }

                const warningHtml = user.isSuperadmin
                    ? `<div class="text-left text-sm text-slate-600 mt-2 space-y-3">
                           <p>Apakah Anda yakin ingin menghapus user <strong class="text-slate-900">${user.name}</strong>?</p>
                           <div class="flex items-start gap-2.5 rounded-xl bg-rose-50 p-3.5 ring-1 ring-rose-200">
                               <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                               <div><p class="font-bold text-rose-700">Perhatian: Ini akun Superadmin!</p>
                               <p class="mt-0.5 text-rose-600">Menghapus akun ini dapat membuat sistem kehilangan administrator utama.</p></div>
                           </div>
                       </div>`
                    : `<div class="text-left text-sm mt-2 space-y-3">
                           <p class="text-slate-600">Apakah Anda yakin ingin menghapus user <strong class="text-slate-900">${user.name}</strong>?</p>
                           <p class="rounded-lg bg-slate-50 p-2.5 text-xs text-slate-500 ring-1 ring-slate-200">Tindakan ini permanen dan tidak dapat dibatalkan.</p>
                       </div>`;

                confirmSubmit(user.deleteUrl, {
                    title: 'Hapus Pengguna?',
                    html: warningHtml,
                    confirmText: 'Ya, hapus',
                });
            }
        }));
    });
</script>
@endsection
