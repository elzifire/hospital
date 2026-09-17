@extends('layouts.app')

@section('title', 'Pesan Manual')
@section('page-title', 'Respon Pasien')

@section('content')
<div class="space-y-6">
    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Pesan Manual</h2>
            <p class="mt-0.5 text-sm text-slate-500">Pilih kontak PNPP lalu tulis pesan teks bebas — dikirim tanpa tipe template Meta agar lebih hemat.</p>
        </div>
        <a href="{{ route('admin.respon.index') }}"
           class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
            Balasan WhatsApp
        </a>
    </div>

    @include('admin.respon._flash')

    @include('admin.respon._subnav', ['active' => 'pesanmanual'])

    <div class="space-y-4"
         x-data="{
             selected: [],
             allIds: @js($canKirimIds),
             isi: '',
             get allChecked() { return this.allIds.length > 0 && this.allIds.every((id) => this.selected.includes(id)); },
             toggleAll() {
                 this.selected = this.allChecked
                     ? this.selected.filter((id) => !this.allIds.includes(id))
                     : [...new Set([...this.selected, ...this.allIds])];
             },
             pakaiTemplate(konten) { this.isi = konten; },
         }">

        {{-- ===== Filter pencarian ===== --}}
        <form method="GET" action="{{ route('admin.respon.pesan-manual') }}"
              class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Cari kontak</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Nama / NIP / nomor HP…"
                       class="w-64 rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Satker</label>
                <select name="satker" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    <option value="">Semua Satker</option>
                    @foreach ($satkers as $satker)
                        <option value="{{ $satker->id }}" @selected($filters['satker'] == $satker->id)>{{ $satker->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">Saring</button>
                @if (filled($filters['q']) || filled($filters['satker']))
                    <a href="{{ route('admin.respon.pesan-manual') }}" class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-300">Reset</a>
                @endif
            </div>
        </form>

        <form method="POST" action="{{ route('admin.respon.pesan-manual-kirim') }}" class="space-y-4">
            @csrf

            {{-- ===== Target kontak PNPP ===== --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Target Kontak PNPP <span class="font-medium text-slate-400">({{ $pnpps->count() }} hasil filter)</span></h3>
                        <p class="mt-0.5 text-xs text-slate-500">Centang satu atau beberapa kontak sebagai penerima (hanya nomor WhatsApp valid).</p>
                    </div>
                    <label x-show="allIds.length > 0" x-cloak
                           class="inline-flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-100 has-[:checked]:border-sky-400 has-[:checked]:bg-sky-50 has-[:checked]:text-sky-700">
                        <input type="checkbox" :checked="allChecked" @change="toggleAll"
                               class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                        <span>Centang semua</span>
                    </label>
                </div>

                @if ($pnpps->isEmpty())
                    <p class="px-5 py-10 text-center text-sm text-slate-400">Tidak ada kontak PNPP yang cocok dengan saringan.</p>
                @else
                    <div class="max-h-[420px] overflow-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="sticky top-0 bg-slate-50/95 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400 backdrop-blur">
                                <tr>
                                    <th class="w-12 px-5 py-3"></th>
                                    <th class="px-5 py-3">Kontak</th>
                                    <th class="px-5 py-3">No. WhatsApp</th>
                                    <th class="px-5 py-3">Satker</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($pnpps as $p)
                                    @php($validWa = \App\Broadcasting\PhoneFormat::toWa($p->no_hp))
                                    <tr class="{{ $validWa ? '' : 'opacity-60' }}">
                                        <td class="px-5 py-3">
                                            @if ($validWa)
                                                <input type="checkbox" name="pnpp_ids[]" value="{{ $p->id }}" x-model="selected"
                                                       class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                            @else
                                                <span class="inline-block h-4 w-4 rounded border border-slate-200 bg-slate-50"></span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3">
                                            <p class="font-semibold text-slate-800">{{ $p->nama }}</p>
                                            <p class="text-xs text-slate-400">NIP/NRP {{ $p->nip ?? '—' }}</p>
                                        </td>
                                        <td class="px-5 py-3">
                                            @if ($validWa)
                                                <span class="font-mono text-xs text-slate-600">{{ $validWa }}</span>
                                            @else
                                                <span class="font-mono text-xs text-slate-400">{{ $p->no_hp ?? '—' }}</span>
                                                <span class="ml-1 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-600 ring-1 ring-inset ring-rose-200">nomor tidak valid</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-xs text-slate-500">{{ $p->satker?->nama ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- ===== Isi pesan + referensi template ===== --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Isi Pesan</h3>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Pesan dikirim sebagai <span class="font-semibold text-slate-700">teks bebas</span> (bukan tipe template Meta) — lebih hemat biaya.
                    </p>
                </div>
                <div class="space-y-4 px-5 py-4">
                    <div>
                        <label for="isi" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Pesan <span class="text-rose-500">*</span></label>
                        <textarea name="isi" id="isi" x-model="isi" rows="6" required placeholder="Tulis pesan untuk kontak terpilih…"
                                  class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('isi') }}</textarea>
                        <p class="mt-1 text-xs text-slate-400">Isi sama untuk semua kontak terpilih. Gunakan referensi template di bawah untuk menyalin kalimat cepat.</p>
                    </div>

                    {{-- Referensi template --}}
                    <details class="rounded-xl border border-slate-200 bg-slate-50/60" x-data="{ buka: true }">
                        <summary class="flex cursor-pointer items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-slate-700 select-none">
                            <span>Referensi Template Pesan</span>
                            <span class="rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-bold text-sky-700 ring-1 ring-inset ring-sky-200">{{ count($templates) }} template</span>
                        </summary>
                        <div class="border-t border-slate-200 px-4 py-3">
                            @if ($templates === [])
                                <p class="text-xs text-slate-400">Belum ada template aktif.</p>
                            @else
                                <div class="grid gap-2">
                                    @foreach ($templates as $t)
                                        <button type="button" @click="pakaiTemplate({{ \Illuminate\Support\Js::from($t['konten']) }})"
                                                class="group flex flex-col items-start gap-1 rounded-xl border border-slate-200 bg-white p-3 text-left transition hover:border-sky-300 hover:bg-sky-50/50">
                                            <span class="flex items-center gap-2 text-xs font-bold text-slate-700">
                                                <span class="rounded bg-sky-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-sky-700">
                                                    {{ $t['kategori'] ?: 'Umum' }}
                                                </span>
                                                {{ $t['judul'] }}
                                            </span>
                                            <span class="whitespace-pre-line text-xs text-slate-500 group-hover:text-slate-600">{{ \Illuminate\Support\Str::limit($t['konten'], 180) }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            <p class="mt-2 text-[11px] text-slate-400">Klik template untuk mengisi isi pesan — tetap terkirim sebagai teks bebas.</p>
                        </div>
                    </details>
                </div>
            </div>

            {{-- ===== Ringkasan & tombol ===== --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-slate-500">
                        Akan mengirim pesan ke <strong class="text-slate-800" x-text="selected.length">0</strong> kontak.
                    </p>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.998 12Zm0 0h7.5" /></svg>
                        Kirim Pesan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection