@extends('layouts.app')

@section('title', 'Riwayat Kunjungan — ' . $pnpp->nama)
@section('page-title', 'Riwayat Kunjungan')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.pnpp.index') }}" class="rounded transition hover:text-sky-600">Data PNPP</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <a href="{{ route('admin.pnpp.edit', $pnpp) }}" class="rounded transition hover:text-sky-600">{{ $pnpp->nama }}</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">Riwayat Kunjungan</span>
        </nav>
        <h2 class="text-xl font-bold tracking-tight text-slate-900">Riwayat Kunjungan</h2>
        <p class="mt-0.5 text-sm text-slate-500">Catatan berobat untuk <strong>{{ $pnpp->nama }}</strong>.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ===== Ringkasan PNPP ===== --}}
        <div class="lg:col-span-1">
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="h-16 bg-linear-to-r from-sky-600 via-indigo-600 to-violet-600"></div>
                <div class="-mt-8 flex flex-col items-center px-5 pb-5 text-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-linear-to-br from-sky-500 to-indigo-500 text-2xl font-extrabold uppercase text-white shadow-lg ring-4 ring-white">
                        {{ substr($pnpp->nama, 0, 1) }}
                    </div>
                    <h3 class="mt-3 text-lg font-bold text-slate-900">{{ $pnpp->nama }}</h3>
                    <p class="font-mono text-xs text-slate-400">{{ $pnpp->nip ?? 'NIP —' }}</p>

                    <div class="mt-2.5 flex flex-wrap items-center justify-center gap-1.5">
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold capitalize ring-1 ring-inset {{ $pnpp->jenis_kelamin === 'L' ? 'bg-sky-50 text-sky-700 ring-sky-600/20' : ($pnpp->jenis_kelamin === 'P' ? 'bg-rose-50 text-rose-700 ring-rose-600/20' : 'bg-slate-100 text-slate-500 ring-slate-300') }}">
                            {{ $pnpp->jenis_kelamin === 'L' ? 'Laki-laki' : ($pnpp->jenis_kelamin === 'P' ? 'Perempuan' : '—') }}
                        </span>
                        @if ($pnpp->usia !== null)
                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">{{ $pnpp->usia }} tahun</span>
                        @endif
                    </div>

                    <dl class="mt-5 w-full space-y-2.5 border-t border-slate-100 pt-4 text-left text-xs">
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-400">No. BPJS</dt>
                            <dd class="truncate font-mono font-semibold text-slate-700">{{ $pnpp->no_bpjs ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-400">Satker</dt>
                            <dd class="truncate font-semibold text-slate-700">{{ $pnpp->satker?->nama ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-400">No. HP</dt>
                            <dd class="truncate font-semibold text-slate-700">{{ $pnpp->no_hp ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-400">Tanggal Lahir</dt>
                            <dd class="truncate font-semibold text-slate-700">{{ $pnpp->tanggal_lahir?->translatedFormat('d M Y') ?? '—' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-4 w-full border-t border-slate-100 pt-4 text-left">
                        <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Penyakit Kronis</p>
                        @if ($pnpp->penyakit->isEmpty())
                            <p class="text-xs italic text-slate-400">Tidak ada / sehat</p>
                        @else
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($pnpp->penyakit as $penyakit)
                                    <span class="rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 ring-1 ring-amber-200/70">{{ $penyakit->nama }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <a href="{{ route('admin.pnpp.edit', $pnpp) }}"
               class="mt-4 flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                Edit Data PNPP
            </a>
        </div>

        {{-- ===== Riwayat Kunjungan ===== --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Form tambah kunjungan --}}
            <div x-data="{ saving: false }" class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 p-6">
                    <h3 class="text-base font-bold text-slate-900">Tambah Kunjungan</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Catat kunjungan berobat terbaru.</p>
                </div>
                <form action="{{ route('admin.pnpp.kunjungan.store', $pnpp) }}" method="POST" @submit="saving = true" x-cloak>
                    @csrf
                    <div class="space-y-5 p-6">
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                            <div>
                                <label for="tanggal_kunjungan" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Tanggal Kunjungan <span class="text-rose-500">*</span></label>
                                <input type="date" name="tanggal_kunjungan" id="tanggal_kunjungan" value="{{ old('tanggal_kunjungan', now()->toDateString()) }}" required
                                       class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('tanggal_kunjungan') ring-rose-300 focus:ring-rose-500 @enderror">
                                @error('tanggal_kunjungan')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="keluhan" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Keluhan</label>
                                <input type="text" name="keluhan" id="keluhan" value="{{ old('keluhan') }}" placeholder="cth. Pusing, demam"
                                       class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('keluhan') ring-rose-300 focus:ring-rose-500 @enderror">
                                @error('keluhan')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="diagnosa" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Diagnosa</label>
                                <input type="text" name="diagnosa" id="diagnosa" value="{{ old('diagnosa') }}" placeholder="cth. Hipertensi"
                                       class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('diagnosa') ring-rose-300 focus:ring-rose-500 @enderror">
                                @error('diagnosa')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-end border-t border-slate-100 bg-slate-50/70 p-4">
                        <button type="submit" :disabled="saving"
                                class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                            <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="saving ? 'Menyimpan...' : 'Tambah Kunjungan'"></span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Daftar kunjungan --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h3 class="text-base font-bold text-slate-900">Daftar Kunjungan</h3>
                    <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-bold tabular-nums text-sky-700 ring-1 ring-sky-200/70">
                        {{ $pnpp->kunjungans->count() }} kunjungan
                    </span>
                </div>

                @if ($pnpp->kunjungans->isEmpty())
                    <div class="px-6 py-16 text-center">
                        <div class="mx-auto flex max-w-sm flex-col items-center">
                            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 ring-8 ring-slate-50">
                                <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-900">Belum ada riwayat kunjungan</h3>
                            <p class="mt-1 text-sm text-slate-500">Tambahkan kunjungan pertama lewat formulir di atas.</p>
                        </div>
                    </div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($pnpp->kunjungans as $kunjungan)
                            <li class="flex items-start gap-4 px-6 py-4 transition-colors hover:bg-sky-50/30">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-bold text-slate-900">{{ $kunjungan->tanggal_kunjungan->translatedFormat('d F Y') }}</span>
                                        @if ($loop->first)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-700 ring-1 ring-emerald-200/70">Terbaru</span>
                                        @endif
                                    </div>
                                    <dl class="mt-1.5 grid grid-cols-1 gap-1 text-xs sm:grid-cols-2">
                                        <div class="flex gap-1.5">
                                            <dt class="flex-shrink-0 font-semibold text-slate-400">Keluhan:</dt>
                                            <dd class="text-slate-600">{{ $kunjungan->keluhan ?: '—' }}</dd>
                                        </div>
                                        <div class="flex gap-1.5">
                                            <dt class="flex-shrink-0 font-semibold text-slate-400">Diagnosa:</dt>
                                            <dd class="text-slate-600">{{ $kunjungan->diagnosa ?: '—' }}</dd>
                                        </div>
                                    </dl>
                                </div>
                                <button type="button"
                                        onclick="confirmSubmit('{{ route('admin.pnpp.kunjungan.destroy', [$pnpp, $kunjungan]) }}', {
                                            title: 'Hapus Kunjungan?',
                                            html: '<p class=\"text-sm text-slate-600\">Kunjungan tanggal <strong>{{ $kunjungan->tanggal_kunjungan->translatedFormat('d M Y') }}</strong> akan dihapus permanen.</p>',
                                            confirmText: 'Ya, hapus'
                                        })"
                                        class="rounded-lg p-2 text-slate-400 transition-all hover:bg-rose-50 hover:text-rose-600" title="Hapus kunjungan">
                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
