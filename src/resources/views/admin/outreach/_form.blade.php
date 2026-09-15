@php
    $method = $method ?? 'POST';
    $kirimPesan = $kirimPesan ?? true;
    $jenis = $jenis ?? 'outreach';
    $jenisLabel = $jenisLabel ?? 'Outreach';
    $jenisDesc = $jenisDesc ?? 'Undangan jadwal (riwayat modul Outreach).';
    $tokenJadwal = ['hari_tanggal', 'waktu_kunjungan', 'poli_layanan', 'poli', 'dokter', 'tanggal', 'jam'];
    $alpineOutreachData = [
        'selected' => $selectedIds,
        'allIds' => $canKirimIds,
        'templates' => $templates ?? [],
        'templateId' => $templateId ?? null,
        'vars' => (object) ($varsAwal ?? []),
        'reminderIds' => (object) ($reminderAwal ?? []),
        'reminders' => $reminders ?? [],
        'pnppData' => (object) ($pnppData ?? []),
        'poliOptions' => $poliOptions ?? [],
    ];
@endphp
<script>
    window.alpineOutreachData = @json($alpineOutreachData);
</script>
<form action="{{ $action }}" method="POST" class="space-y-6"
      x-data="{
          ...(window.alpineOutreachData || {}),
          tokenJadwal: ['hari_tanggal', 'waktu_kunjungan', 'poli_layanan', 'poli', 'dokter', 'tanggal', 'jam'],
          waktuBawaan() {
              const d = new Date(Date.now() + 15 * 60000);
              const p = (n) => String(n).padStart(2, '0');
              return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + 'T' + p(d.getHours()) + ':' + p(d.getMinutes());
          },
          activeTemplate() {
              return this.templates.find((t) => String(t.id) === String(this.templateId)) ?? null;
          },
          tokenList() {
              return this.activeTemplate()?.token ?? [];
          },
          pilihTemplate(id) {
              this.templateId = id;
              this.vars = {};
              this.$nextTick(() => this.isiOtomatisVars());
          },
          refPid() {
              if (this.selected.length > 0) return this.selected[0];
              const kunci = Object.keys(this.pnppData || {});
              return kunci.length > 0 ? Number(kunci[0]) : null;
          },
tokenPribadi() {
               return ['nama', 'nama_pnpp', 'nip', 'nip_pnpp', 'satker', 'satker_pnpp'];
           },
          isiOtomatisVars() {
              this.vars = this.vars || {};
              this.autovars = this.autovars || {};
              for (const token of Object.keys(this.autovars)) {
                  delete this.vars[token];
                  delete this.autovars[token];
              }
              const sasaran = this.selected.length > 0
                  ? this.selected
                  : Object.keys(this.pnppData || {}).map(Number);
              if (sasaran.length === 0) return;
              for (const token of this.tokenList()) {
                  if (this.tokenPribadi().includes(token)) continue;
                  let isiSama = null;
                  let punya = false;
                  for (const pid of sasaran) {
                      const nilai = this.nilaiToken(pid, token);
                      if (nilai === '') continue;
                      punya = true;
                      if (isiSama === null) {
                          isiSama = nilai;
                      } else if (isiSama !== nilai) {
                          isiSama = '__BEDA__';
                      }
                  }
                  if (punya && isiSama !== '__BEDA__' && isiSama !== null) {
                      this.vars[token] = isiSama;
                      this.autovars[token] = true;
                  }
              }
          },
          isiOtomatisReferensi() {
              for (const pid of Object.keys(this.pnppData || {})) {
                  if (this.reminderIds[pid]) continue;
                  const r = this.remindersUntuk(Number(pid))[0];
                  if (r) this.reminderIds[pid] = r.id;
              }
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
              this.isiOtomatisVars();
          },
          get templatesJadwal() {
              const token = this.activeTemplate()?.token ?? [];
              return this.tokenJadwal.some((t) => token.includes(t));
          },
          remindersUntuk(pid) {
              return (this.reminders || []).filter((r) => Number(r.pnpp_id) === Number(pid));
          },
          nilaiToken(pid, token) {
              const manual = this.vars[token] || '';
              if (manual.trim() !== '') return manual;

              const rid = this.reminderIds[pid];
              const r = rid ? (this.reminders || []).find((x) => Number(x.id) === Number(rid)) : null;
              if (r && r.nilai && r.nilai[token]) return r.nilai[token];

              const d = this.pnppData[pid] || {};
              if (d[token]) return d[token];

              return '';
          },
          previewFor(pid) {
              const s = this.activeTemplate().konten;
              return s.replace(/\{+([a-z_]+)\}+/gi, (cocok, token) => {
                  const isi = this.nilaiToken(pid, token.toLowerCase());
                  return isi !== '' ? isi : '—';
              });
}
              }"
      x-init="isiOtomatisReferensi(); isiOtomatisVars()">
    @csrf
    @if ($method === 'PUT')
        @method('PUT')
    @endif

    {{-- ===== Penerima (target dari data PNPP) ===== --}}
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
                <span>Centang semua</span>
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
                            @php($wajib = in_array((int) $p->id, $selectedIds, true))
                            <tr class="{{ $validWa || $wajib ? '' : 'opacity-60' }}">
                                <td class="px-5 py-3">
                                    @if ($validWa || $wajib)
                                        <input type="checkbox" name="pnpp_ids[]" value="{{ $p->id }}"
                                               x-model="selected" @change="isiOtomatisVars()"
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

    {{-- ===== Template & pesan ===== --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-sm font-bold text-slate-900">Template &amp; Pesan</h2>
            <p class="mt-0.5 text-xs text-slate-500">
                Pilih template pesan sebagai format kiriman. Token jadwal ({hari_tanggal}, {waktu_kunjungan},
                {poli_layanan}) terisi otomatis dari jadwal Digital Reminder pasien.
            </p>
        </div>
        <div class="space-y-4 px-5 py-4">
            <div>
                <label for="message_template_id" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Template</label>
                <select x-model="templateId" name="message_template_id" id="message_template_id" required
                        @change="pilihTemplate($el.value)"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:w-96">
                    <option value="" disabled selected>Pilih template…</option>
                    @foreach ($templates as $t)
                        <option value="{{ $t['id'] }}">{{ $t['judul'] }} — {{ $t['token'] ? '{'.implode(', ', $t['token']).'}' : 'tanpa variabel' }}</option>
                    @endforeach
                </select>
            </div>

            <div x-show="activeTemplate() !== null" x-cloak class="space-y-4">
                <div>
                    <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
                        <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Variabel pesan</label>
                        <button type="button" @click="isiOtomatisVars()"
                                class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-sky-700 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-200">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                            </svg>
                            Isi ulang otomatis
                        </button>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <template x-for="(token) in tokenList()" :key="token">
                            <div>
                                <label class="mb-1 flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                                    <code class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] text-sky-700" x-text="'{' + token + '}'"></code>
                                </label>
                                <template x-if="['poli', 'instalasi'].includes(token)">
                                    <select :name="'vars[' + token + ']'"
                                            x-model="vars[token]"
                                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                        <option value="">Otomatis dari data pasien…</option>
                                        <template x-for="o in poliOptions" :key="o">
                                            <option :value="o" x-text="o"></option>
                                        </template>
                                    </select>
                                </template>
                                <template x-if="token === 'tanggal'">
                                    <input type="date" :name="'vars[' + token + ']'" x-model="vars[token]"
                                           class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                </template>
                                <template x-if="token !== 'tanggal' && !['poli', 'instalasi'].includes(token)">
                                    <input type="text" :name="'vars[' + token + ']'" x-model="vars[token]"
                                           maxlength="255"
                                           class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                           x-bind:placeholder="tokenPribadi().includes(token) ? 'otomatis per penerima ({' + token + '})' : 'kosong = otomatis ({' + token + '})'">
                                </template>
                                <p class="mt-0.5 text-[11px] text-slate-400">Terisi otomatis dari pasien/jadwal acuan dan bisa diubah; {nama}/{nip}/{satker} diisi per penerima pada pratinjau.</p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- ===== Pratinjau per penerima ===== --}}
            <div x-show="activeTemplate() !== null" x-cloak class="border-t border-slate-100 pt-4">
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Pratinjau per Penerima</h3>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Isi pesan untuk masing-masing penerima sesuai variabel &amp; jadwal yang dipilih.
                            {nama} dan variabel lain terisi otomatis per penerima dari data pasien &amp; referensi jadwal.
                        </p>
                    </div>
                    <span class="ml-auto rounded-full bg-sky-50 px-2.5 py-0.5 text-[11px] font-semibold text-sky-700 ring-1 ring-inset ring-sky-200" x-text="selected.length + ' penerima'"></span>
                </div>
                <div class="space-y-4">
                    <template x-for="pid in selected" :key="pid">
                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold text-slate-800" x-text="(pnppData[pid] || {}).nama || '#' + pid"></span>
                                <span class="text-xs text-slate-400" x-text="(pnppData[pid] || {}).nip || ''"></span>
                                <span class="ml-auto text-xs text-slate-400" x-text="(pnppData[pid] || {}).satker || ''"></span>
                            </div>

                            <div x-show="templatesJadwal" x-cloak class="mt-2.5">
                                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Referensi jadwal Digital Reminder</label>
                                <select x-model="reminderIds[pid]"
                                        :name="'reminder_ids[' + pid + ']'"
                                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    <option value="">Isi manual / tanpa jadwal</option>
                                    <template x-for="r in remindersUntuk(pid)" :key="r.id">
                                        <option :value="r.id" x-text="r.label"></option>
                                    </template>
                                </select>
                                <p class="mt-0.5 text-[11px] text-slate-400">
                                    Token {hari_tanggal}, {waktu_kunjungan}, dan {poli_layanan} ikut terisi dari jadwal yang dipilih.
                                </p>
                            </div>

                            <div class="mt-2.5 rounded-xl bg-[#dcf8c6] px-3 py-2.5 ring-1 ring-inset ring-emerald-200/60">
                                <pre class="whitespace-pre-line text-xs leading-relaxed text-slate-800" x-text="previewFor(pid)"></pre>
                            </div>
                        </div>
                    </template>
                    <template x-if="selected.length === 0 && refPid() !== null" x-cloak>
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/60 p-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold text-slate-800" x-text="(pnppData[refPid()] || {}).nama || '#' + refPid()"></span>
                                <span class="ml-auto text-xs text-slate-400">contoh pratinjau</span>
                            </div>
                            <div class="mt-2.5 rounded-xl bg-[#dcf8c6] px-3 py-2.5 ring-1 ring-inset ring-emerald-200/60">
                                <pre class="whitespace-pre-line text-xs leading-relaxed text-slate-800" x-text="previewFor(refPid())"></pre>
                            </div>
                        </div>
                    </template>
                    <template x-if="selected.length === 0 && refPid() === null" x-cloak>
                        <p class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-xs text-slate-400">Tidak ada pasien hasil filter — perbarui saringan untuk melihat pratinjau.</p>
                    </template>
                    <p x-show="selected.length === 0 && refPid() !== null" x-cloak class="text-[11px] text-slate-400">Pratinjau memakai pasien pertama hasil filter sebagai contoh — centang penerima untuk pratinjau per orang.</p>
                </div>
            </div>
        </div>
    </div>
    
    @if ($kirimPesan)
        {{-- ===== Jenis pesan ===== --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-bold text-slate-900">Jenis Pesan</h2>
                <p class="mt-0.5 text-xs text-slate-500">Pesan akan tersimpan di riwayat modul yang dipilih.</p>
            </div>
            <div class="space-y-3 px-5 py-4">
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 transition has-[:checked]:border-sky-400 has-[:checked]:bg-sky-50/60">
                    <input type="radio" name="jenis" value="{{ $jenis }}" checked class="mt-1 h-4 w-4 border-slate-300 text-sky-600 focus:ring-sky-500">
                    <span>
                        <span class="block text-sm font-semibold text-slate-800">{{ $jenisLabel }}</span>
                        <span class="block text-xs text-slate-500">{{ $jenisDesc }}</span>
                    </span>
                </label>
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
                {{-- <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 transition has-[:checked]:border-sky-400 has-[:checked]:bg-sky-50/60">
                    <input type="radio" name="mode" value="jadwalkan" x-model="mode" class="mt-1 h-4 w-4 border-slate-300 text-sky-600 focus:ring-sky-500">
                    <span>
                        <span class="block text-sm font-semibold text-slate-800">Jadwalkan</span>
                        <span class="block text-xs text-slate-500">Sistem akan mengirim pesan otomatis pada waktu yang Anda pilih.</span>
                    </span>
                </label> --}}
                <div x-show="mode === 'jadwalkan'" x-cloak
                     class="flex flex-wrap items-end gap-3 rounded-xl bg-slate-50/60 p-3 ring-1 ring-inset ring-slate-200">
                    <div>
                        <label for="kirim_pada" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Waktu pengiriman</label>
                        <input type="datetime-local" name="kirim_pada" id="kirim_pada"
                               :value="mode === 'jadwalkan' ? waktuBawaan() : ''"
                               class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <p class="text-xs text-slate-400">Gunakan format 24 jam (contoh: 14:30). Waktu minimal 1 menit ke depan.</p>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== Ringkasan & tombol ===== --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-xs text-slate-500">
                @if ($kirimPesan)
                    <p>
                        Akan mengirim pesan ke <strong class="text-slate-800" x-text="selected.length">0</strong> pasien
                        menggunakan format resmi WhatsApp Business.
                    </p>
                @else
                    <p>
                        Perubahan akan tersimpan untuk <strong class="text-slate-800" x-text="selected.length">0</strong> pasien.
                        Pesan yang sudah terkirim di grup ini tidak ikut diubah.
                    </p>
                @endif
            </div>
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                @if ($kirimPesan)
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.998 12Zm0 0h7.5" /></svg>
                @else
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                @endif
                <span x-text="mode === 'jadwalkan' && {{ $kirimPesan ? 'true' : 'false' }} ? 'Jadwalkan Kirim' : '{{ $submitLabel }}'"></span>
            </button>
        </div>
    </div>
</form>