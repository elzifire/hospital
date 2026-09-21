@extends('layouts.app')

@section('title', 'Percakapan')
@section('page-title', 'Percakapan')

@php
    $replisKonteks = collect($replis)->where('kontekstual', true)->values()->all();
    $replisCepat = collect($replis)->where('kontekstual', false)->values()->all();
@endphp

@section('content')
<div class="space-y-5"
     x-data="percakapan({
         endpoint: @js(route('admin.respon.timeline', $noHp)),
         balas: @js(route('admin.respon.balas', $noHp)),
     })"
     x-init="init()">

    {{-- ===== Header percakapan ===== --}}
    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.respon.index') }}"
               class="rounded-lg bg-slate-100 p-2.5 text-slate-500 transition hover:bg-slate-200 hover:text-slate-700" title="Kembali">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full {{ $pnpp ? 'bg-sky-100 text-sky-700 font-bold' : 'bg-slate-200 text-slate-500 font-semibold' }}">
                <span class="text-sm">{{ strtoupper(mb_substr($pnpp?->nama ?? $noHp, 0, 1, 'UTF-8')) }}</span>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-base font-bold tracking-tight text-slate-900">{{ $pnpp?->nama ?? 'Nomor Tak Dikenal' }}</h1>
                    @if ($pnpp)
                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-600 ring-1 ring-emerald-200">Terdaftar</span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-rose-50 px-1.5 py-0.5 text-[10px] font-semibold text-rose-500">tak terdaftar</span>
                    @endif
                </div>
                <p class="font-mono text-xs text-slate-400">{{ $noHp }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2 text-xs text-slate-400">
            <span class="relative flex h-2 w-2" x-show="aktif">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
            </span>
            <span x-show="aktif" x-cloak>Memantau pesan baru…</span>
        </div>
    </div>

    {{-- ===== Balasan kontekstual & cepat ===== --}}
    @if (count($replisKonteks) > 0 || count($replisCepat) > 0)
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            @if (count($replisKonteks) > 0)
                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-sky-500">Balasan kontekstual</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($replisKonteks as $r)
                        <button type="button" @click="isiRepliTeks($event.currentTarget.dataset.teks)" data-teks="{{ $r['teks'] }}"
                                class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-3.5 py-1.5 text-xs font-semibold text-sky-700 ring-1 ring-sky-200 transition hover:bg-sky-100">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                            {{ $r['label'] }}
                        </button>
                    @endforeach
                </div>
            @endif
            @if (count($replisCepat) > 0)
                <p class="mb-2 mt-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Balasan cepat</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($replisCepat as $r)
                        <button type="button" @click="isiRepliTeks($event.currentTarget.dataset.teks)" data-teks="{{ $r['teks'] }}"
                                class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3.5 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-200">
                            {{ $r['label'] }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- ===== Garis waktu percakapan ===== --}}
    <div class="rounded-2xl border border-slate-200 bg-[#efeae2] p-3 shadow-sm sm:p-4">
        <div class="h-[26rem] overflow-y-auto px-1 sm:px-2" x-ref="papan">
            <div x-ref="timeline">
                @include('admin.respon._timeline', ['timeline' => $timeline])
            </div>
        </div>
    </div>

    {{-- ===== Form balas (chat composer) ===== --}}
    <form @submit.prevent="kirim($event)"
          class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">

        {{-- Pratinjau media terpilih --}}
        <div x-show="berkas" x-cloak x-transition
             class="mb-3 flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/>
                </svg>
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-slate-700" x-text="berkas?.name ?? ''"></p>
                <p class="text-[11px] text-slate-400" x-text="(berkas ? Math.ceil(berkas.size / 1024) : 0) + ' KB · ' + labelTipe()"></p>
            </div>
            <button type="button" @click="hapusBerkas()"
                    class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-200 hover:text-rose-500" title="Buang berkas">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Panel CTA URL --}}
        <div x-show="panelCta" x-cloak x-transition
             class="mb-3 rounded-xl border border-sky-200 bg-sky-50/60 px-4 py-3">
            <div class="mb-2 flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wide text-sky-600">Tombol CTA URL</p>
                <button type="button" @click="panelCta = false"
                        class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-200 hover:text-slate-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="grid gap-2.5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-[11px] font-semibold text-slate-500">URL tujuan <span class="text-rose-500">*</span></label>
                    <input type="url" x-model="cta.url" placeholder="https://…" required
                           class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-semibold text-slate-500">Teks tombol <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="cta.label" maxlength="40" placeholder="Buka" required
                           class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-semibold text-slate-500">Judul (opsional, maks. 60)</label>
                    <input type="text" x-model="cta.header" maxlength="60"
                           class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                </div>
            </div>
            <p class="mt-2 text-[11px] text-slate-400">Teks pesan diketik di kolom di bawah. URL harus terdaftar terlebih dahulu di WhatsApp Business.</p>
        </div>

        <div class="flex items-end gap-2.5">
            {{-- Lampirkan media --}}
            <input type="file" x-ref="fileInput" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt" hidden
                   @change="pilihBerkas($event)">
            <button type="button" @click="$refs.fileInput.click()" :class="berkas && 'bg-emerald-50 text-emerald-600 ring-1 ring-emerald-200'"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" title="Lampirkan media">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
            </button>

            {{-- Tombol CTA URL --}}
            <button type="button" @click="panelCta = !panelCta"
                    :class="panelCta && 'bg-sky-50 text-sky-600 ring-1 ring-sky-200'"
                    class="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-xl px-3 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" title="Kirim tombol CTA URL">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                CTA
            </button>

            <div class="min-w-0 flex-1">
                <textarea x-ref="isi" name="isi" rows="1"
                          x-bind:placeholder="berkas ? 'Tambahkan keterangan (opsional)…' : (panelCta ? 'Tulis teks pesan…' : 'Tulis balasan…')"
                          class="w-full resize-none rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                          @keydown.enter.prevent="$event.shiftKey || ($event.metaKey || $event.ctrlKey) || kirim($event)"
                          style="min-height: 40px; max-height: 120px;"
                          @input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 120) + 'px'"></textarea>
                <p class="mt-1.5 text-[11px] text-slate-400">
                    Enter untuk kirim · Shift+Enter untuk baris baru ·
                    <span class="font-medium text-slate-500" x-text="berkas ? 'Media' : (panelCta ? 'Tombol CTA URL' : 'Teks bebas')"></span>
                    (berlaku dalam 24 jam sesi WA).
                </p>
            </div>
            <button type="submit" :disabled="sending"
                    class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-50">
                <svg x-show="sending" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                <svg x-show="!sending" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.126a59.768 59.768 0 0 1 21.323 5.485 57.746 57.746 0 0 1-3.045 5.39M6 12l5.34 2.398M6 12h.008M6 12l3.34 8.25 2.64-4.108M13.5 10.5 21 6.75 20.25 17.25 13.5 10.5Z"/></svg>
                <span x-text="sending ? 'Mengirim…' : 'Kirim'"></span>
            </button>
        </div>

        <p x-cloak :class="resultOk === true ? 'mt-2 text-xs font-semibold text-emerald-600' : (resultOk === false ? 'mt-2 text-xs font-semibold text-rose-600' : 'hidden')"
           x-text="statusMsg"></p>
    </form>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('percakapan', (config) => ({
            endpoint: config.endpoint,
            endpointBalas: config.balas,
            html: '',
            signature: '',
            aktif: false,
            sending: false,
            statusMsg: '',
            resultOk: null,
            berkas: null,
            panelCta: false,
            cta: { url: '', label: '', header: '' },

            init() {
                this.$nextTick(() => {
                    this.scrollKeBawah();
                    this.sync();
                });
                setInterval(() => this.sync(), 5000);
            },

            async sync() {
                try {
                    const resp = await fetch(this.endpoint, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    });
                    if (!resp.ok) return;

                    const data = await resp.json();
                    this.aktif = true;

                    if (data.signature !== this.signature) {
                        const lama = this.signature;
                        this.signature = data.signature;
                        this.html = data.html;
                        this.$nextTick(() => {
                            this.scrollKeBawah();
                            window.dispatchEvent(new CustomEvent('respon:baru', { detail: data }));
                        });
                        if (lama !== '') {
                            this.statusMsg = 'Ada pesan baru.';
                            this.resultOk = true;
                        }
                    }
                } catch (e) {
                    // Abaikan — polling diulang.
                }
            },

            scrollKeBawah() {
                const papan = this.$refs.papan;
                if (papan) {
                    papan.scrollTop = papan.scrollHeight;
                }
            },

            isiRepliTeks(teks) {
                if (!teks) return;
                const input = this.$refs.isi;
                if (input) {
                    this.berkas = null;
                    this.panelCta = false;
                    input.value = teks;
                    this.$nextTick(() => {
                        input.style.height = 'auto';
                        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
                        input.focus();
                    });
                }
            },

            pilihBerkas(event) {
                const file = event.target.files && event.target.files[0];
                this.berkas = file || null;
                if (file) this.panelCta = false;
            },

            hapusBerkas() {
                this.berkas = null;
                if (this.$refs.fileInput) this.$refs.fileInput.value = '';
            },

            labelTipe() {
                if (!this.berkas) return '';
                if (this.berkas.type.startsWith('image/')) return 'Gambar';
                if (this.berkas.type.startsWith('audio/')) return 'Audio';
                if (this.berkas.type.startsWith('video/')) return 'Video';
                return 'Dokumen';
            },

            tipeMedia() {
                if (!this.berkas) return 'text';
                if (this.berkas.type.startsWith('image/')) return 'image';
                if (this.berkas.type.startsWith('audio/')) return 'audio';
                if (this.berkas.type.startsWith('video/')) return 'video';
                return 'document';
            },

            async kirim(event) {
                const isi = this.$refs.isi?.value ?? '';

                const form = new FormData();
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
                form.append('_token', token);

                if (this.berkas) {
                    form.append('tipe', this.tipeMedia());
                    form.append('media', this.berkas);
                    form.append('caption', this.berkas.type.startsWith('image/') ? '' : isi);
                } else if (this.panelCta) {
                    if (!this.cta.url || !this.cta.label || isi.trim() === '') {
                        this.resultOk = false;
                        this.statusMsg = 'Lengkapi URL, teks tombol, dan teks pesan untuk CTA URL.';
                        return;
                    }
                    form.append('tipe', 'interactive');
                    form.append('isi', isi);
                    form.append('cta_url', this.cta.url);
                    form.append('cta_label', this.cta.label);
                    form.append('cta_header', this.cta.header);
                } else {
                    if (isi.trim() === '') return;
                    form.append('tipe', 'text');
                    form.append('isi', isi);
                }

                this.sending = true;
                this.statusMsg = '';
                this.resultOk = null;

                try {
                    const resp = await fetch(this.endpointBalas, {
                        method: 'POST',
                        body: form,
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await resp.json();

                    if (responsOk(resp, data)) {
                        this.$refs.isi.value = '';
                        this.$refs.isi.style.height = 'auto';
                        this.hapusBerkas();
                        this.panelCta = false;
                        this.cta.url = '';
                        this.cta.label = '';
                        this.cta.header = '';
                        this.statusMsg = data.message;
                        this.resultOk = true;
                        window.dispatchEvent(new CustomEvent('respon:terkirim', { detail: data }));
                        await this.sync();
                    } else {
                        this.resultOk = false;
                        this.statusMsg = data.message ?? pesanError(data);
                    }
                } catch (e) {
                    this.resultOk = false;
                    this.statusMsg = 'Gagal mengirim balasan.';
                } finally {
                    this.sending = false;
                }
            },
        }));

        function responsOk(response, data) {
            return response.ok && data && data.ok === true;
        }

        function pesanError(data) {
            if (data && data.errors) return Object.values(data.errors).flat().join(' · ');
            return data && data.message ? data.message : 'Terjadi kesalahan.';
        }
    });
</script>
@endsection