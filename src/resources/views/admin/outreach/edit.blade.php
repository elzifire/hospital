@extends('layouts.app')

@section('title', 'Edit Pesan Outreach')
@section('page-title', 'Edit Pesan Outreach')

@section('content')
<div x-data="outreachEditForm()" class="mx-auto max-w-6xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.outreach.index') }}" class="rounded transition hover:text-sky-600">Outreach</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">Edit Pesan</span>
        </nav>
        <h2 class="text-xl font-bold tracking-tight text-slate-900">Edit Pesan Outreach</h2>
        <p class="mt-0.5 text-sm text-slate-500">Perbarui isi pesan, jadwal kirim, dan status — penerima mengikuti data pesan ini.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ===== Form ===== --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- Penerima (data pesan — tidak diubah di sini) --}}
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 p-6">
                    <h3 class="text-base font-bold text-slate-900">Penerima</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Penerima tetap mengikuti data pesan ini — buat pesan baru untuk penerima lain.</p>
                </div>

                <div class="p-6">
                    <ul class="divide-y divide-slate-50 rounded-xl border border-slate-100">
                        <template x-for="p in penerima" :key="p.id">
                            <li class="flex items-center gap-3 px-4 py-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold uppercase text-emerald-700" x-text="inisial(p.nama)"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-700" x-text="p.nama"></p>
                                    <p class="truncate font-mono text-xs text-slate-400" x-text="(p.nip || '—') + (p.noHp ? ' · ' + p.noHp : '')"></p>
                                </div>
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200/70">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    Terpilih
                                </span>
                            </li>
                        </template>
                    </ul>
                    <p class="mt-2 text-xs text-slate-400"><span x-text="penerima.length"></span> penerima pada pesan ini.</p>
                </div>
            </div>

            {{-- ===== Pesan & Jadwal Kirim ===== --}}
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 p-6">
                    <h3 class="text-base font-bold text-slate-900">Pesan & Jadwal Kirim</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Susun pesan dari template atau tulis manual, lalu atur waktu pengiriman.</p>
                </div>

                <div class="space-y-5 p-6">
                    <div class="flex items-center gap-2 rounded-xl bg-emerald-50/70 p-3 ring-1 ring-emerald-100">
                        <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" /></svg>
                        <span class="text-xs font-semibold text-emerald-700">Dikirim melalui <strong>WhatsApp</strong> — satu-satunya channel broadcast.</span>
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

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
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
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Status</label>
                            <select x-model="status"
                                    class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition cursor-pointer">
                                <option value="Menunggu Dikirim">Menunggu Dikirim</option>
                                <option value="Terkirim">Terkirim</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action bar --}}
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.outreach.index') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Batal</a>
                <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    Simpan Perubahan
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
                        <span class="text-sm font-medium text-slate-500">Penerima</span>
                        <span class="text-sm font-bold tabular-nums text-slate-800" x-text="penerima.length"></span>
                    </li>
                    <li class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-sm font-medium text-slate-500">Channel</span>
                        <span class="text-sm font-bold text-emerald-600">WhatsApp</span>
                    </li>
                    <li class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-sm font-medium text-slate-500">Jadwal kirim</span>
                        <span class="text-sm font-bold text-slate-800" x-text="jadwalKirim ? formatWaktu(jadwalKirim) : '—'"></span>
                    </li>
                    <li class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-sm font-medium text-slate-500">Status</span>
                        <span class="text-sm font-bold text-slate-800" x-text="status"></span>
                    </li>
                </ul>
            </div>

            {{-- Pratinjau WhatsApp --}}
            <div class="sticky top-20 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 bg-emerald-600 px-5 py-4">
                    <p class="text-sm font-bold text-white">Pratinjau WhatsApp</p>
                    <span x-show="penerima.length > 1" x-cloak class="rounded-full bg-white/20 px-2 py-0.5 text-[11px] font-bold text-white" x-text="'+' + (penerima.length - 1) + ' lain'"></span>
                </div>
                <div class="space-y-3 bg-[#e5ddd5] p-5">
                    <div class="ml-auto max-w-[85%] rounded-xl rounded-tr-sm bg-[#dcf8c6] p-3 shadow-sm">
                        <p class="whitespace-pre-line text-sm text-slate-800" x-text="previewPesan"></p>
                        <p class="mt-1 text-right text-[10px] text-slate-400" x-text="previewWaktu"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function outreachEditForm() {
        return {
            templates: @json($templates),

            // Data penerima pesan ini (read-only — disesuaikan saat logic tersedia).
            penerima: @json($pnpps->take(2)->values()),

            templateId: null,
            pesan: 'Halo {nama}, kami dari RS Bhayangkara Bogor ingin menyampaikan informasi terkait layanan kesehatan Anda.',
            jadwalKirim: '',
            modeKirim: 'terjadwal',
            status: 'Menunggu Dikirim',

            init() {
                // Prefill demo: template pertama (disesuaikan saat logic tersedia).
                this.templateId = this.templates[0]?.id ?? null;
                if (this.templates[0]) this.pesan = this.templates[0].konten;
                this.jadwalKirim = new Date().toISOString().slice(0, 10) + 'T07:30';
            },

            get previewPesan() {
                const p = this.penerima[0];
                if (!p) return this.pesan;
                return this.pesan.replaceAll('{nama}', p.nama.split(' ')[0]);
            },
            get previewWaktu() {
                if (!this.jadwalKirim) return '';
                return this.formatWaktu(this.jadwalKirim);
            },

            inisial(nama) {
                return (nama || '?').charAt(0);
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
