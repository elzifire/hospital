@extends('layouts.app')

@section('title', 'Percakapan')
@section('page-title', 'Percakapan')

@section('content')
<div class="space-y-5"
     x-data="percakapan({
         endpoint: @js(route('admin.respon.timeline', $noHp)),
         balas: @js(route('admin.respon.balas', $noHp)),
     })"
     x-init="init()">

    {{-- ===== Header percakapan ===== --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
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
          class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm ring-1 ring-slate-100">
        <div class="flex items-end gap-3">
            <div class="min-w-0 flex-1">
                <textarea x-ref="isi" name="isi" rows="1" required
                          placeholder="Tulis balasan…"
                          class="w-full resize-none rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                          @keydown.enter.prevent="$event.shiftKey || ($event.metaKey || $event.ctrlKey) || kirim($event)"
                          style="min-height: 40px; max-height: 120px;"
                          @input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 120) + 'px'"></textarea>
                <p class="mt-1.5 text-[11px] text-slate-400">
                    Enter untuk kirim · Shift+Enter untuk baris baru ·
                    <span class="font-medium text-slate-500">Teks bebas</span> (berlaku dalam 24 jam sesi WA).
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

            async kirim(event) {
                const isi = this.$refs.isi?.value ?? '';
                if (isi.trim() === '') return;

                const form = new FormData();
                form.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '');
                form.append('isi', isi);

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