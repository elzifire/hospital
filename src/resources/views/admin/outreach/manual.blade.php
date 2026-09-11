@extends('layouts.app')

@section('title', 'Kirim Pesan Manual')
@section('page-title', 'Kirim Pesan Manual')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Kirim Pesan Manual</h1>
            <p class="mt-1 text-sm text-slate-500">
                Pilih satu atau beberapa pasien sebagai penerima. Setiap pasien mendapat satu pesan;
                sistem mengirim lewat format resmi WhatsApp Business.
            </p>
        </div>
        <a href="{{ route('admin.outreach.index') }}"
           class="inline-flex items-center gap-2 rounded-lg bg-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-300">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Kembali ke Outreach
        </a>
    </div>

    {{-- ===== Filter pasien ===== --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-sm font-bold text-slate-900">Cari &amp; Pilih Pasien</h2>
            <p class="mt-0.5 text-xs text-slate-500">Hasil saringan di bawah menjadi daftar calon penerima.</p>
        </div>
        <form method="GET" action="{{ request()->url() }}"
              class="flex flex-wrap items-end gap-3 bg-slate-50/50 px-5 py-4">
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Nama / NIP / no. HP…"
                       class="w-52 rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Satker</label>
                <select name="satker" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    <option value="">Semua</option>
                    @foreach ($satkers as $s)
                        <option value="{{ $s->id }}" {{ $filters['satker'] == $s->id ? 'selected' : '' }}>{{ $s->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">
                    Filter
                </button>
                <a href="{{ request()->url() }}"
                   class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-300">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- ===== Form kirim manual ===== --}}
    <form action="{{ route('admin.outreach.store') }}" method="POST" class="space-y-6"
          x-data="{
              mode: 'sekarang',
              selected: [],
              allIds: @json($canKirimIds),
              waktuBawaan() {
                  const d = new Date(Date.now() + 15 * 60000);
                  const p = (n) => String(n).padStart(2, '0');
                  return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + 'T' + p(d.getHours()) + ':' + p(d.getMinutes());
              },
              get allChecked() {
                  return this.allIds.length > 0 && this.allIds.every((id) => this.selected.includes(id));
              },
              toggleAll() {
                  if (this.allChecked) {
                      this.selected = this.selected.filter((id) => !this.allIds.includes(id));
                  } else {
                      this.selected = [...new Set([...this.selected, ...this.allIds])];
                  }
              }
          }">
        @csrf

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">
                        Penerima <span class="font-medium text-slate-400">({{ $pnpps->count() }} hasil filter)</span>
                    </h2>
                    <p class="mt-0.5 text-xs text-slate-500">Centang satu atau beberapa pasien sebagai penerima pesan.</p>
                </div>
                <label x-show="allIds.length > 0" x-cloak
                       class="inline-flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-100 has-[:checked]:border-sky-400 has-[:checked]:bg-sky-50 has-[:checked]:text-sky-700">
                    <input type="checkbox" x-ref="pilihSemua" :checked="allChecked" @change="toggleAll"
                           x-effect="$refs.pilihSemua.indeterminate = selected.length > 0 && !allChecked"
                           class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                    <span>Pilih semua</span>
                </label>
            </div>

            @if ($pnpps->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-slate-400">Tidak ada pasien yang cocok dengan saringan.</p>
            @else
                <div class="max-h-[420px] overflow-auto">
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
                                <tr class="{{ $validWa ? '' : 'opacity-60' }}">
                                    <td class="px-5 py-3">
                                        @if ($validWa)
                                            <input type="checkbox" name="pnpp_ids[]" value="{{ $p->id }}"
                                                   x-model="selected" 
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

        {{-- ===== Jenis pesan ===== --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-bold text-slate-900">Jenis Pesan</h2>
                <p class="mt-0.5 text-xs text-slate-500">Pesan akan tersimpan di riwayat modul yang dipilih.</p>
            </div>
            <div class="space-y-3 px-5 py-4">
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 transition has-[:checked]:border-sky-400 has-[:checked]:bg-sky-50/60">
                    <input type="radio" name="jenis" value="outreach" checked class="mt-1 h-4 w-4 border-slate-300 text-sky-600 focus:ring-sky-500">
                    <span>
                        <span class="block text-sm font-semibold text-slate-800">Outreach</span>
                        <span class="block text-xs text-slate-500">Undangan jadwal (riwayat modul Outreach).</span>
                    </span>
                </label>
                {{-- @can('manage follow-up')
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 transition has-[:checked]:border-sky-400 has-[:checked]:bg-sky-50/60">
                        <input type="radio" name="jenis" value="follow_up" class="mt-1 h-4 w-4 border-slate-300 text-sky-600 focus:ring-sky-500">
                        <span>
                            <span class="block text-sm font-semibold text-slate-800">Follow Up</span>
                            <span class="block text-xs text-slate-500">Tindak lanjut jadwal (riwayat modul Follow Up).</span>
                        </span>
                    </label>
                @endcan --}}
            </div>
        </div>

        {{-- ===== Kapan dikirim ===== --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-bold text-slate-900">Kapan Dikirim</h2>
                <p class="mt-0.5 text-xs text-slate-500">Kirim sekarang juga, atau jadwalkan untuk dikirim otomatis di waktu lain.</p>
            </div>
            <div class="space-y-3 px-5 py-4">
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 transition has-[:checked]:border-sky-400 has-[:checked]:bg-sky-50/60">
                    <input type="radio" name="mode" value="sekarang" checked x-model="mode" class="mt-1 h-4 w-4 border-slate-300 text-sky-600 focus:ring-sky-500">
                    <span>
                        <span class="block text-sm font-semibold text-slate-800">Kirim Sekarang</span>
                        <span class="block text-xs text-slate-500">Pesan langsung terkirim begitu Anda menekan tombol.</span>
                    </span>
                </label>
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 transition has-[:checked]:border-sky-400 has-[:checked]:bg-sky-50/60">
                    <input type="radio" name="mode" value="jadwalkan" x-model="mode" class="mt-1 h-4 w-4 border-slate-300 text-sky-600 focus:ring-sky-500">
                    <span>
                        <span class="block text-sm font-semibold text-slate-800">Jadwalkan</span>
                        <span class="block text-xs text-slate-500">Sistem akan mengirim pesan otomatis pada waktu yang Anda pilih.</span>
                    </span>
                </label>
                {{-- <div x-show="mode === 'jadwalkan'" x-cloak
                     class="flex flex-wrap items-end gap-3 rounded-xl bg-slate-50/60 p-3 ring-1 ring-inset ring-slate-200">
                    <div>
                        <label for="kirim_pada" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Waktu pengiriman</label>
                        <input type="datetime-local" name="kirim_pada" id="kirim_pada"
                               :value="mode === 'jadwalkan' ? waktuBawaan() : ''"
                               class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <p class="text-xs text-slate-400">Gunakan format 24 jam (contoh: 14:30). Waktu minimal 1 menit ke depan.</p>
                </div> --}}
            </div>
        </div>

        {{-- ===== Ringkasan & tombol kirim ===== --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-xs text-slate-500">
                    <p>
                        Akan mengirim pesan ke <strong class="text-slate-800" x-text="selected.length">0</strong> pasien
                        menggunakan format resmi WhatsApp Business.
                    </p>
                </div>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.998 12Zm0 0h7.5" /></svg>
                    <span x-text="mode === 'jadwalkan' ? 'Jadwalkan Kirim' : 'Kirim Sekarang'"></span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection