@extends('layouts.app')

@section('title', 'Auto Reply')
@section('page-title', 'Auto Reply')

@section('content')
<div x-data="autoReplyTable()" class="space-y-6">

    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Auto Reply</h2>
            <p class="mt-0.5 text-sm text-slate-500">Bank data pertanyaan & jawaban otomatis untuk balasan WhatsApp masuk.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.auto-reply.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Tambah Aturan
            </a>
        </div>
    </div>

    @include('admin.respon._flash')

    {{-- ===== Filter Kategori ===== --}}
    <form method="GET" action="{{ route('admin.auto-reply.index') }}" class="flex flex-wrap items-center gap-3">
        <select name="kategori" onchange="this.form.submit()"
                class="rounded-xl border-0 bg-white py-2.5 pl-3 pr-9 text-sm font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
            <option value="">Semua Kategori</option>
            @foreach (\App\Models\AutoReply::kategoriOptions() as $k => $label)
                <option value="{{ $k }}" @selected($kategori === $k)>{{ $label }}</option>
            @endforeach
        </select>
        @if ($kategori)
            <a href="{{ route('admin.auto-reply.index') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-200">Reset Filter</a>
        @endif
    </form>

    {{-- ===== Tabel ===== --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                        <th class="px-6 py-3.5 font-semibold">Aturan</th>
                        <th class="px-6 py-3.5 font-semibold">Kategori</th>
                        <th class="px-6 py-3.5 font-semibold">Cara Cocok</th>
                        <th class="px-6 py-3.5 font-semibold">Pola</th>
                        <th class="px-6 py-3.5 font-semibold">Jawaban</th>
                        <th class="px-6 py-3.5 text-center font-semibold">Prioritas</th>
                        <th class="px-6 py-3.5 text-center font-semibold">Status</th>
                        <th class="px-6 py-3.5 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($aturans as $aturan)
                        <tr class="group bg-white transition-colors hover:bg-sky-50/40">
                            <td class="px-6 py-4">
                                <span class="text-sm font-bold text-slate-900">{{ $aturan->nama }}</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex items-center rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                                    {{ \App\Models\AutoReply::kategoriOptions()[$aturan->kategori] ?? $aturan->kategori }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-xs font-medium text-slate-600">
                                {{ \App\Models\AutoReply::caraCocokLabels()[$aturan->cara_cocok] ?? $aturan->cara_cocok }}
                            </td>
                            <td class="px-6 py-4">
                                @if ($aturan->pola)
                                    <code class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold text-slate-600">{{ $aturan->pola }}</code>
                                @else
                                    <span class="text-xs italic text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="max-w-[280px] px-6 py-4">
                                <p class="truncate text-xs leading-relaxed text-slate-600" title="{{ $aturan->isi }}">{{ $aturan->isi }}</p>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-center tabular-nums text-sm font-bold text-slate-700">{{ $aturan->prioritas }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-center">
                                <form method="POST" action="{{ route('admin.auto-reply.toggle', $aturan->id) }}">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold transition {{ $aturan->aktif ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $aturan->aktif ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                        {{ $aturan->aktif ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </form>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5 opacity-60 transition-opacity group-hover:opacity-100 lg:opacity-0 lg:group-hover:opacity-100">
                                    <a href="{{ route('admin.auto-reply.edit', $aturan->id) }}" title="Edit Aturan"
                                       class="rounded-lg p-2 text-slate-400 transition-all hover:bg-sky-50 hover:text-sky-600 focus:opacity-100">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                    </a>
                                    {{-- Tombol Hapus disembunyikan sementara.
                                    <button type="button" @click="askDelete({{ $aturan->id }}, '{{ $aturan->nama }}', '{{ route('admin.auto-reply.destroy', $aturan->id) }}')" title="Hapus Aturan"
                                            class="rounded-lg p-2 text-slate-400 transition-all hover:bg-rose-50 hover:text-rose-600 focus:opacity-100">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                    </button>
                                    --}}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 ring-8 ring-slate-50">
                                        <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-900">Belum ada aturan auto reply</h3>
                                    <p class="mt-1 text-sm text-slate-500">Tambahkan bank data pertanyaan & jawaban otomatis.</p>
                                    <a href="{{ route('admin.auto-reply.create') }}" class="mt-5 rounded-lg bg-slate-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-700">Tambah Aturan</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('autoReplyTable', () => ({
            askDelete(id, nama, url) {
                confirmSubmit(url, {
                    title: 'Hapus Aturan?',
                    html: `<div class="text-left text-sm mt-2">
                               <p class="text-slate-600">Apakah Anda yakin ingin menghapus aturan <strong class="text-slate-900">${nama}</strong>?</p>
                               <p class="mt-3 rounded-lg bg-slate-50 p-2.5 text-xs text-slate-500 ring-1 ring-slate-200">Tindakan ini permanen dan tidak dapat dibatalkan.</p>
                           </div>`,
                    confirmText: 'Ya, hapus',
                });
            }
        }));
    });
</script>
@endsection