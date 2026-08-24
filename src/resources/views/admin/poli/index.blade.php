@extends('layouts.app')

@section('title', 'Data Poli')
@section('page-title', 'Data Poli')

@section('content')
<div x-data="poliTable()" class="space-y-6">

    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Data Poli</h2>
            <p class="mt-0.5 text-sm text-slate-500">Poliklinik tempat dokter memberikan layanan.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.master.import', 'poli') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                Import
            </a>
            <a href="{{ route('admin.master.export', 'poli') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                Export
            </a>
            <a href="{{ route('admin.poli.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Tambah Poli
            </a>
        </div>
    </div>

    {{-- ===== Statistik Ringkas ===== --}}
    @php
        $stats = [
            ['label' => 'Total Poli',   'value' => $polis->count(),          'icon' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z', 'color' => 'sky'],
            ['label' => 'Total Dokter', 'value' => $polis->sum('dokters_count'), 'icon' => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z', 'color' => 'emerald'],
        ];
        $statColor = [
            'sky'     => ['bg-sky-50',    'text-sky-600'],
            'emerald' => ['bg-emerald-50','text-emerald-600'],
        ];
    @endphp
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($stats as $stat)
            @php $c = $statColor[$stat['color']]; @endphp
            <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-xs ring-1 ring-slate-200 transition-all hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg {{ $c[0] }} {{ $c[1] }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $stat['icon'] }}" /></svg>
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
        <div class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/60 p-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="relative w-full lg:max-w-sm">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                </div>
                <input x-model.debounce.300ms="search" type="text"
                       class="block w-full rounded-xl border-0 bg-white py-2.5 pl-10 pr-9 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition"
                       placeholder="Cari nama atau kode poli...">
                <button x-show="search" x-cloak @click="search = ''" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <select x-model="sortBy" class="rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                    <option value="az">Nama A–Z</option>
                    <option value="za">Nama Z–A</option>
                    <option value="dokter_desc">Dokter Terbanyak</option>
                </select>
                <select x-model.number="perPage" class="rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] text-left">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                        <th class="px-6 py-3.5 font-semibold">Poli</th>
                        <th class="px-6 py-3.5 font-semibold">Kode</th>
                        <th class="px-6 py-3.5 font-semibold">Jumlah Dokter</th>
                        <th class="px-6 py-3.5 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <template x-for="p in paginatedPolis" :key="p.id">
                        <tr class="group bg-white transition-colors hover:bg-sky-50/40">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3.5">
                                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" /></svg>
                                    </div>
                                    <span class="truncate text-sm font-bold text-slate-900" x-text="p.nama"></span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <template x-if="p.kode">
                                    <code class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold text-slate-600" x-text="p.kode"></code>
                                </template>
                                <template x-if="!p.kode">
                                    <span class="text-xs italic text-slate-400">—</span>
                                </template>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold tabular-nums text-slate-600">
                                    <span x-text="p.doktersCount"></span>
                                    <span class="text-[10px] font-medium text-slate-400">dokter</span>
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5 opacity-60 transition-opacity group-hover:opacity-100 lg:opacity-0">
                                    <a :href="p.editUrl" title="Edit Poli"
                                       class="rounded-lg p-2 text-slate-400 transition-all hover:bg-sky-50 hover:text-sky-600 focus:opacity-100">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                    </a>
                                    <button type="button" @click="askDelete(p)" title="Hapus Poli"
                                            class="rounded-lg p-2 text-slate-400 transition-all hover:bg-rose-50 hover:text-rose-600 focus:opacity-100">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="filteredPolis.length === 0" x-cloak>
                        <td colspan="4" class="px-6 py-16 text-center">
                            <div class="mx-auto flex max-w-sm flex-col items-center">
                                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 ring-8 ring-slate-50">
                                    <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" /></svg>
                                </div>
                                <h3 class="text-base font-bold text-slate-900">Tidak ada poli ditemukan</h3>
                                <p class="mt-1 text-sm text-slate-500">Ubah kata kunci atau tambah poli baru.</p>
                                <button type="button" @click="search = ''" class="mt-5 rounded-lg bg-slate-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-700">Reset Pencarian</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:flex-row" x-show="filteredPolis.length > 0" x-cloak>
            <p class="text-xs font-medium text-slate-500">
                Menampilkan <span class="font-bold text-slate-800" x-text="startIndex + 1"></span>–<span class="font-bold text-slate-800" x-text="endIndex"></span>
                dari <span class="font-bold text-slate-800" x-text="filteredPolis.length"></span> poli
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
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('poliTable', () => ({
            search: '',
            sortBy: 'az',
            perPage: 10,
            currentPage: 1,

            polis: [
                @foreach ($polis as $p)
                {
                    id: {{ $p->id }},
                    nama: @js($p->nama),
                    kode: @js($p->kode),
                    doktersCount: {{ $p->dokters_count }},
                    editUrl: "{{ route('admin.poli.edit', $p->id) }}",
                    deleteUrl: "{{ route('admin.poli.destroy', $p->id) }}",
                }{{ ! $loop->last ? ',' : '' }}
                @endforeach
            ],

            get filteredPolis() {
                let result = this.polis.filter(p => {
                    const q = this.search.toLowerCase();
                    return p.nama.toLowerCase().includes(q) || (p.kode || '').toLowerCase().includes(q);
                });

                if (this.sortBy === 'az')          result.sort((a, b) => a.nama.localeCompare(b.nama));
                if (this.sortBy === 'za')          result.sort((a, b) => b.nama.localeCompare(a.nama));
                if (this.sortBy === 'dokter_desc') result.sort((a, b) => b.doktersCount - a.doktersCount);

                return result;
            },

            get totalPages()    { return Math.max(1, Math.ceil(this.filteredPolis.length / this.perPage)); },
            get startIndex()    { return (this.currentPage - 1) * this.perPage; },
            get endIndex()      { return Math.min(this.startIndex + this.perPage, this.filteredPolis.length); },
            get paginatedPolis(){ return this.filteredPolis.slice(this.startIndex, this.endIndex); },

            prevPage() { if (this.currentPage > 1) this.currentPage--; },
            nextPage() { if (this.currentPage < this.totalPages) this.currentPage++; },

            askDelete(p) {
                if (p.doktersCount > 0) {
                    infoDialog(
                        'Tidak Dapat Dihapus',
                        `Poli <strong>${p.nama}</strong> masih memiliki <strong>${p.doktersCount} dokter</strong>.<p class="mt-2 text-sm text-slate-500 text-left">Pindahkan atau hapus dokter terkait terlebih dahulu.</p>`,
                        'warning'
                    );
                    return;
                }

                confirmSubmit(p.deleteUrl, {
                    title: 'Hapus Poli?',
                    html: `<div class="text-left text-sm mt-2">
                               <p class="text-slate-600">Apakah Anda yakin ingin menghapus poli <strong class="text-slate-900">${p.nama}</strong>?</p>
                               <p class="mt-3 rounded-lg bg-slate-50 p-2.5 text-xs text-slate-500 ring-1 ring-slate-200">Tindakan ini permanen dan tidak dapat dibatalkan.</p>
                           </div>`,
                    confirmText: 'Ya, hapus',
                });
            }
        }));
    });
</script>
@endsection
