@extends('layouts.app')

@section('title', 'Jadwal Ulang Kunjungan')
@section('page-title', 'Jadwal Ulang Kunjungan')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Jadwal Ulang Kunjungan</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $reminder->pnpp?->nama }} · NIP {{ $reminder->pnpp?->nip ?? '—' }} · {{ $reminder->pnpp?->satker?->nama ?? '—' }}
            </p>
        </div>
        <a href="{{ route('admin.digital-reminder.index') }}"
           class="inline-flex items-center gap-2 rounded-lg bg-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-300">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Kembali ke Daftar
        </a>
    </div>

    {{-- ===== Info jadwal lama ===== --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-bold text-slate-900">Jadwal Lama</h3>
            <p class="mt-0.5 text-xs text-slate-500">Jadwal ini akan ditandai <strong>Jadwal Ulang</strong> dan tidak lagi tampil di Follow Up; pesan undangan lama yang belum terkirim otomatis dibatalkan.</p>
        </div>
        <dl class="grid gap-x-6 gap-y-3 px-5 py-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tanggal</dt>
                <dd class="mt-0.5 font-semibold text-slate-800">{{ $reminder->tanggal?->translatedFormat('l, d F Y') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Jam</dt>
                <dd class="mt-0.5 font-semibold text-slate-800">{{ $reminder->jam?->format('H:i') ?? '—' }} WIB</dd>
            </div>
            <div>
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Status</dt>
                <dd>
                    <span class="mt-0.5 inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold
                        {{ $reminder->status === 'terjadwal' ? 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200/70' : ($reminder->status === 'tidak_datang' ? 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200/70' : 'bg-slate-100 text-slate-500 ring-1 ring-inset ring-slate-200/70') }}">
                        {{ ucfirst(str_replace('_', ' ', $reminder->status)) }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Poli</dt>
                <dd class="mt-0.5 font-semibold text-slate-800">{{ $reminder->poli?->nama ?? ($reminder->home_visit ? 'Home Visit' : '—') }}</dd>
            </div>
            
        </dl>
    </div>

    {{-- ===== Form jadwal ulang ===== --}}
    <form action="{{ route('admin.digital-reminder.jadwal-ulang.store', $reminder) }}" method="POST"
          class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        @csrf
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tanggal Baru <span class="text-rose-500">*</span></label>
                <input type="date" name="tanggal" value="{{ old('tanggal') }}" required
                       class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                @error('tanggal')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Jam Baru <span class="text-rose-500">*</span></label>
                <input type="time" name="jam" value="{{ old('jam') }}" required
                       class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                @error('jam')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Catatan (opsional)</label>
            <textarea name="catatan" rows="2" maxlength="500" placeholder="Alasan jadwal ulang, misalnya pasien minta reschedule…"
                      class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('catatan') }}</textarea>
            @error('catatan')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
        </div>
        <p class="rounded-lg bg-sky-50 px-4 py-3 text-xs text-sky-700 ring-1 ring-inset ring-sky-100">
            Jadwal baru dibuat dengan status <strong>Terjadwal</strong> dan akan menjadi <strong>Selesai</strong> saat kunjungan dicatat. Poli, dokter, dan template pesan mengikuti jadwal lama.
        </p>
        <div class="flex gap-2">
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Buat Jadwal Ulang
            </button>
            <a href="{{ route('admin.digital-reminder.index') }}"
               class="inline-flex items-center rounded-lg bg-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-300">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection