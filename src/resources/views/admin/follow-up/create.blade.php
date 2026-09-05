@extends('layouts.app')

@section('title', 'Tambah Follow Up')
@section('page-title', 'Tambah Follow Up')

@section('content')
<div x-data="followUpForm()" class="mx-auto max-w-6xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.follow-up.index') }}" class="rounded transition hover:text-sky-600">Follow Up</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">Tambah Follow Up</span>
        </nav>
        <h2 class="text-xl font-bold tracking-tight text-slate-900">Tambah Follow Up</h2>
        <p class="mt-0.5 text-sm text-slate-500">Pilih satker dan penerima yang belum membalas, lalu atur pesan pengingat ulang.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ===== Form ===== --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- ===== Langkah 1: Pilih Satker ===== --}}
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 p-6">
                    <h3 class="flex items-center gap-2.5 text-base font-bold text-slate-900">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-sky-600 text-xs font-bold text-white">1</span>
                        Pilih Satker
                    </h3>
                    <p class="mt-0.5 text-sm text-slate-500">Cari dan pilih satuan kerja sasaran follow up.</p>
                </div>

                <div class="p-6">
                    <div class="relative mb-3">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                        </div>
                        <input type="text" x-model="cariSatker" placeholder="Cari satker..."
                               class="block w-full rounded-xl border-0 bg-white py-2.5 pl-10 pr-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition">
                    </div>

                    <div class="max-h-52 space-y-1 overflow-y-auto rounded-xl border border-slate-100 p-2">
                        <template x-for="s in satkersTersaring" :key="s.id">
                            <button type="button" @click="pilihSatker(s.id)"
                                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left transition"
                                    :class="satkerId === s.id ? 'bg-sky-50 ring-1 ring-sky-200' : 'hover:bg-slate-50'">
                                <svg class="h-5 w-5 flex-shrink-0" :class="satkerId === s.id ? 'text-sky-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold" :class="satkerId === s.id ? 'text-sky-700' : 'text-slate-700'" x-text="s.nama"></span>
                                    <span class="block truncate font-mono text-xs text-slate-400" x-text="(s.kode || '—') + ' · ' + jumlahPnppSatker(s.id) + ' PNPP'"></span>
                                </span>
                                <svg x-show="satkerId === s.id" x-cloak class="h-5 w-5 flex-shrink-0 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </button>
                        </template>
                        <p x-show="satkersTersaring.length === 0" x-cloak class="px-3 py-6 text-center text-sm text-slate-400">Satker tidak ditemukan.</p>
                    </div>
                </div>
            </div>

            {{-- ===== Langkah 2: Pilih Penerima ===== --}}
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 p-6">
                    <h3 class="flex items-center gap-2.5 text-base font-bold text-slate-900">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-sky-600 text-xs font-bold text-white">2</span>
                        Pilih Penerima
                    </h3>
                    <p class="mt-0.5 text-sm text-slate-500">Sasaran follow up: PNPP yang belum membalas pesan WhatsApp sebelumnya.</p>
                </div>

                <div class="space-y-5 p-6">
                    {{-- Mode --}}
                    <div class="grid grid-cols-2 gap-2 rounded-xl bg-slate-100 p-1">
                        <button type="button" @click="mode = 'pnpp'"
                                class="rounded-lg py-2 text-sm font-semibold transition"
                                :class="mode === 'pnpp' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                            Database PNPP
                        </button>
                        <button type="button" @click="mode = 'grouping'; applyGrouping()"
                                class="rounded-lg py-2 text-sm font-semibold transition"
                                :class="mode === 'grouping' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                            Select Grouping
                        </button>
                    </div>

                    {{-- Belum pilih satker --}}
                    <div x-show="satkerId === null" x-cloak class="flex items-center gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50/60 p-4">
                        <svg class="h-5 w-5 flex-shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.952l-.707.707a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m9.86 2.714.041-.02a.75.75 0 0 1 1.063.952l-.707.707a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757" /></svg>
                        <p class="text-sm text-slate-500">Pilih <strong class="text-slate-700">satker</strong> terlebih dahulu untuk melihat database PNPP.</p>
                    </div>

                    {{-- Mode Database PNPP --}}
                    <div x-show="mode === 'pnpp' && satkerId !== null" x-cloak>
                        <div class="mb-3 flex items-center gap-2">
                            <div class="relative flex-1">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                                </div>
                                <input type="text" x-model="cariP" placeholder="Cari nama / NIP PNPP..."
                                       class="block w-full rounded-xl border-0 bg-white py-2.5 pl-10 pr-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition">
                            </div>
                            <button type="button" @click="pilihSemua()" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-xs font-semibold text-slate-600 shadow-xs transition hover:bg-slate-50">Pilih Semua</button>
                            <button type="button" @click="hapusPilihan()" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-xs font-semibold text-slate-600 shadow-xs transition hover:bg-slate-50">Hapus</button>
                        </div>

                        <div class="max-h-56 space-y-1 overflow-y-auto rounded-xl border border-slate-100 p-2">
                            <template x-for="p in pnppsTersaring" :key="p.id">
                                <label class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 transition hover:bg-sky-50">
                                    <input type="checkbox" :checked="terpilih(p.id)" @change="togglePnpp(p)" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold uppercase text-emerald-700" x-text="inisial(p.nama)"></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-slate-700" x-text="p.nama"></span>
                                        <span class="block truncate font-mono text-xs text-slate-400" x-text="(p.nip || '—') + (p.noHp ? ' · ' + p.noHp : '')"></span>
                                    </span>
                                </label>
                            </template>
                            <p x-show="pnppsTersaring.length === 0" x-cloak class="px-3 py-6 text-center text-sm text-slate-400">PNPP tidak ditemukan.</p>
                        </div>
                        <p class="mt-2 text-xs text-slate-400"><span x-text="pilih.length"></span> penerima dipilih — hanya penerima yang belum membalas yang perlu di-follow up.</p>
                    </div>

                    {{-- Mode Grouping --}}
                    <div x-show="mode === 'grouping' && satkerId !== null" x-cloak class="space-y-4">
                        <button type="button" @click="applyGrouping()"
                                class="w-full rounded-xl p-4 text-left ring-1 ring-inset transition bg-sky-50 ring-sky-300 hover:bg-sky-50/70">
                            <p class="text-sm font-bold text-sky-700">Semua PNPP Satker</p>
                            <p class="mt-0.5 text-xs text-slate-400" x-text="'Pilih semua ' + jumlahPnppSatker(satkerId) + ' PNPP di ' + namaSatker"></p>
                        </button>

                        <div class="flex items-center gap-2 rounded-xl bg-emerald-50/70 p-3 ring-1 ring-emerald-100">
                            <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a2.966 2.966 0 0 0-.942-3.196" /></svg>
                            <p class="text-xs font-semibold text-emerald-700"><span x-text="pilih.length"></span> PNPP terpilih — daftar bisa disesuaikan manual di mode Database PNPP.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== Pesan & Jadwal Kirim ===== --}}
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 p-6">
                    <h3 class="text-base font-bold text-slate-900">Pesan & Jadwal Kirim</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Atur isi pengingat ulang WhatsApp dan waktu pengirimannya.</p>
                </div>

                <div class="space-y-5 p-6">
                    <div class="flex items-center gap-2 rounded-xl bg-emerald-50/70 p-3 ring-1 ring-emerald-100">
                        <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" /></svg>
                        <span class="text-xs font-semibold text-emerald-700">Dikirim melalui <strong>WhatsApp</strong> (pengingat ulang).</span>
                    </div>

                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label class="block text-xs font-bold uppercase tracking-wide text-slate-500">Gunakan Template</label>
                            <a href="{{ route('admin.setting.index') }}" target="_blank" class="text-xs font-semibold text-sky-600 hover:underline">Kelola Template &rarr;</a>
                        </div>
                        <select x-model="templateId" @change="applyTemplate()"
                                class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition cursor-pointer">
                            <option value="">— Pilih template —</option>
                            @foreach ($templates as $t)
                                <option value="{{ $t->id }}">{{ $t->judul }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Isi Pesan <span class="text-rose-500">*</span></label>
                        <textarea rows="3" x-model="pesan"
                                  class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition"></textarea>
                        <p class="mt-1.5 text-xs text-slate-400">Variabel tersedia: <code class="rounded bg-slate-100 px-1 py-0.5 font-mono">{nama}</code></p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Jadwal Kirim</label>
                            <input type="datetime-local" x-model="jadwalKirim"
                                   class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Mode Kirim</label>
                            <select x-model="modeKirim"
                                    class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition cursor-pointer">
                                <option value="terjadwal">Terjadwal</option>
                                <option value="sekarang">Kirim Sekarang</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action bar --}}
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.follow-up.index') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Batal</a>
                <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    Simpan Follow Up
                </button>
            </div>
        </div>

        {{-- ===== Kolom kanan ===== --}}
        <div class="space-y-6 lg:col-span-1">
            {{-- Ringkasan --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-bold text-slate-900">Ringkasan</h3>
                </div>
                <ul class="divide-y divide-slate-50">
                    <li class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-sm font-medium text-slate-500">Satker</span>
                        <span class="max-w-[55%] truncate text-sm font-bold text-slate-800" x-text="namaSatker || '—'"></span>
                    </li>
                    <li class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-sm font-medium text-slate-500">Penerima dipilih</span>
                        <span class="text-sm font-bold tabular-nums text-slate-800" x-text="pilih.length"></span>
                    </li>
                    <li class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-sm font-medium text-slate-500">Jadwal kirim</span>
                        <span class="text-sm font-bold text-slate-800" x-text="jadwalKirim ? formatWaktu(jadwalKirim) : '—'"></span>
                    </li>
                    <li class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-sm font-medium text-slate-500">Mode kirim</span>
                        <span class="text-sm font-bold capitalize text-slate-800" x-text="modeKirim === 'sekarang' ? 'Sekarang' : 'Terjadwal'"></span>
                    </li>
                </ul>
            </div>

            {{-- Pratinjau WhatsApp --}}
            <div class="sticky top-20 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 bg-emerald-600 px-5 py-4">
                    <p class="text-sm font-bold text-white">Pratinjau WhatsApp</p>
                    <span x-show="pilih.length > 1" x-cloak class="rounded-full bg-white/20 px-2 py-0.5 text-[11px] font-bold text-white" x-text="'+' + (pilih.length - 1) + ' lain'"></span>
                </div>
                <div class="space-y-3 bg-[#e5ddd5] p-5">
                    <div class="ml-auto max-w-[85%] rounded-xl rounded-tr-sm bg-[#dcf8c6] p-3 shadow-sm">
                        <p class="whitespace-pre-line text-sm text-slate-800" x-text="previewPesan"></p>
                        <p class="mt-1 text-right text-[10px] text-slate-400" x-text="previewWaktu"></p>
                    </div>
                    <p x-show="pilih.length === 0" x-cloak class="py-4 text-center text-xs text-slate-500">Pilih penerima untuk melihat pratinjau pesan.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function followUpForm() {
        return {
            satkers: @json($satkers),
            pnpps: @json($pnpps),
            templates: @json($templates),

            satkerId: null,
            cariSatker: '',
            mode: 'pnpp',
            cariP: '',
            pilih: [],
            templateId: null,
            pesan: 'Halo {nama}, kami belum menerima balasan Anda atas pesan sebelumnya. Mohon konfirmasi kunjungan Anda dengan membalas pesan ini.',
            jadwalKirim: '',
            modeKirim: 'terjadwal',

            get satkersTersaring() {
                const q = this.cariSatker.toLowerCase();
                return this.satkers.filter(s => !q || s.nama.toLowerCase().includes(q) || (s.kode || '').toLowerCase().includes(q));
            },
            get namaSatker() {
                return this.satkers.find(s => s.id === this.satkerId)?.nama || null;
            },
            get pnppsTersaring() {
                const q = this.cariP.toLowerCase();
                return this.pnpps.filter(p =>
                    p.satkerId === this.satkerId &&
                    (!q || p.nama.toLowerCase().includes(q) || (p.nip || '').toLowerCase().includes(q))
                );
            },
            get previewPesan() {
                const p = this.pilih[0];
                if (!p) return this.pesan;
                return this.pesan.replaceAll('{nama}', p.nama.split(' ')[0]);
            },
            get previewWaktu() {
                if (!this.jadwalKirim) return '';
                return this.formatWaktu(this.jadwalKirim);
            },

            jumlahPnppSatker(satkerId) {
                return this.pnpps.filter(p => p.satkerId === satkerId).length;
            },
            inisial(nama) {
                return (nama || '?').charAt(0);
            },
            terpilih(id) {
                return this.pilih.some(p => p.id === id);
            },
            pilihSatker(id) {
                this.satkerId = id;
                this.pilih = [];
                if (this.mode === 'grouping') this.applyGrouping();
            },
            togglePnpp(p) {
                const i = this.pilih.findIndex(x => x.id === p.id);
                if (i >= 0) {
                    this.pilih.splice(i, 1);
                } else {
                    this.pilih.push({ id: p.id, nama: p.nama, nip: p.nip, noHp: p.noHp });
                }
            },
            pilihSemua() {
                this.pnppsTersaring.forEach(p => {
                    if (!this.terpilih(p.id)) this.togglePnpp(p);
                });
            },
            hapusPilihan() {
                this.pilih = [];
            },
            applyGrouping() {
                if (this.satkerId === null) return;
                this.pilih = this.pnpps
                    .filter(p => p.satkerId === this.satkerId)
                    .map(p => ({ id: p.id, nama: p.nama, nip: p.nip, noHp: p.noHp }));
            },
            applyTemplate() {
                const t = this.templates.find(x => x.id === Number(this.templateId));
                if (t) this.pesan = t.konten;
            },
            formatWaktu(nilai) {
                const d = new Date(nilai);
                return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' +
                       d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            },
        };
    }
</script>
@endsection
