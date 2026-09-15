@extends('layouts.app')

@section('title', 'Buat Jadwal Kunjungan')
@section('page-title', 'Buat Jadwal Kunjungan')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Buat Jadwal Kunjungan</h1>
            <p class="mt-1 text-sm text-slate-500">
                Pilih pasien PNPP lalu atur jadwalnya — pesan WhatsApp digenerate belakangan dari modul Outreach &amp; Follow Up.
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

    {{-- ===== Langkah 1a: filter pasien (form GET terpisah) ===== --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-sm font-bold text-slate-900">1. Cari &amp; Pilih Pasien</h2>
            <p class="mt-0.5 text-xs text-slate-500">Saring pasien PNPP — hasil filter menjadi kandidat di bawahnya.</p>
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

    {{-- ===== Form utama (POST) ===== --}}
    <form action="{{ route('admin.digital-reminder.store') }}" method="POST"
          x-data="{
              terpilih: {},
              semua: false,
              poliTerpilih: {},
              semuaPoli: false,
              semuaId: @js($pnpps->pluck('id')),
              poliIdSemua: @js($polis->pluck('id')),
              oldPoliIds: @js(collect(old('poli_ids', []))->map(fn ($v) => (string) $v)),
              poliAwal: @js($poliAwal ?? []),
              templates: @js($templates->map(fn ($t) => ['id' => (int) $t->id, 'judul' => (string) $t->judul, 'konten' => (string) $t->konten, 'token' => $t->tokenParam()])->values()),
              templateId: @js((string) old('message_template_id')),
              tanggal: @js((string) old('tanggal')),
              jam: @js((string) old('jam')),
              poliData: @js($polis->mapWithKeys(fn ($po) => [(string) $po->id => $po->nama])),
              pnppData: @js($pnpps->mapWithKeys(fn ($p) => [(string) $p->id => ['nama' => $p->nama ?? '—', 'nip' => $p->nip ?? '', 'satker' => $p->satker?->nama ?? '']])),
              get jumlah() { return Object.values(this.terpilih).filter(Boolean).length },
              get jumlahPoli() { return Object.values(this.poliTerpilih).filter(Boolean).length },
              get totalJadwal() { return this.jumlah * this.jumlahPoli },
              activeTemplate() {
                  return this.templates.find((t) => String(t.id) === String(this.templateId)) ?? null;
              },
              pilihTemplate(id) {
                  this.templateId = id;
              },
              get contohPid() {
                  const kunci = Object.keys(this.pnppData || {});
                  return kunci.length > 0 ? Number(kunci[0]) : null;
              },
              tanggalIndonesia(iso) {
                  if (!iso) return '—';
                  const d = new Date(iso + 'T00:00:00');
                  return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
              },
              get poliNama() {
                  return Object.keys(this.poliTerpilih)
                      .filter((i) => this.poliTerpilih[i])
                      .map((i) => this.poliData[i])
                      .filter(Boolean)
                      .join(', ');
              },
              get pasienPreview() {
                  return Object.keys(this.terpilih).filter((i) => this.terpilih[i]);
              },
              nilaiToken(pid, token) {
                  const d = this.pnppData[pid] || {};
                  switch (token) {
                      case 'nama': return d.nama || '—';
                      case 'nip': return d.nip || '—';
                      case 'satker': return d.satker || '—';
                      case 'hari_tanggal': return this.tanggal ? this.tanggalIndonesia(this.tanggal) : '—';
                      case 'tanggal': return this.tanggal || '—';
                      case 'waktu_kunjungan':
                      case 'jam': return this.jam || '—';
                      case 'poli':
                      case 'instalasi':
                      case 'poli_layanan': return this.poliNama || '—';
                      default: return '—';
                  }
              },
              previewFor(pid) {
                  const t = this.activeTemplate();
                  if (!t) return '';
                  return t.konten.replace(/\{+([a-z_]+)\}+/gi, (cocok, token) => {
                      const isi = this.nilaiToken(pid, token.toLowerCase());
                      return isi !== '' ? isi : '—';
                  });
              },
              togglePoliSemua() {
                  const aktif = !this.semuaPoli;
                  this.semuaPoli = aktif;
                  this.poliIdSemua.forEach(i => this.poliTerpilih[i] = aktif);
              },
              toggleSemua() {
                  this.semua = !this.semua;
                  const nilai = this.semua;
                  this.semuaId.forEach(i => this.terpilih[i] = nilai);
              }
          }"
          x-init="poliIdSemua.forEach(i => poliTerpilih[i] = oldPoliIds.includes(String(i)) || poliAwal.includes(String(i)))"
          class="space-y-6">
        @csrf

        {{-- ===== Langkah 1b: tabel pasien ===== --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">
                            Pasien <span class="font-medium text-slate-400">({{ $pnpps->count() }} hasil filter)</span>
                        </h2>
                        <p class="mt-0.5 text-xs text-slate-500">Centang pasien — semua yang dipilih mendapat jadwal yang sama.</p>
                    </div>
                    <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 ring-1 ring-inset ring-sky-300/60">
                        <span x-text="jumlah"></span> dipilih
                    </span>
                </div>
            </div>

            @if ($pnpps->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-slate-400">Tidak ada pasien yang cocok dengan filter.</p>
            @else
                <div class="max-h-[420px] overflow-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="sticky top-0 bg-slate-50/95 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400 backdrop-blur">
                            <tr>
                                <th class="w-10 px-5 py-3">
                                    <input type="checkbox" :checked="semua" @change="toggleSemua()"
                                           class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" title="Pilih semua hasil filter">
                                </th>
                                <th class="px-5 py-3">Pasien</th>
                                <th class="px-5 py-3">No. WhatsApp</th>
                                <th class="px-5 py-3">Satker</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($pnpps as $p)
                                @php($validWa = \App\Broadcasting\PhoneFormat::toWa($p->no_hp))
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-5 py-3">
                                        <input type="checkbox" name="pnpp_ids[]" value="{{ $p->id }}" x-model="terpilih[{{ $p->id }}]"
                                               class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                    </td>
                                    <td class="px-5 py-3">
                                        <p class="font-semibold text-slate-800">{{ $p->nama }}</p>
                                        <p class="text-xs text-slate-400">NIP {{ $p->nip ?? '—' }}</p>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="font-mono text-xs {{ $validWa ? 'text-slate-600' : 'text-rose-400' }}">{{ $p->no_hp ?? '—' }}</span>
                                        @if (! $validWa)
                                            <span class="ml-1 text-[10px] font-semibold text-rose-400">(tidak valid)</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-xs text-slate-500">{{ $p->satker?->nama ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            @error('pnpp_ids')<p class="border-t border-slate-100 px-5 py-3 text-xs text-rose-500">{{ $message }}</p>@enderror
        </div>

        {{-- ===== Langkah 2: chips poli + pengaturan shared ===== --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">2. Atur Jadwal</h2>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Centang beberapa poli sekaligus — pengaturan (tanggal/jam/catatan) dipakai semua poli. Dokter diisi belakangan lewat <em>edit</em> karena beda-beda antar poli.
                        </p>
                    </div>
                    <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 ring-1 ring-inset ring-sky-300/60">
                        <span x-text="jumlahPoli"></span> poli
                    </span>
                </div>
            </div>
            <div class="space-y-5 p-5">
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Poli yang Dituju <span class="text-rose-500">*</span></label>
                        <button type="button" @click="togglePoliSemua()"
                                class="text-[11px] font-semibold text-sky-600 transition hover:text-sky-800"
                                x-text="semuaPoli ? 'Hapus semua' : 'Pilih semua'"></button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($polis as $po)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="poli_ids[]" value="{{ $po->id }}" x-model="poliTerpilih[{{ $po->id }}]"
                                       class="peer sr-only">
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition peer-checked:border-sky-400 peer-checked:bg-sky-50 peer-checked:text-sky-700 hover:border-slate-300">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                    </svg>
                                    {{ $po->nama }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('poli_ids')<p class="mt-2 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tanggal <span class="text-rose-500">*</span></label>
                        <input type="date" name="tanggal" x-model="tanggal" value="{{ old('tanggal') }}" min="{{ today()->format('Y-m-d') }}" required
                               class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        @error('tanggal')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Jam <span class="text-rose-500">*</span></label>
                        <input type="time" name="jam" x-model="jam" value="{{ old('jam') }}" required
                               class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        @error('jam')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Jenis Kunjungan</label>
                        <div class="flex flex-wrap gap-3">
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-600 transition has-[:checked]:border-sky-400 has-[:checked]:bg-sky-50 has-[:checked]:text-sky-700">
                                <input type="radio" name="home_visit" value="0" {{ old('home_visit', '0') === '0' ? 'checked' : '' }}
                                       class="h-4 w-4 border-slate-300 text-sky-600 focus:ring-sky-500">
                                Kunjungan di RS
                            </label>
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-600 transition has-[:checked]:border-teal-400 has-[:checked]:bg-teal-50 has-[:checked]:text-teal-700">
                                <input type="radio" name="home_visit" value="1" {{ old('home_visit') === '1' ? 'checked' : '' }}
                                       class="h-4 w-4 border-slate-300 text-teal-600 focus:ring-teal-500">
                                Home Visit
                            </label>
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Catatan</label>
                        <textarea name="catatan" rows="2" maxlength="500" placeholder="Catatan internal untuk jadwal ini (opsional)…"
                                  class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('catatan') }}</textarea>
                        @error('catatan')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Template Pesan WhatsApp</label>
                        <select name="message_template_id" x-model="templateId" @change="pilihTemplate($el.value)"
                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                            <option value="">Default — mengikuti aturan modul (Outreach / Follow Up)</option>
                            @foreach ($templates as $tpl)
                                <option value="{{ $tpl->id }}" {{ (string) old('message_template_id') === (string) $tpl->id ? 'selected' : '' }}>{{ $tpl->judul }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-400">Opsional — hanya template kategori <strong>Digital Reminder</strong>. Bila dipilih, semua jadwal dari form ini memakai template ini (menggantikan default aturan modul) saat pesan digenerate. Pratinjau tampil di bawah begitu template dipilih — variabel terisi otomatis dari tanggal/jam/poli di atas.</p>
                        @error('message_template_id')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- ===== Pratinjau per pasien ===== --}}
                <div x-show="activeTemplate()" x-cloak class="border-t border-slate-100 pt-5">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Pratinjau Pesan</h3>
                            <p class="mt-0.5 text-xs text-slate-500">Isi pesan mengikuti template terpilih, tanggal/jam, dan poli di atas — variabel terisi otomatis, token yang belum terisi ditandai <code class="rounded bg-slate-100 px-1 text-[10px]"> — </code>.</p>
                        </div>
                        <span class="rounded-full bg-sky-50 px-2.5 py-0.5 text-[11px] font-semibold text-sky-700 ring-1 ring-inset ring-sky-200"
                              x-text="pasienPreview.length ? (pasienPreview.length + ' pasien') : 'contoh'"></span>
                    </div>
                    <div class="space-y-3">
                        <template x-for="pid in pasienPreview" :key="pid">
                            <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-800" x-text="(pnppData[pid] || {}).nama || '#' + pid"></span>
                                    <span class="text-xs text-slate-400" x-text="(pnppData[pid] || {}).nip || ''"></span>
                                    <span class="ml-auto text-xs text-slate-400" x-text="(pnppData[pid] || {}).satker || ''"></span>
                                </div>
                                <div class="mt-2.5 rounded-xl bg-[#dcf8c6] px-3 py-2.5 ring-1 ring-inset ring-emerald-200/60">
                                    <pre class="whitespace-pre-line text-xs leading-relaxed text-slate-800" x-text="previewFor(pid)"></pre>
                                </div>
                            </div>
                        </template>
                        <template x-if="pasienPreview.length === 0 && contohPid !== null" x-cloak>
                            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/60 p-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-800" x-text="(pnppData[contohPid] || {}).nama || '#' + contohPid"></span>
                                    <span class="ml-auto text-xs text-slate-400">contoh pratinjau</span>
                                </div>
                                <div class="mt-2.5 rounded-xl bg-[#dcf8c6] px-3 py-2.5 ring-1 ring-inset ring-emerald-200/60">
                                    <pre class="whitespace-pre-line text-xs leading-relaxed text-slate-800" x-text="previewFor(contohPid)"></pre>
                                </div>
                            </div>
                        </template>
                        <p x-show="pasienPreview.length === 0" x-cloak class="text-[11px] text-slate-400">Pratinjau memakai pasien pertama hasil filter sebagai contoh — centang pasien di langkah 1 untuk pratinjau per pasien.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Bar aksi ===== --}}
        <div class="sticky bottom-0 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur">
            <p class="text-sm text-slate-500">
                <span class="font-bold text-slate-800" x-text="jumlah"></span> pasien ×
                <span class="font-bold text-slate-800" x-text="jumlahPoli"></span> poli =
                <span class="font-bold text-sky-700" x-text="totalJadwal"></span> jadwal
            </p>
            <button type="submit" :disabled="totalJadwal === 0"
                    class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-40">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <span x-text="'Buat ' + totalJadwal + ' Jadwal'"></span>
            </button>
        </div>
    </form>
</div>
@endsection
