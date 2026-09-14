@extends('layouts.app')

@section('title', 'Kelola Kategori Template')
@section('page-title', 'Kelola Kategori Template')

@section('content')
@php
    $colorMap = [
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'ring' => 'ring-emerald-300/60', 'dot' => 'bg-emerald-500', 'badge' => 'bg-emerald-500/10 text-emerald-700 ring-emerald-600/20'],
        'sky'     => ['bg' => 'bg-sky-50',     'text' => 'text-sky-700',     'ring' => 'ring-sky-300/60',     'dot' => 'bg-sky-500',     'badge' => 'bg-sky-500/10 text-sky-700 ring-sky-600/20'],
        'amber'   => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'ring' => 'ring-amber-300/60',   'dot' => 'bg-amber-500',   'badge' => 'bg-amber-500/10 text-amber-700 ring-amber-600/20'],
        'rose'    => ['bg' => 'bg-rose-50',    'text' => 'text-rose-700',    'ring' => 'ring-rose-300/60',    'dot' => 'bg-rose-500',    'badge' => 'bg-rose-500/10 text-rose-700 ring-rose-600/20'],
        'purple'  => ['bg' => 'bg-purple-50',  'text' => 'text-purple-700',  'ring' => 'ring-purple-300/60',  'dot' => 'bg-purple-500',  'badge' => 'bg-purple-500/10 text-purple-700 ring-purple-600/20'],
        'indigo'  => ['bg' => 'bg-indigo-50',  'text' => 'text-indigo-700',  'ring' => 'ring-indigo-300/60',  'dot' => 'bg-indigo-500',  'badge' => 'bg-indigo-500/10 text-indigo-700 ring-indigo-600/20'],
        'teal'    => ['bg' => 'bg-teal-50',    'text' => 'text-teal-700',    'ring' => 'ring-teal-300/60',    'dot' => 'bg-teal-500',    'badge' => 'bg-teal-500/10 text-teal-700 ring-teal-600/20'],
    ];
@endphp

<div x-data="kategoriTable()" class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                <a href="{{ route('admin.dashboard') }}" class="rounded transition hover:text-sky-600">Dashboard</a>
                <svg class="h-3.5 w-3.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                <a href="{{ route('admin.setting.index') }}" class="rounded transition hover:text-sky-600">Pengaturan</a>
                <svg class="h-3.5 w-3.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                <span class="font-semibold text-slate-700">Kategori Template</span>
            </nav>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Kelola Kategori Template</h2>
            <p class="mt-0.5 text-sm text-slate-500">Kelompokkan template pesan agar mudah dicari & diintegrasikan ke modul broadcast.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.setting.index') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" /></svg>
                Kembali ke Template
            </a>
            <a href="{{ route('admin.setting.kategori.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Tambah Kategori
            </a>
        </div>
    </div>

    {{-- Search --}}
    <div class="relative w-full lg:max-w-sm">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
        </div>
        <input x-model.debounce.300ms="search" type="text"
               class="block w-full rounded-xl border-0 bg-white py-2.5 pl-10 pr-9 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition"
               placeholder="Cari nama kategori...">
        <button x-show="search" x-cloak @click="search = ''" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
        </button>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 text-[11px] uppercase tracking-wider text-slate-400">
                        <th class="px-6 py-3.5 font-semibold">Kategori</th>
                        <th class="px-6 py-3.5 font-semibold">Warna</th>
                        <th class="px-6 py-3.5 font-semibold">Deskripsi</th>
                        <th class="px-6 py-3.5 text-center font-semibold">Jumlah Template</th>
                        <th class="px-6 py-3.5 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <template x-for="c in filteredKategoris" :key="c.id">
                        <tr class="group bg-white transition-colors hover:bg-sky-50/40">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3.5">
                                    <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl" :class="c.color.bg + ' ' + c.color.text + ' ' + c.color.ring">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /></svg>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-bold text-slate-900" x-text="c.nama"></p>
                                        <p class="font-mono text-[10px] text-slate-400" x-text="c.slug"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="inline-block h-2.5 w-2.5 rounded-full" :class="c.color.dot"></span>
                                    <span class="text-xs font-semibold capitalize text-slate-500" x-text="c.warna"></span>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="max-w-sm truncate text-slate-500" x-text="c.deskripsi || 'Tidak ada deskripsi'"></p>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-700 tabular-nums" x-text="c.templates_count"></span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5 opacity-60 transition-opacity group-hover:opacity-100 lg:opacity-0">
                                    <a :href="'/admin/setting/kategori/' + c.id + '/edit'" title="Edit Kategori"
                                       class="rounded-lg p-2 text-slate-400 transition-all hover:bg-sky-50 hover:text-sky-600 focus:opacity-100">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                    </a>
                                    <button type="button" @click="askDelete(c)" title="Hapus Kategori"
                                            class="rounded-lg p-2 text-slate-400 transition-all hover:bg-rose-50 hover:text-rose-600 focus:opacity-100">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="filteredKategoris.length === 0" x-cloak>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="mx-auto flex max-w-sm flex-col items-center">
                                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 ring-8 ring-slate-50">
                                    <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /></svg>
                                </div>
                                <h3 class="text-base font-bold text-slate-900">Tidak ada kategori ditemukan</h3>
                                <p class="mt-1 text-sm text-slate-500">Ubah kata kunci atau tambah kategori baru.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('kategoriTable', () => ({
            search: '',
            kategoris: [
                @foreach ($categories as $c)
                {
                    id: {{ $c->id }},
                    nama: @js($c->nama),
                    slug: @js($c->slug),
                    warna: @js($c->warna),
                    deskripsi: @js($c->deskripsi),
                    templatesCount: {{ $c->templates_count }},
                    color: {
                        bg: @js($colorMap[$c->warna]['bg'] ?? $colorMap['sky']['bg']),
                        text: @js($colorMap[$c->warna]['text'] ?? $colorMap['sky']['text']),
                        ring: @js($colorMap[$c->warna]['ring'] ?? $colorMap['sky']['ring']),
                        dot: @js($colorMap[$c->warna]['dot'] ?? $colorMap['sky']['dot']),
                    },
                }{{ ! $loop->last ? ',' : '' }}
                @endforeach
            ],

            get filteredKategoris() {
                const q = this.search.toLowerCase();
                return this.kategoris.filter(c =>
                    c.nama.toLowerCase().includes(q) ||
                    (c.deskripsi || '').toLowerCase().includes(q)
                );
            },

            askDelete(c) {
                confirmSubmit('/admin/setting/kategori/' + c.id, {
                    title: 'Hapus Kategori?',
                    html: `<div class="text-left text-sm mt-2">
                               <p class="text-slate-600">Apakah Anda yakin ingin menghapus kategori <strong class="text-slate-900">${c.nama}</strong>?</p>
                               <p class="mt-3 rounded-lg bg-slate-50 p-2.5 text-xs text-slate-500 ring-1 ring-slate-200">Template yang memakai kategori ini akan otomatis menjadi "Tanpa Kategori".</p>
                           </div>`,
                    confirmText: 'Ya, hapus',
                });
            }
        }));
    });
</script>
@endsection