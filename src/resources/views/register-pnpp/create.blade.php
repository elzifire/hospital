<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Registrasi PNPP — RS Bhayangkara Bogor</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none !important;}</style>
  </head>
  <body class="bg-slate-100 antialiased">

<div class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6 lg:px-8" x-data="registerForm()">

    @php
        // ---- Style tokens ---------------------------------------------------
        // Satu sumber kebenaran untuk class Tailwind yang berulang. Kalau mau
        // ganti warna/ukuran input misalnya, cukup ubah di sini.
        $inputClass = 'block w-full rounded-xl border-0 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-sky-500';
        $labelClass = 'mb-1.5 block text-sm font-semibold text-slate-700';
        $errorClass = 'mt-1.5 text-xs font-medium text-rose-600';
        $hintClass = 'mt-1 text-[11px] text-slate-400';
        $requiredMark = '<span class="text-rose-500">*</span>';
        $optionalTag = '<span class="text-[11px] font-normal text-slate-400">(opsional)</span>';
        $sectionTitleClass = 'mb-4 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-sky-700';
        $sectionBadgeClass = 'flex h-6 w-6 items-center justify-center rounded-full bg-sky-600 text-[11px] text-white';
        $radioLabelClass = 'inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700';
        $radioInputClass = 'h-4 w-4 border-slate-300 text-sky-600 focus:ring-sky-500';
        $checkboxItemClass = 'flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 transition hover:border-sky-300 hover:bg-sky-50/50';
        $checkboxInputClass = 'h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500';

        // ---- Definisi field "Data Diri" --------------------------------------
        // Ketujuh field ini punya bentuk markup yang sama persis
        // (label + input/textarea + error), jadi cukup dideskripsikan sebagai
        // data lalu di-loop, daripada ditulis manual tujuh kali.
        $dataDiriFields = [
            ['name' => 'nama', 'label' => 'Nama Lengkap', 'required' => true, 'span' => 2],
            ['name' => 'nik', 'label' => 'NIK', 'optional' => true,
                'attrs' => 'inputmode="numeric" maxlength="16"', 'placeholder' => 'Nomor Induk Kependudukan'],
            ['name' => 'nip', 'label' => 'NIP/NRP', 'required' => true,
                'attrs' => 'inputmode="numeric" maxlength="50"', 'placeholder' => 'Nomor Induk Pegawai'],
            ['name' => 'jabatan', 'label' => 'Jabatan', 'required' => true, 'span' => 2],
            ['name' => 'ttl', 'label' => 'Tempat, Tanggal Lahir', 'required' => true,
                'placeholder' => 'mis. Bogor, 12 Mei 1990'],
            ['name' => 'no_hp', 'label' => 'No. HP', 'required' => true,
                'attrs' => 'inputmode="tel"', 'hint' => 'Untuk konfirmasi status persetujuan.'],
            ['name' => 'alamat', 'label' => 'Alamat', 'required' => true, 'span' => 2, 'type' => 'textarea'],
        ];

        // ---- Map poli -> jam layanan, dipakai untuk cek jam live di Alpine ----
        $poliJadwal = $polis->mapWithKeys(fn ($p) => [$p->id => [
            'nama' => $p->nama,
            'buka' => $p->jam_buka?->format('H:i'),
            'tutup' => $p->jam_tutup?->format('H:i'),
        ]])->all();
    @endphp

    <div class="w-full max-w-4xl overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-slate-900/5">

        {{-- Header --}}
        <div class="border-b border-slate-100 bg-gradient-to-r from-sky-50 to-slate-50 px-8 py-6">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-sky-600 text-white shadow-sm">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">Registrasi PNPP</h1>
                    <p class="text-sm text-slate-500">Pendaftaran online untuk pegawai yang menerima layanan kesehatan di RS Bhayangkara Bogor.</p>
                </div>
            </div>
        </div>

        <div class="px-8 py-6">
            @if ($errors->any())
                <div class="mb-6 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800">
                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                    <div class="text-sm">
                        <p class="font-bold">Mohon perbaiki isian berikut:</p>
                        <ul class="mt-1 list-inside list-disc space-y-0.5">
                            @foreach ($errors->all() as $pesan)
                                <li>{{ $pesan }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('register-pnpp.store') }}" class="space-y-8">
                @csrf

                {{-- ===== 1. Data diri ===== --}}
                <section>
                    <h2 class="{{ $sectionTitleClass }}">
                        <span class="{{ $sectionBadgeClass }}">1</span>
                        Data Diri
                    </h2>

                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($dataDiriFields as $field)
                            <div class="{{ ($field['span'] ?? 1) === 2 ? 'sm:col-span-2' : '' }}">
                                <label for="{{ $field['name'] }}" class="{{ $labelClass }}">
                                    {{ $field['label'] }}
                                    @if($field['required'] ?? false) {!! $requiredMark !!} @endif
                                    @if($field['optional'] ?? false) {!! $optionalTag !!} @endif
                                </label>

                                @if(($field['type'] ?? 'text') === 'textarea')
                                    <textarea
                                        id="{{ $field['name'] }}"
                                        name="{{ $field['name'] }}"
                                        rows="{{ $field['rows'] ?? 2 }}"
                                        @if($field['required'] ?? false) required @endif
                                        class="{{ $inputClass }}">{{ old($field['name']) }}</textarea>
                                @else
                                    <input
                                        type="text"
                                        id="{{ $field['name'] }}"
                                        name="{{ $field['name'] }}"
                                        value="{{ old($field['name']) }}"
                                        @if($field['required'] ?? false) required @endif
                                        {!! $field['attrs'] ?? '' !!}
                                        placeholder="{{ $field['placeholder'] ?? '' }}"
                                        class="{{ $inputClass }}">
                                @endif

                                @if(!empty($field['hint']))
                                    <p class="{{ $hintClass }}">{{ $field['hint'] }}</p>
                                @endif
                                @error($field['name'])<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>
                </section>

                {{-- ===== 2. Satuan kerja ===== --}}
                <section>
                    <h2 class="{{ $sectionTitleClass }}">
                        <span class="{{ $sectionBadgeClass }}">2</span>
                        Satuan Kerja
                    </h2>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <div class="mb-3 flex flex-wrap gap-4">
                                <label class="{{ $radioLabelClass }}">
                                    <input type="radio" name="pilih_satker" value="list" x-model="satkerMode" class="{{ $radioInputClass }}">
                                    Pilih dari daftar satker
                                </label>
                                <label class="{{ $radioLabelClass }}">
                                    <input type="radio" name="pilih_satker" value="baru" x-model="satkerMode" class="{{ $radioInputClass }}">
                                    Satker belum ada, tambah manual
                                </label>
                            </div>

                            <select name="satker_id" :disabled="satkerMode === 'baru'" :class="satkerMode === 'baru' ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'"
                                    class="{{ $inputClass }}">
                                <option value="">— Pilih satker asal —</option>
                                @foreach ($satkers as $satker)
                                    <option value="{{ $satker->id }}" @selected(old('satker_id') == $satker->id)>{{ $satker->nama }}</option>
                                @endforeach
                            </select>

                            <div x-show="satkerMode === 'baru'" x-cloak x-transition class="mt-3">
                                <label for="satker_baru" class="{{ $labelClass }}">Nama Satker Baru {!! $requiredMark !!}</label>
                                <input type="text" id="satker_baru" name="satker_baru" value="{{ old('satker_baru') }}"
                                       class="{{ $inputClass }}" placeholder="mis. Dinas Kesehatan Kota Bogor">
                            </div>
                            @error('satker_id')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                            @error('satker_baru')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="unit" class="{{ $labelClass }}">Unit / Bagian {!! $optionalTag !!}</label>
                            <input type="text" id="unit" name="unit" value="{{ old('unit') }}" class="{{ $inputClass }}">
                            @error('unit')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                {{-- ===== 3. Kunjungan ===== --}}
                <section>
                    <h2 class="{{ $sectionTitleClass }}">
                        <span class="{{ $sectionBadgeClass }}">3</span>
                        Rencana Kunjungan
                    </h2>

                    {{-- 3a. Poli tujuan dulu — jam layanan tiap poli ditampilkan
                         agar pasien bisa menyesuaikan jam/tanggal kunjungan. --}}
                    <div class="mb-5">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Poli Tujuan {!! $requiredMark !!}
                            <span class="text-[11px] font-normal text-slate-400">(boleh pilih lebih dari satu)</span>
                        </label>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($polis as $poli)
                                <label class="{{ $checkboxItemClass }}">
                                    <input type="checkbox" name="poli_dituju[]" value="{{ $poli->id }}"
                                           x-model="poliDituju" @change="cekJam()"
                                           @checked(in_array($poli->id, old('poli_dituju', [])))
                                           class="{{ $checkboxInputClass }}">
                                    <span class="flex items-center justify-between gap-2">
                                        <span class="text-sm font-medium text-slate-700">{{ $poli->nama }}</span>
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold tabular-nums {{ $poli->buka24Jam() ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-sky-50 text-sky-700 ring-1 ring-sky-200' }}">{{ $poli->jamLayanan() }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('poli_dituju')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="rencana_tanggal_kunjungan" class="{{ $labelClass }}">Tanggal Kunjungan {!! $requiredMark !!}</label>
                            <input type="date" id="rencana_tanggal_kunjungan" name="rencana_tanggal_kunjungan" value="{{ old('rencana_tanggal_kunjungan') }}" required min="{{ date('Y-m-d') }}"
                                   class="{{ $inputClass }}">
                            @error('rencana_tanggal_kunjungan')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="rencana_jam_kunjungan" class="{{ $labelClass }}">Jam Kunjungan {!! $requiredMark !!}</label>
                            <input type="time" id="rencana_jam_kunjungan" name="rencana_jam_kunjungan" value="{{ old('rencana_jam_kunjungan') }}" required
                                   x-model="jam" @change="cekJam()" class="{{ $inputClass }}">
                            @error('rencana_jam_kunjungan')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- Peringatan live: jam di luar jam layanan poli yang dipilih --}}
                    <div x-show="pesanJam" x-cloak x-transition class="mt-3">
                        <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs font-medium leading-relaxed text-amber-800" x-text="pesanJam"></p>
                    </div>

                    <div class="mt-5">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Tujuan Kunjungan {!! $requiredMark !!}
                            <span class="text-[11px] font-normal text-slate-400">(boleh pilih lebih dari satu)</span>
                        </label>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($tujuanKunjungans as $tujuan)
                                <label class="{{ $checkboxItemClass }}">
                                    <input type="checkbox" name="tujuan_kunjungan[]" value="{{ $tujuan->id }}"
                                           @checked(in_array($tujuan->id, old('tujuan_kunjungan', [])))
                                           class="{{ $checkboxInputClass }}">
                                    <span class="text-sm font-medium text-slate-700">{{ $tujuan->nama }}</span>
                                </label>
                            @endforeach
                            <label class="{{ $checkboxItemClass }}">
                                <input type="checkbox" x-model="lainnya" class="{{ $checkboxInputClass }}">
                                <span class="text-sm font-medium text-slate-700">Yang lain...</span>
                            </label>
                        </div>
                        <div x-show="lainnya" x-cloak x-transition class="mt-3">
                            <label for="tujuan_lainnya" class="{{ $labelClass }}">Jelaskan tujuan lainnya</label>
                            <input type="text" id="tujuan_lainnya" name="tujuan_lainnya" x-model="lainnyaText" value="{{ old('tujuan_lainnya') }}"
                                   placeholder="mis. Fisioterapi, rontgen, konseling..." class="{{ $inputClass }}">
                        </div>
                        @error('tujuan_kunjungan')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                        @error('tujuan_lainnya')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </div>
                </section>

                {{-- Aksi --}}
                <div class="flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-gray-950"><span class="font-bold text-rose-600">*</span> Dengan mengirim formulir, Anda menyetujui data diverifikasi oleh petugas rumah sakit.</p>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-8 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                        Kirim Pendaftaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('registerForm', () => ({
            satkerMode: 'list',
            lainnya: false,
            lainnyaText: @js(old('tujuan_lainnya', '')),
            poliDituju: @js(old('poli_dituju', [])),
            jam: @js(old('rencana_jam_kunjungan', '')),
            poliJadwal: @js($poliJadwal),
            pesanJam: '',

            init() {
                if (this.lainnyaText) this.lainnya = true;
                this.cekJam();
            },

            // Cek live: jam kunjungan harus dalam jam layanan SEMUA poli terpilih.
            cekJam() {
                const jam = this.jam;
                if (!jam) { this.pesanJam = ''; return; }

                const diluar = [];
                for (const id of this.poliDituju) {
                    const p = this.poliJadwal[id];
                    if (p && p.buka && p.tutup && (jam < p.buka || jam > p.tutup)) {
                        diluar.push(`${p.nama} (${p.buka}–${p.tutup})`);
                    }
                }

                this.pesanJam = diluar.length
                    ? `Jam ${jam} berada di luar jam layanan: ${diluar.join(', ')}. Silakan sesuaikan jam atau pilihan poli.`
                    : '';
            }
        }));
    });
</script>

</body>
</html>