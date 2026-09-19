@extends('layouts.app')

@section('title', 'Tambah Kunjungan')
@section('page-title', 'Tambah Kunjungan')

@section('content')
<div class="space-y-6" x-data="{
        mode: '{{ old('reminder_id') ? 'jadwal' : 'manual' }}',
        reminders: @js($reminders->map(fn ($r) => [
            'id' => $r->id,
            'tanggal' => $r->tanggal->format('Y-m-d'),
            'poli_id' => $r->poli_id,
            'poli' => $r->poli?->nama,
        ])->values()),
        reminderId: '{{ old('reminder_id') }}',
        tanggal: '{{ old('tanggal_kunjungan', now()->toDateString()) }}',
        pilih: @js(collect(old('poli_pilih', []))->mapWithKeys(fn ($id) => [(string) $id => true])->all()),
        get reminder() {
            return this.reminders.find(r => String(r.id) === String(this.reminderId)) ?? null;
        },
        setReminder(r) {
            this.reminderId = String(r.id);
            this.tanggal = r.tanggal;
        },
    }">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Tambah Kunjungan</h1>
            <p class="mt-1 text-sm text-slate-500">
                Catat kunjungan dari jadwal Digital Reminder (poli terkunci) atau langsung tanpa jadwal.
            </p>
        </div>
        <a href="{{ route('admin.kunjungan.index') }}"
           class="inline-flex items-center gap-2 rounded-lg bg-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-300">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Kembali ke Daftar
        </a>
    </div>

    {{-- ===== Langkah 1: pilih cara mencatat ===== --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-sm font-bold text-slate-900">1. Pilih Cara Mencatat</h2>
            <p class="mt-0.5 text-xs text-slate-500">Dari jadwal: poli terjadwal otomatis terkunci &amp; jadwal selesai. Manual: bebas memilih poli tanpa jadwal.</p>
        </div>
        <div class="grid grid-cols-1 gap-3 p-5 sm:grid-cols-2">
            <button type="button" @click="mode = 'jadwal'" x-cloak
                    :class="mode === 'jadwal' ? 'border-sky-400 bg-sky-50 ring-2 ring-sky-200' : 'border-slate-200 hover:border-slate-300'"
                    class="rounded-xl border px-5 py-4 text-left transition">
                <span class="flex items-center gap-2.5">
                    <svg class="h-5 w-5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                    <span>
                        <span class="block text-sm font-bold text-slate-900">Dari Jadwal (Reminder)</span>
                        <span class="mt-0.5 block text-xs text-slate-500">Pilih penjadwalan yang sudah dibuat&nbsp;— tanggal &amp; poli terjadwal sama dengan jadwal.</span>
                    </span>
                </span>
            </button>
            <button type="button" @click="mode = 'manual'" x-cloak
                    :class="mode === 'manual' ? 'border-sky-400 bg-sky-50 ring-2 ring-sky-200' : 'border-slate-200 hover:border-slate-300'"
                    class="rounded-xl border px-5 py-4 text-left transition">
                <span class="flex items-center gap-2.5">
                    <svg class="h-5 w-5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                    <span>
                        <span class="block text-sm font-bold text-slate-900">Catat Manual</span>
                        <span class="mt-0.5 block text-xs text-slate-500">Tanpa jadwal&nbsp;— cari pasien lalu catat semua poli yang dikunjunginya hari itu.</span>
                    </span>
                </span>
            </button>
        </div>
    </div>

    {{-- ===== Cari pasien / jadwal (form GET bersama) ===== --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-sm font-bold text-slate-900">Cari &amp; Saring</h2>
            <p class="mt-0.5 text-xs text-slate-500">Saring pasien PNPP maupun daftar jadwal — hasil filter menjadi kandidat di bawahnya.</p>
        </div>
        <form method="GET" action="{{ request()->url() }}"
              class="flex flex-wrap items-end gap-3 bg-slate-50/50 px-5 py-4">
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Nama / NIP / no. HP…"
                       class="h-10 w-52 rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Satker</label>
                <select name="satker" class="h-10 rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    <option value="">Semua</option>
                    @foreach ($satkers as $s)
                        <option value="{{ $s->id }}" {{ $filters['satker'] == $s->id ? 'selected' : '' }}>{{ $s->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="h-10 rounded-lg bg-sky-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">
                    Filter
                </button>
                <a href="{{ request()->url() }}"
                   class="h-10 rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-300">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- ===== Form "Dari Jadwal" (POST → kunjungan.catat) ===== --}}
    <form x-show="mode === 'jadwal'" x-cloak action="{{ route('admin.kunjungan.catat') }}" method="POST" class="space-y-6">
        @csrf

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-bold text-slate-900">
                    Jadwal Tersedia <span class="font-medium text-slate-400">({{ $reminders->count() }} belum dicatat)</span>
                </h2>
                <p class="mt-0.5 text-xs text-slate-500">Pilih satu penjadwalan — poli &amp; tanggal diambil dari jadwal (poli boleh diubah tanggal realisasinya).</p>
            </div>

            @if ($reminders->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-slate-400">Tidak ada jadwal terjadwal yang belum dicatat.</p>
            @else
                <div class="max-h-[340px] overflow-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="sticky top-0 bg-slate-50/95 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400 backdrop-blur">
                            <tr>
                                <th class="w-12 px-5 py-3"></th>
                                <th class="px-5 py-3">Jadwal</th>
                                <th class="px-5 py-3">Pasien</th>
                                <th class="px-5 py-3">Poli</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($reminders as $r)
                                <tr class="cursor-pointer transition has-[:checked]:bg-sky-50/60 hover:bg-slate-50/60">
                                    <td class="px-5 py-3">
                                        <input type="radio" name="reminder_id" value="{{ $r->id }}" required
                                               x-model="reminderId"
                                               @change="setReminder({ id: {{ $r->id }}, tanggal: '{{ $r->tanggal->format('Y-m-d') }}' })"
                                               class="h-4 w-4 border-slate-300 text-sky-600 focus:ring-sky-500"
                                               title="Pilih jadwal {{ $r->pnpp?->nama }}">
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3" onclick="this.parentElement.querySelector('input[type=radio]').click()">
                                        <p class="font-semibold text-slate-800">{{ $r->tanggal?->translatedFormat('d M Y') }}</p>
                                        <p class="text-xs text-slate-400">{{ $r->jam?->format('H:i') }} WIB{{ $r->home_visit ? ' · Home Visit' : '' }}</p>
                                    </td>
                                    <td class="px-5 py-3" onclick="this.parentElement.querySelector('input[type=radio]').click()">
                                        <p class="font-semibold text-slate-800">{{ $r->pnpp?->nama ?? '—' }}</p>
                                        <p class="text-xs text-slate-400">NIP {{ $r->pnpp?->nip ?? '—' }} · {{ $r->pnpp?->satker?->nama ?? '—' }}</p>
                                    </td>
                                    <td class="px-5 py-3" onclick="this.parentElement.querySelector('input[type=radio]').click()">
                                        <span class="rounded-full bg-sky-50 px-2.5 py-0.5 text-[11px] font-semibold text-sky-700 ring-1 ring-inset ring-sky-200/70">
                                            {{ $r->poli?->nama ?? 'Tanpa poli' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            @error('reminder_id')<p class="border-t border-slate-100 px-5 py-3 text-xs text-rose-500">{{ $message }}</p>@enderror
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-bold text-slate-900">Catat Poli yang Dikunjungi</h2>
                <p class="mt-0.5 text-xs text-slate-500"
                   x-text="reminder
                        ? 'Realisasi jadwal ' + (reminder.poli || 'home visit') + ' — poli lain yang dikunjungi juga bisa dicentang.'
                        : 'Pilih penjadwalan terlebih dahulu.'"></p>
            </div>
            <template x-if="reminder">
                <div class="space-y-5 p-5">
                    <div class="max-w-xs">
                        <label for="tanggal_kunjungan" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tanggal Kunjungan <span class="text-rose-500">*</span></label>
                        <input type="date" name="tanggal_kunjungan" id="tanggal_kunjungan" :value="tanggal" required
                               class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        @error('tanggal_kunjungan')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Poli yang Dikunjungi</p>
                        <div class="space-y-2">
                            {{-- Poli terjadwal: terkunci, selalu tercatat --}}
                            <div class="rounded-xl border border-sky-200 bg-sky-50/60 px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                    </svg>
                                    <span class="text-sm font-bold text-sky-800" x-text="reminder.poli"></span>
                                    <span class="rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-sky-700">terjadwal — wajib</span>
                                </div>
                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <input type="text" :name="'polis[' + reminder.poli_id + '][keluhan]'" maxlength="1000" placeholder="Keluhan di poli ini (opsional)…"
                                           class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    <input type="text" :name="'polis[' + reminder.poli_id + '][diagnosa]'" maxlength="1000" placeholder="Diagnosa di poli ini (opsional)…"
                                           class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                </div>
                            </div>

                            {{-- Poli lain: opsional via checklist (sembunyikan poli terjadwal) --}}
                            @foreach ($polis as $po)
                                <div x-show="String(reminder.poli_id) !== '{{ $po->id }}'">
                                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 transition has-[:checked]:border-emerald-300 has-[:checked]:bg-emerald-50/60 has-[:checked]:text-emerald-800">
                                        <input type="checkbox" name="poli_pilih[]" value="{{ $po->id }}" x-model="pilih[{{ $po->id }}]"
                                               class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                        {{ $po->nama }}
                                    </label>
                                    <div x-show="pilih[{{ $po->id }}]" x-cloak class="mt-2 grid grid-cols-1 gap-3 px-1 sm:grid-cols-2">
                                        <input type="text" :name="'polis[' + {{ $po->id }} + '][keluhan]'" maxlength="1000" placeholder="Keluhan di poli ini (opsional)…"
                                               class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                        <input type="text" :name="'polis[' + {{ $po->id }} + '][diagnosa]'" maxlength="1000" placeholder="Diagnosa di poli ini (opsional)…"
                                               class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('poli_pilih')<p class="mt-2 text-xs text-rose-500">{{ $message }}</p>@enderror
                    </div>
                </div>
            </template>
            <div class="flex items-center justify-end border-t border-slate-100 bg-slate-50/50 px-5 py-4">
                <button type="submit" x-show="reminder" x-cloak
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    Catat Kunjungan
                </button>
            </div>
        </div>
    </form>

    {{-- ===== Form "Catat Manual" (POST → kunjungan.store) ===== --}}
    <form x-show="mode === 'manual'" x-cloak action="{{ route('admin.kunjungan.store') }}" method="POST" class="space-y-6">
        @csrf

        {{-- ===== Pilih pasien (satu) ===== --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-bold text-slate-900">
                    Pasien <span class="font-medium text-slate-400">({{ $pnpps->count() }} hasil filter)</span>
                </h2>
                <p class="mt-0.5 text-xs text-slate-500">Pilih satu pasien — kunjungan bisa dicatat untuk beberapa poli sekaligus.</p>
            </div>

            @if ($pnpps->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-slate-400">Tidak ada pasien yang cocok dengan filter.</p>
            @else
                <div class="max-h-[340px] overflow-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="sticky top-0 bg-slate-50/95 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400 backdrop-blur">
                            <tr>
                                <th class="w-12 px-5 py-3"></th>
                                <th class="px-5 py-3">Pasien</th>
                                <th class="px-5 py-3">No. WhatsApp</th>
                                <th class="px-5 py-3">Satker</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($pnpps as $p)
                                @php($validWa = \App\Broadcasting\PhoneFormat::toWa($p->no_hp))
                                <tr class="cursor-pointer transition has-[:checked]:bg-sky-50/60 hover:bg-slate-50/60">
                                    <td class="px-5 py-3">
                                        <input type="radio" name="pnpp_id" value="{{ $p->id }}" required
                                               class="h-4 w-4 border-slate-300 text-sky-600 focus:ring-sky-500"
                                               @checked(old('pnpp_id') == $p->id) title="Pilih {{ $p->nama }}">
                                    </td>
                                    <td class="px-5 py-3" onclick="this.parentElement.querySelector('input[type=radio]').click()">
                                        <p class="font-semibold text-slate-800">{{ $p->nama }}</p>
                                        <p class="text-xs text-slate-400">NIP {{ $p->nip ?? '—' }}</p>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="font-mono text-xs {{ $validWa ? 'text-slate-600' : 'text-slate-400' }}">{{ $p->no_hp ?? '—' }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-xs text-slate-500">{{ $p->satker?->nama ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            @error('pnpp_id')<p class="border-t border-slate-100 px-5 py-3 text-xs text-rose-500">{{ $message }}</p>@enderror
        </div>

        {{-- ===== Tanggal + poli repeatable ===== --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-bold text-slate-900">Catat Poli yang Dikunjungi</h2>
                <p class="mt-0.5 text-xs text-slate-500">Satu tanggal bisa beberapa poli — keluhan &amp; diagnosa diisi per poli.</p>
            </div>
            <div class="space-y-5 p-5">
                <div class="max-w-xs">
                    <label for="tanggal_kunjungan" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tanggal Kunjungan <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal_kunjungan" id="tanggal_kunjungan" value="{{ old('tanggal_kunjungan', now()->toDateString()) }}" required
                           class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    @error('tanggal_kunjungan')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>

                @include('admin.kunjungan._poli-rows')
            </div>
            <div class="flex items-center justify-end border-t border-slate-100 bg-slate-50/50 px-5 py-4">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Simpan Kunjungan
                </button>
            </div>
        </div>
    </form>
</div>
@endsection