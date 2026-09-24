@extends('layouts.app')

@section('title', 'Detail Registrasi PNPP')
@section('page-title', 'Detail Registrasi PNPP')

@section('content')
@php
    $statuses = \App\Models\RegisterPnpp::STATUS_LABEL;
    $approved = $registerPnpp->status === \App\Models\RegisterPnpp::STATUS_DISETUJUI;
    $jmlPoli = $registerPnpp->polis->count();
    $jmlSetuju = $registerPnpp->polis->filter(fn ($p) => $p->pivot->approved_at !== null)->count();
    $poliSayaDisetujui = $poliAktif !== null
        && $registerPnpp->polis->contains(fn ($p) => (int) $p->id === (int) $poliAktif && $p->pivot->approved_at !== null);
@endphp

<div class="space-y-6">
    {{-- ===== Header / aksi ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                <a href="{{ route('admin.register-pnpp.index') }}" class="rounded transition hover:text-sky-600">Registrasi PNPP</a>
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                <span>Detail</span>
            </nav>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">{{ $registerPnpp->nama }}</h2>
            <div class="mt-1.5 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold {{ $approved ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $approved ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                    {{ $statuses[$registerPnpp->status] ?? $registerPnpp->status }}
                </span>
                <span class="text-xs text-slate-400">
                    Didaftarkan {{ $registerPnpp->created_at?->translatedFormat('d M Y, H:i') }}
                </span>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.register-pnpp.index') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
                Kembali
            </a>

            @if ($poliAktif !== null)
                {{-- Akun poli: setujui/batalkan bagian polinya saja. --}}
                @if ($poliSayaDisetujui)
                    <form method="POST" action="{{ route('admin.register-pnpp.unapprove', $registerPnpp) }}"
                          onsubmit="return confirm('Batalkan persetujuan poli Anda untuk {{ $registerPnpp->nama }}?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-amber-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                            Batalkan Persetujuan Poli Saya
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.register-pnpp.approve', $registerPnpp) }}"
                          onsubmit="return confirm('Setujui {{ $registerPnpp->nama }} untuk poli Anda?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            Setujui untuk Poli Saya
                        </button>
                    </form>
                @endif
            @else
                {{-- Admin/superadmin: setujui semua atau batalkan semua. --}}
                @if ($approved)
                    <form method="POST" action="{{ route('admin.register-pnpp.unapprove', $registerPnpp) }}"
                          onsubmit="return confirm('Batalkan persetujuan {{ $registerPnpp->nama }} untuk semua poli?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-amber-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                            Batalkan Semua Poli
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.register-pnpp.approve', $registerPnpp) }}"
                          onsubmit="return confirm('Setujui {{ $registerPnpp->nama }} untuk semua poli tujuan?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            Setujui Semua Poli
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    {{-- ===== Sinkronisasi Database PNPP ===== --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        @if ($pnppCocok)
            <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-slate-900">Sudah ada di database PNPP</p>
                    <p class="text-xs text-slate-500">Pendaftar ini cocok dengan PNPP yang sudah terdaftar. Saat disetujui, data diri (alamat, no. HP, jabatan, satker & bagian) akan diperbarui dari data pendaftar.</p>
                </div>
                <a href="{{ route('admin.pnpp.edit', $pnppCocok) }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
                    Lihat PNPP #{{ $pnppCocok->id }}
                </a>
            </div>
        @else
            <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-slate-900">Pendaftar baru — belum ada di database PNPP</p>
                    <p class="text-xs text-slate-500">Saat disetujui, data pendaftar akan dibuatkan baris PNPP baru otomatis sebelum penjadwalan Digital Reminder dibuat.</p>
                </div>
            </div>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- ===== Data Diri ===== --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
            <h3 class="mb-4 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-slate-500">
                <svg class="h-4 w-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                Data Pendaftar
            </h3>
            <dl class="grid gap-x-8 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Nama Lengkap</dt>
                    <dd class="mt-0.5 text-sm font-bold text-slate-900">{{ $registerPnpp->nama }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Jabatan</dt>
                    <dd class="mt-0.5 text-sm font-semibold text-slate-800">{{ $registerPnpp->jabatan }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">NIK</dt>
                    <dd class="mt-0.5 text-sm font-semibold tabular-nums text-slate-800">{{ $registerPnpp->nik ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">NIP/NRP</dt>
                    <dd class="mt-0.5 text-sm font-semibold tabular-nums text-slate-800">{{ $registerPnpp->nip ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tempat, Tanggal Lahir</dt>
                    <dd class="mt-0.5 text-sm font-semibold text-slate-800">{{ $registerPnpp->ttl }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">No. HP</dt>
                    <dd class="mt-0.5 text-sm font-bold tabular-nums text-slate-800">{{ $registerPnpp->no_hp }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Alamat</dt>
                    <dd class="mt-0.5 text-sm text-slate-700">{{ $registerPnpp->alamat }}</dd>
                </div>
            </dl>
        </div>

        {{-- ===== Satker & Rencana ===== --}}
        <div class="space-y-6">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-slate-500">
                    <svg class="h-4 w-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                    Satuan Kerja
                </h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Satker</dt>
                        <dd class="mt-0.5 text-sm font-bold text-slate-900">{{ $registerPnpp->satker?->nama ?: '—' }}</dd>
                    </div>
                    @if ($registerPnpp->unit)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Unit / Bagian</dt>
                            <dd class="mt-0.5 text-sm font-semibold text-slate-800">{{ $registerPnpp->unit }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-slate-500">
                    <svg class="h-4 w-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                    Rencana Kunjungan
                </h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tanggal</dt>
                        <dd class="mt-0.5 text-sm font-bold tabular-nums text-slate-900">{{ $registerPnpp->rencana_tanggal_kunjungan?->translatedFormat('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Jam</dt>
                        <dd class="mt-0.5 text-sm font-semibold tabular-nums text-slate-800">{{ $registerPnpp->rencana_jam_kunjungan }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- ===== Poli Tujuan + Persetujuan ===== --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-slate-500">
                <svg class="h-4 w-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" /></svg>
                Poli Tujuan
                <span class="ml-auto text-xs font-bold text-slate-400">{{ $jmlSetuju }}/{{ $jmlPoli }} disetujui</span>
            </h3>
            @forelse ($registerPnpp->polis as $poli)
                @php
                    $okPoli = $poli->pivot->approved_at !== null;
                    $adalahPoliSaya = $poliAktif !== null && (int) $poli->id === (int) $poliAktif;
                    $waktuSetujui = $okPoli ? \Illuminate\Support\Carbon::parse($poli->pivot->approved_at)->translatedFormat('d M Y, H:i') : null;
                @endphp
                <div class="mb-2 flex items-center justify-between gap-3 rounded-xl border px-4 py-3 transition {{ $okPoli ? 'border-emerald-200 bg-emerald-50/50' : 'border-slate-200 bg-slate-50/60' }}">
                    <div class="flex min-w-0 items-center gap-3">
                        @if ($okPoli)
                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </div>
                        @else
                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5" /></svg>
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-slate-900">
                                {{ $poli->nama }}
                                @if ($adalahPoliSaya)
                                    <span class="ml-1 text-[10px] font-extrabold uppercase tracking-wider text-sky-500">Poli saya</span>
                                @endif
                            </p>
                            @if ($okPoli)
                                <p class="text-xs text-emerald-700">Disetujui {{ $waktuSetujui }}</p>
                            @else
                                <p class="text-xs text-slate-400">Belum disetujui</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-shrink-0 items-center gap-2">
                        @if ($poliAktif !== null && ! $adalahPoliSaya)
                            <span class="text-[11px] font-semibold text-slate-400">Kelola oleh poli tersebut</span>
                        @else
                            @if ($okPoli)
                                <form method="POST" action="{{ route('admin.register-pnpp.unapprove', ['registerPnpp' => $registerPnpp] + ($poliAktif === null ? ['poli_id' => $poli->id] : [])) }}"
                                      onsubmit="return confirm('Batalkan persetujuan untuk poli {{ $poli->nama }}?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-600 ring-1 ring-amber-200 transition hover:bg-amber-100">
                                        Batalkan
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.register-pnpp.approve', ['registerPnpp' => $registerPnpp] + ($poliAktif === null ? ['poli_id' => $poli->id] : [])) }}"
                                      onsubmit="return confirm('Setujui pendaftar untuk poli {{ $poli->nama }}?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-600 ring-1 ring-emerald-200 transition hover:bg-emerald-100">
                                        Setujui
                                    </button>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>
            @empty
                <span class="text-sm italic text-slate-400">Tidak ada poli dipilih</span>
            @endforelse
        </div>

        {{-- ===== Tujuan Kunjungan ===== --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-slate-500">
                <svg class="h-4 w-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" /></svg>
                Tujuan Kunjungan
            </h3>
            <div class="flex flex-wrap gap-2">
                @forelse ($registerPnpp->tujuanKunjungans as $tujuan)
                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-sky-50 px-3 py-1.5 text-sm font-bold text-sky-700 ring-1 ring-sky-100">{{ $tujuan->nama }}</span>
                @empty
                    <span class="text-sm italic text-slate-400">Tidak ada tujuan dipilih</span>
                @endforelse
                @if ($registerPnpp->tujuan_lainnya)
                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-amber-50 px-3 py-1.5 text-sm font-bold text-amber-700 ring-1 ring-amber-100">{{ $registerPnpp->tujuan_lainnya }}</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection