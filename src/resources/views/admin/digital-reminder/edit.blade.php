@extends('layouts.app')

@section('title', 'Edit Jadwal Kunjungan')
@section('page-title', 'Edit Jadwal Kunjungan')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Jadwal Kunjungan</h1>
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

    {{-- ===== Form edit jadwal ===== --}}
    <form action="{{ route('admin.digital-reminder.update', $reminder) }}" method="POST"
          x-data="{
              poliId: '{{ old('poli_id', $reminder->poli_id) }}',
              dokters: @js($dokters->map(fn ($d) => ['id' => $d->id, 'nama' => $d->nama, 'poli_id' => $d->poli_id])),
              get dokterOptions() { return this.dokters.filter(d => d.poli_id == this.poliId) }
          }">
        @csrf
        @method('PUT')

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-bold text-slate-900">Blok Jadwal</h2>
                <p class="mt-0.5 text-xs text-slate-500">Tanggal boleh di masa lalu — untuk koreksi jadwal lama.</p>
            </div>
            <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Poli <span class="text-rose-500">*</span></label>
                    <select name="poli_id" x-model="poliId" required
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        <option value="" disabled>— Pilih poli —</option>
                        @foreach ($polis as $po)
                            <option value="{{ $po->id }}" {{ old('poli_id', $reminder->poli_id) == $po->id ? 'selected' : '' }}>{{ $po->nama }}</option>
                        @endforeach
                    </select>
                    @error('poli_id')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Dokter</label>
                    <select name="dokter_id"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        <option value="">— Opsional —</option>
                        @foreach ($dokters as $d)
                            <option value="{{ $d->id }}" data-poli="{{ $d->poli_id }}"
                                    {{ (string) old('dokter_id', $reminder->dokter_id) === (string) $d->id ? 'selected' : '' }}>{{ $d->nama }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Sebaiknya sesuai poli — validasi menolak dokter dari poli lain.</p>
                    @error('dokter_id')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tanggal <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal" value="{{ old('tanggal', $reminder->tanggal?->format('Y-m-d')) }}" required
                           class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    @error('tanggal')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Jam <span class="text-rose-500">*</span></label>
                    <input type="time" name="jam" value="{{ old('jam', $reminder->jam?->format('H:i')) }}" required
                           class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    @error('jam')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Jenis Kunjungan</label>
                    <div class="flex flex-wrap gap-3">
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-600 transition has-[:checked]:border-sky-400 has-[:checked]:bg-sky-50 has-[:checked]:text-sky-700">
                            <input type="radio" name="home_visit" value="0" {{ old('home_visit', $reminder->home_visit ? '1' : '0') === '0' ? 'checked' : '' }}
                                   class="h-4 w-4 border-slate-300 text-sky-600 focus:ring-sky-500">
                            Kunjungan di RS
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-600 transition has-[:checked]:border-teal-400 has-[:checked]:bg-teal-50 has-[:checked]:text-teal-700">
                            <input type="radio" name="home_visit" value="1" {{ old('home_visit', $reminder->home_visit ? '1' : '0') === '1' ? 'checked' : '' }}
                                   class="h-4 w-4 border-slate-300 text-teal-600 focus:ring-teal-500">
                            Home Visit
                        </label>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Status</label>
                    <select name="status" required
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 md:w-1/3">
                        @foreach (\App\Models\Reminder::STATUS as $st)
                            <option value="{{ $st }}" {{ old('status', $reminder->status) === $st ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Status digenerate otomatis saat sweep (tanggal lewat tanpa kunjungan → tidak datang), tapi bisa dikoreksi manual.</p>
                    @error('status')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Catatan</label>
                    <textarea name="catatan" rows="2" maxlength="500" placeholder="Catatan internal untuk jadwal ini (opsional)…"
                              class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('catatan', $reminder->catatan) }}</textarea>
                    @error('catatan')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/50 px-5 py-4">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </form>

    {{-- ===== Catat / info kunjungan nyata ===== --}}
    <div id="kunjungan" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-sm font-bold text-slate-900">Kunjungan Nyata</h2>
            @if ($reminder->kunjungan)
                <p class="mt-0.5 text-xs text-slate-500">Realisasi kunjungan yang sudah tercatat — termasuk poli lain pada tanggal yang sama.</p>
            @else
                <p class="mt-0.5 text-xs text-slate-500">
                    Catat kehadiran pasien — status penjadwalan otomatis menjadi <strong>selesai</strong>. Poli terjadwal pasti tercatat; centang poli lain yang juga dikunjungi hari itu.
                </p>
            @endif
        </div>

        @if ($reminder->kunjungan)
            <ul class="divide-y divide-slate-100">
                @foreach ($kunjunganSehari as $k)
                    <li class="flex flex-wrap items-start justify-between gap-3 px-5 py-3.5">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $k->poli_id === $reminder->poli_id ? 'bg-sky-50 text-sky-700 ring-sky-200/70' : 'bg-slate-100 text-slate-600 ring-slate-200' }}">
                                    {{ $k->poli?->nama ?? 'Tanpa poli' }}
                                </span>
                                @if ($k->poli_id === $reminder->poli_id)
                                    <span class="text-[10px] font-bold uppercase tracking-wide text-slate-400">poli terjadwal</span>
                                @endif
                            </div>
                            <dl class="mt-1.5 flex flex-wrap gap-x-6 gap-y-1 text-xs">
                                <div class="flex gap-1.5">
                                    <dt class="font-semibold text-slate-400">Keluhan:</dt>
                                    <dd class="text-slate-600">{{ $k->keluhan ?: '—' }}</dd>
                                </div>
                                <div class="flex gap-1.5">
                                    <dt class="font-semibold text-slate-400">Diagnosa:</dt>
                                    <dd class="text-slate-600">{{ $k->diagnosa ?: '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                        <span class="text-xs font-semibold text-slate-400">{{ $k->tanggal_kunjungan?->translatedFormat('d M Y') }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="border-t border-slate-100 bg-slate-50/50 px-5 py-3">
                <a href="{{ route('admin.pnpp.kunjungan', $reminder->pnpp_id) }}"
                   class="text-xs font-semibold text-sky-600 transition hover:text-sky-800">Lihat riwayat kunjungan pasien →</a>
            </div>
        @else
            <form action="{{ route('admin.digital-reminder.kunjungan', $reminder) }}" method="POST"
                  x-data="{ pilih: {} }" class="space-y-5 p-5">
                @csrf
                <div>
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tanggal Kunjungan <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal_kunjungan" value="{{ old('tanggal_kunjungan', $reminder->tanggal?->format('Y-m-d')) }}" required
                           class="w-full max-w-xs rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
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
                                <span class="text-sm font-bold text-sky-800">{{ $reminder->poli?->nama }}</span>
                                <span class="rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-sky-700">terjadwal — wajib</span>
                            </div>
                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <input type="text" name="polis[{{ $reminder->poli_id }}][keluhan]" maxlength="1000" value="{{ old('polis.'.$reminder->poli_id.'.keluhan') }}" placeholder="Keluhan di poli ini (opsional)…"
                                       class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                <input type="text" name="polis[{{ $reminder->poli_id }}][diagnosa]" maxlength="1000" value="{{ old('polis.'.$reminder->poli_id.'.diagnosa') }}" placeholder="Diagnosa di poli ini (opsional)…"
                                       class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                            </div>
                        </div>

                        {{-- Poli lain: opsional via checklist --}}
                        @foreach ($polis->where('id', '!==', $reminder->poli_id) as $po)
                            <div>
                                <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 transition has-[:checked]:border-emerald-300 has-[:checked]:bg-emerald-50/60 has-[:checked]:text-emerald-800">
                                    <input type="checkbox" name="poli_pilih[]" value="{{ $po->id }}" x-model="pilih[{{ $po->id }}]"
                                           class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    {{ $po->nama }}
                                </label>
                                <div x-show="pilih[{{ $po->id }}]" x-cloak class="mt-2 grid grid-cols-1 gap-3 px-1 sm:grid-cols-2">
                                    <input type="text" name="polis[{{ $po->id }}][keluhan]" maxlength="1000" value="{{ old('polis.'.$po->id.'.keluhan') }}" placeholder="Keluhan di poli ini (opsional)…"
                                           class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    <input type="text" name="polis[{{ $po->id }}][diagnosa]" maxlength="1000" value="{{ old('polis.'.$po->id.'.diagnosa') }}" placeholder="Diagnosa di poli ini (opsional)…"
                                           class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('poli_pilih')<p class="mt-2 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                        Catat Kunjungan
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
