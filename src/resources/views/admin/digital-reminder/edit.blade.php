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
              dokterId: '{{ old('dokter_id', $reminder->dokter_id) }}',
              homeVisit: '{{ old('home_visit', $reminder->home_visit ? '1' : '0') }}',
              tanggal: '{{ old('tanggal', $reminder->tanggal?->format('Y-m-d')) }}',
              jam: '{{ old('jam', $reminder->jam?->format('H:i')) }}',
              dokters: @js($dokters->map(fn ($d) => ['id' => $d->id, 'nama' => $d->nama, 'poli_id' => $d->poli_id])),
              templates: @js($templates->map(fn ($t) => ['id' => (int) $t->id, 'judul' => (string) $t->judul, 'konten' => (string) $t->konten, 'token' => $t->tokenParam()])->values()),
              templateId: @js((string) old('message_template_id', $reminder->message_template_id)),
              varsKustom: @js(old('vars_kustom', $reminder->vars_kustom) ?? []),
              tokenJadwal: ['poli', 'instalasi', 'poli_layanan', 'dokter', 'tanggal', 'jam', 'hari_tanggal', 'waktu_kunjungan'],
              poliData: @js($polis->mapWithKeys(fn ($po) => [(string) $po->id => $po->nama])),
              pnppPid: {{ (int) $reminder->pnpp_id }},
              pnppData: @js([(string) $reminder->pnpp_id => ['nama' => $reminder->pnpp?->nama ?? '—', 'nip' => $reminder->pnpp?->nip ?? '', 'satker' => $reminder->pnpp?->satker?->nama ?? '']]),
              activeTemplate() {
                  return this.templates.find((t) => String(t.id) === String(this.templateId)) ?? null;
              },
              pilihTemplate(id) {
                  this.templateId = id;
              },
              tokenPribadi() {
                  return ['nama', 'nama_pnpp', 'nip', 'nip_pnpp', 'satker', 'satker_pnpp'];
              },
              tokenLabel(token) {
                  const labels = { nama: 'Nama', nama_pnpp: 'Nama', nip: 'NIP', nip_pnpp: 'NIP', satker: 'Satker', satker_pnpp: 'Satker', obat: 'Obat', poli: 'Poli', instalasi: 'Instalasi', dokter: 'Dokter', tanggal: 'Tanggal', jam: 'Jam', hari_tanggal: 'Hari/Tanggal', waktu_kunjungan: 'Waktu', poli_layanan: 'Poli Layanan' };
                  return labels[token] || token;
              },
              tanggalIndonesia(iso) {
                  if (!iso) return '—';
                  const d = new Date(iso + 'T00:00:00');
                  return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
              },
              get dokterNama() {
                  const d = this.dokters.find((x) => String(x.id) === String(this.dokterId));
                  return d ? d.nama : '';
              },
              get dokterOptions() { return this.dokters.filter(d => d.poli_id == this.poliId) },
nilaiToken(token) {
                   switch (token) {
                       case 'nama': case 'nama_pnpp': return this.pnppData[this.pnppPid]?.nama || '—';
                       case 'nip': case 'nip_pnpp': return this.pnppData[this.pnppPid]?.nip || '—';
                       case 'satker': case 'satker_pnpp': return this.pnppData[this.pnppPid]?.satker || '—';
                       case 'hari_tanggal': return this.tanggal ? this.tanggalIndonesia(this.tanggal) : '—';
                       case 'tanggal': return this.tanggal || '—';
                       case 'waktu_kunjungan':
                       case 'jam': return this.jam || '—';
                       case 'poli':
                       case 'instalasi':
                       case 'poli_layanan': return this.poliData[this.poliId] || '—';
                       case 'dokter': return this.dokterNama || '—';
                       default: return '—';
                   }
               },
              previewFor() {
                  const t = this.activeTemplate();
                  if (!t) return '';
                  return t.konten.replace(/\{+([a-z_]+)\}+/gi, (cocok, token) => {
                      const isi = this.nilaiToken(token.toLowerCase());
                      return isi !== '' ? isi : '—';
                  });
              }
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
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Poli <span x-show="homeVisit === '0'" class="text-rose-500">*</span></label>
                    <select name="poli_id" x-model="poliId" :required="homeVisit === '0'"
                            class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        <option value="" disabled>— Pilih poli —</option>
                        @foreach ($polis as $po)
                            <option value="{{ $po->id }}" {{ old('poli_id', $reminder->poli_id) == $po->id ? 'selected' : '' }}>{{ $po->nama }}</option>
                        @endforeach
                    </select>
                    @error('poli_id')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Dokter</label>
                    <select name="dokter_id" x-model="dokterId" :disabled="!poliId"
                            class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400">
                        <option value="">— Opsional —</option>
                        @foreach ($dokters as $d)
                            <option value="{{ $d->id }}" data-poli="{{ $d->poli_id }}"
                                    {{ (string) old('dokter_id', $reminder->dokter_id) === (string) $d->id ? 'selected' : '' }}>{{ $d->nama }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Sebaiknya sesuai poli — validasi menolak dokter dari poli lain. Untuk home visit tanpa poli, dokter dikosongkan.</p>
                    @error('dokter_id')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tanggal <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal" x-model="tanggal" value="{{ old('tanggal', $reminder->tanggal?->format('Y-m-d')) }}" required
                           class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    @error('tanggal')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Jam <span class="text-rose-500">*</span></label>
                    <input type="time" name="jam" x-model="jam" value="{{ old('jam', $reminder->jam?->format('H:i')) }}" required
                           class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    @error('jam')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Jenis Kunjungan</label>
                    <div class="flex flex-wrap gap-3">
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-600 transition has-[:checked]:border-sky-400 has-[:checked]:bg-sky-50 has-[:checked]:text-sky-700">
                            <input type="radio" name="home_visit" value="0" x-model="homeVisit" {{ old('home_visit', $reminder->home_visit ? '1' : '0') === '0' ? 'checked' : '' }}
                                   class="h-4 w-4 border-slate-300 text-sky-600 focus:ring-sky-500">
                            Kunjungan di RS
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-600 transition has-[:checked]:border-teal-400 has-[:checked]:bg-teal-50 has-[:checked]:text-teal-700">
                            <input type="radio" name="home_visit" value="1" x-model="homeVisit" {{ old('home_visit', $reminder->home_visit ? '1' : '0') === '1' ? 'checked' : '' }}
                                   class="h-4 w-4 border-slate-300 text-teal-600 focus:ring-teal-500">
                            Home Visit
                        </label>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">Home visit tidak wajib memilih poli — biarkan kosong untuk jadwal kunjungan ke rumah pasien.</p>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Status</label>
                    <select name="status" required
                            class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 md:w-1/3">
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
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Template Pesan WhatsApp</label>
                    <select name="message_template_id" x-model="templateId" @change="pilihTemplate($el.value)"
                            class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        <option value="">Default — mengikuti aturan modul (Outreach / Follow Up)</option>
                        @foreach ($templates as $tpl)
                            <option value="{{ $tpl->id }}" {{ (string) old('message_template_id', $reminder->message_template_id) === (string) $tpl->id ? 'selected' : '' }}>{{ $tpl->judul }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Opsional — hanya template kategori <strong>Digital Reminder</strong>. Bila dipilih, pesan untuk jadwal ini memakai template ini (menggantikan default aturan modul) saat digenerate.</p>
                    @error('message_template_id')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- ===== Variabel pesan kustom ===== --}}
            <div x-show="activeTemplate() && activeTemplate().token.length > 0" x-cloak
                 class="space-y-3 border-t border-slate-100 px-5 py-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Variabel Pesan</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Isi manual untuk menimpa nilai otomatis. Kosongkan untuk pakai nilai dari data pasien / jadwal.</p>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <template x-for="token in (activeTemplate()?.token ?? [])" :key="token">
                        <div>
                            <label class="mb-1 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                <code class="rounded bg-slate-100 px-1 py-0.5 text-[10px] text-sky-700" x-text="'{' + token + '}'"></code>
                                <span x-text="tokenLabel(token)"></span>
                            </label>
                            <input type="text"
                                   :name="'vars_kustom[' + token + ']'"
                                   x-model="varsKustom[token]"
                                   :readonly="tokenPribadi().includes(token)"
                                   :placeholder="tokenPribadi().includes(token) ? 'otomatis per penerima' : 'kosong = otomatis'"
                                   class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400">
                        </div>
                    </template>
                </div>
            </div>

            {{-- ===== Pratinjau pesan ===== --}}
            <div x-show="activeTemplate()" x-cloak
                 class="space-y-3 border-t border-slate-100 px-5 py-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Pratinjau Pesan</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Menyesuaikan tanggal, jam, poli, dan dokter di atas — token yang belum terisi ditandai <code class="rounded bg-slate-100 px-1 text-[10px]"> — </code>.</p>
                </div>
                <div class="max-w-xl rounded-xl bg-[#dcf8c6] px-3 py-2.5 ring-1 ring-inset ring-emerald-200/60">
                    <pre class="whitespace-pre-line text-xs leading-relaxed text-slate-800" x-text="previewFor()"></pre>
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
                           class="h-10 w-full max-w-xs rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    @error('tanggal_kunjungan')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Poli yang Dikunjungi</p>
                    <div class="space-y-2">
                        @if ($reminder->poli_id)
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
                                           class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    <input type="text" name="polis[{{ $reminder->poli_id }}][diagnosa]" maxlength="1000" value="{{ old('polis.'.$reminder->poli_id.'.diagnosa') }}" placeholder="Diagnosa di poli ini (opsional)…"
                                           class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                </div>
                            </div>
                        @else
                            <p class="rounded-xl border border-dashed border-teal-300 bg-teal-50/40 px-4 py-3 text-xs text-teal-800">
                                Jadwal ini home visit tanpa poli terjadwal — centang poli yang benar-benar dikunjungi, atau biarkan kosong untuk mencatat tanpa poli.
                            </p>
                        @endif

                        {{-- Poli lain (atau semua poli untuk home visit): opsional via checklist --}}
                        @foreach (($reminder->poli_id ? $polis->where('id', '!==', $reminder->poli_id) : $polis) as $po)
                            <div>
                                <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 transition has-[:checked]:border-emerald-300 has-[:checked]:bg-emerald-50/60 has-[:checked]:text-emerald-800">
                                    <input type="checkbox" name="poli_pilih[]" value="{{ $po->id }}" x-model="pilih[{{ $po->id }}]"
                                           class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    {{ $po->nama }}
                                </label>
                                <div x-show="pilih[{{ $po->id }}]" x-cloak class="mt-2 grid grid-cols-1 gap-3 px-1 sm:grid-cols-2">
                                    <input type="text" name="polis[{{ $po->id }}][keluhan]" maxlength="1000" value="{{ old('polis.'.$po->id.'.keluhan') }}" placeholder="Keluhan di poli ini (opsional)…"
                                           class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    <input type="text" name="polis[{{ $po->id }}][diagnosa]" maxlength="1000" value="{{ old('polis.'.$po->id.'.diagnosa') }}" placeholder="Diagnosa di poli ini (opsional)…"
                                           class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
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
