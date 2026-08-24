@extends('layouts.app')

@section('title', 'Manage Permissions')
@section('page-title', 'Manage Permissions')

@section('content')
@php
    $usedCount = $permissions->filter(fn ($p) => $p->roles->count() > 0)->count();
    $unusedCount = $permissions->count() - $usedCount;
    $usageRate = $permissions->count() > 0 ? round(($usedCount / $permissions->count()) * 100) : 0;
@endphp

<div x-data="permissionTable()" class="space-y-6">

    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Manajemen Permission</h2>
            <p class="mt-0.5 text-sm text-slate-500">Permission adalah satuan izin akses terkecil yang bisa diberikan ke role.</p>
        </div>
        <a href="{{ route('admin.permissions.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Tambah Permission
        </a>
    </div>

    {{-- ===== Statistik Ringkas ===== --}}
    @php
        $stats = [
            ['label' => 'Total Permission', 'value' => $permissions->count(), 'color' => 'sky',
             'icon' => 'M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z'],
            ['label' => 'Dipakai Role',     'value' => $usedCount,            'color' => 'emerald',
             'icon' => 'm4.5 12.75 6 6 9-13.5'],
            ['label' => 'Tidak Dipakai',    'value' => $unusedCount,          'color' => 'amber',
             'icon' => 'M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636'],
            ['label' => 'Tingkat Pakai',    'value' => $usageRate . '%',      'color' => 'violet',
             'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z'],
        ];
        $statStyles = [
            'sky'     => 'bg-sky-50 text-sky-600',
            'emerald' => 'bg-emerald-50 text-emerald-600',
            'amber'   => 'bg-amber-50 text-amber-600',
            'violet'  => 'bg-violet-50 text-violet-600',
        ];
    @endphp
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($stats as $stat)
            <button type="button" @if ($stat['label'] === 'Tidak Dipakai') @click="setFilter('unused')" title="Klik untuk lihat yang tidak dipakai" @elseif ($stat['label'] === 'Dipakai Role') @click="setFilter('used')" title="Klik untuk lihat yang dipakai" @endif
                class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 text-left shadow-xs ring-1 ring-slate-200 transition-all hover:-translate-y-0.5 hover:shadow-md focus:outline-none">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg {{ $statStyles[$stat['color']] }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $stat['icon'] }}" /></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-lg font-extrabold leading-tight tabular-nums text-slate-900">{{ $stat['value'] }}</p>
                    <p class="truncate text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ $stat['label'] }}</p>
                </div>
            </button>
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
                       placeholder="Cari permission...">
                <button x-show="search" x-cloak @click="search = ''" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <select x-model="filter" class="rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                    <option value="all">Semua</option>
                    <option value="used">Dipakai Role</option>
                    <option value="unused">Tidak Dipakai</option>
                </select>
                <select x-model="sortBy" class="rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                    <option value="az">Nama A–Z</option>
                    <option value="za">Nama Z–A</option>
                    <option value="most_used">Paling Sering Dipakai</option>
                    <option value="least_used">Paling Jarang Dipakai</option>
                </select>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[680px] text-left">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                        <th class="px-6 py-3.5 font-semibold">Permission</th>
                        <th class="px-6 py-3.5 font-semibold">Digunakan Oleh Role</th>
                        <th class="px-6 py-3.5 font-semibold">Jumlah</th>
                        <th class="px-6 py-3.5 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <template x-for="p in paginatedPermissions" :key="p.id">
                        <tr class="group bg-white transition-colors hover:bg-sky-50/40">
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex items-center gap-2 text-sm font-semibold text-slate-800">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-violet-50 text-violet-500">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" /></svg>
                                    </span>
                                    <span x-text="p.name"></span>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-if="p.roles.length > 0">
                                        <template x-for="role in p.roles.slice(0, 3)" :key="role">
                                            <span class="inline-flex items-center rounded-md bg-sky-50 px-2 py-0.5 text-[11px] font-semibold capitalize text-sky-700 ring-1 ring-inset ring-sky-600/10" x-text="role"></span>
                                        </template>
                                    </template>
                                    <span x-show="p.roles.length > 3" class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-500" x-text="'+' + (p.roles.length - 3)"></span>
                                    <span x-show="p.roles.length === 0" class="text-xs italic text-slate-400">Belum dipakai</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex min-w-8 items-center justify-center rounded-full px-2 py-0.5 text-xs font-bold tabular-nums"
                                      :class="p.roles.length > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-400'"
                                      x-text="p.roles.length"></span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5 opacity-60 transition-opacity group-hover:opacity-100 lg:opacity-0">
                                    <a :href="p.editUrl" title="Edit Permission"
                                       class="rounded-lg p-2 text-slate-400 transition-all hover:bg-sky-50 hover:text-sky-600 focus:opacity-100">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                    </a>
                                    <button type="button" title="Hapus Permission" @click="askDelete(p)"
                                            class="rounded-lg p-2 text-slate-400 transition-all hover:bg-rose-50 hover:text-rose-600 focus:opacity-100">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    {{-- Empty state --}}
                    <tr x-show="filteredPermissions.length === 0" x-cloak>
                        <td colspan="4" class="px-6 py-16 text-center">
                            <div class="mx-auto flex max-w-sm flex-col items-center">
                                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 ring-8 ring-slate-50">
                                    <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" /></svg>
                                </div>
                                <h3 class="text-base font-bold text-slate-900">Tidak ada permission ditemukan</h3>
                                <p class="mt-1 text-sm text-slate-500">Ubah kata kunci atau filter, atau tambahkan permission baru.</p>
                                <button type="button" @click="resetFilters()" class="mt-5 rounded-lg bg-slate-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-700">Reset Filter</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:flex-row" x-show="filteredPermissions.length > 0" x-cloak>
            <p class="text-xs font-medium text-slate-500">
                Menampilkan <span class="font-bold text-slate-800" x-text="startIndex + 1"></span>–<span class="font-bold text-slate-800" x-text="endIndex"></span>
                dari <span class="font-bold text-slate-800" x-text="filteredPermissions.length"></span> permission
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
        <p><strong>Tips:</strong> Gunakan penamaan konsisten format <code class="rounded bg-white/70 px-1 font-mono">verb resource</code> (cth: <em>view pasien</em>, <em>edit jadwal</em>). Menghapus permission yang sedang dipakai role akan otomatis mencabut akses terkait.</p>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('permissionTable', () => ({
            search: '',
            filter: 'all',
            sortBy: 'az',
            currentPage: 1,
            perPage: 10,

            permissions: [
                @foreach ($permissions as $permission)
                {
                    id: {{ $permission->id }},
                    name: @js($permission->name),
                    roles: @js($permission->roles->pluck('name')),
                    editUrl: "{{ route('admin.permissions.edit', $permission) }}",
                    deleteUrl: "{{ route('admin.permissions.destroy', $permission) }}",
                }{{ ! $loop->last ? ',' : '' }}
                @endforeach
            ],

            get filteredPermissions() {
                let result = this.permissions.filter(p => {
                    const matchSearch = p.name.toLowerCase().includes(this.search.toLowerCase());
                    const matchFilter = this.filter === 'all'
                        || (this.filter === 'used' && p.roles.length > 0)
                        || (this.filter === 'unused' && p.roles.length === 0);
                    return matchSearch && matchFilter;
                });

                if (this.sortBy === 'az')          result.sort((a, b) => a.name.localeCompare(b.name));
                if (this.sortBy === 'za')          result.sort((a, b) => b.name.localeCompare(a.name));
                if (this.sortBy === 'most_used')   result.sort((a, b) => b.roles.length - a.roles.length);
                if (this.sortBy === 'least_used')  result.sort((a, b) => a.roles.length - b.roles.length);

                return result;
            },

            get totalPages()           { return Math.max(1, Math.ceil(this.filteredPermissions.length / this.perPage)); },
            get startIndex()           { return (this.currentPage - 1) * this.perPage; },
            get endIndex()             { return Math.min(this.startIndex + this.perPage, this.filteredPermissions.length); },
            get paginatedPermissions() { return this.filteredPermissions.slice(this.startIndex, this.endIndex); },

            setFilter(f)   { this.filter = f; this.currentPage = 1; },
            resetFilters() { this.search = ''; this.filter = 'all'; this.currentPage = 1; },
            prevPage()     { if (this.currentPage > 1) this.currentPage--; },
            nextPage()     { if (this.currentPage < this.totalPages) this.currentPage++; },

            askDelete(p) {
                const usedWarning = p.roles.length > 0
                    ? `<div class="flex items-start gap-2.5 rounded-xl bg-amber-50 p-3.5 mt-3 ring-1 ring-amber-200 text-left">
                           <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                           <div class="text-sm">
                               <p class="font-bold text-amber-800">Permission ini sedang dipakai ${p.roles.length} role:</p>
                               <p class="mt-1 font-medium capitalize text-amber-700">${p.roles.join(', ')}</p>
                               <p class="mt-1 text-xs text-amber-600">Role terkait akan langsung kehilangan izin ini.</p>
                           </div>
                       </div>`
                    : '';

                confirmSubmit(p.deleteUrl, {
                    title: 'Hapus Permission?',
                    html: `<div class="text-left text-sm mt-2">
                              <p class="text-slate-600">Apakah Anda yakin ingin menghapus permission <strong class="text-slate-900">&quot;${p.name}&quot;</strong>?</p>
                              ${usedWarning}
                              <p class="mt-3 rounded-lg bg-slate-50 p-2.5 text-xs text-slate-500 ring-1 ring-slate-200">Tindakan ini permanen dan tidak dapat dibatalkan.</p>
                           </div>`,
                    confirmText: 'Ya, hapus',
                });
            }
        }));
    });
</script>
@endsection
