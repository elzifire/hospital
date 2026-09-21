@extends('layouts.app')

@section('title', 'Balasan WhatsApp')
@section('page-title', 'Respon Pasien')

@section('content')
@php
    $statCards = [
        ['id' => 'stat-total',    'label' => 'Total Balasan',    'value' => number_format($total),       'raw' => $total,       'tone' => 'sky',     'icon' => 'M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-2.029 2.115 2.115 0 0 0-1.661-.586 48.744 48.744 0 0 0-8.983 0 2.115 2.115 0 0 0-1.661.586 2.126 2.126 0 0 0-.476 2.029c.172.714.308 1.44.41 2.174m3.923-2.174a41.03 41.03 0 0 0-.41 2.174c-.058.35-.088.706-.088 1.066v4.286c0 .36.03.716.088 1.066'],
        ['id' => 'stat-belum',    'label' => 'Belum Dibaca',     'value' => number_format($belumDibaca), 'raw' => $belumDibaca, 'tone' => 'rose',    'icon' => 'M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'],
        ['id' => 'stat-hari',     'label' => 'Balasan Hari Ini', 'value' => number_format($hariIni),     'raw' => $hariIni,     'tone' => 'emerald', 'icon' => 'M4.5 12.75l6 6 9-13.5'],
        ['id' => 'stat-pasien',   'label' => 'Pasien Terdaftar', 'value' => number_format($pasienUnik),  'raw' => $pasienUnik,  'tone' => 'violet',  'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
        ['id' => 'stat-tak',      'label' => 'Nomor Tak Dikenal', 'value' => number_format($takTerdaftar), 'raw' => $takTerdaftar, 'tone' => 'amber',  'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
    ];

    $toneColor = [
        'sky'     => ['bg' => 'bg-sky-50',     'text' => 'text-sky-600'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
        'violet'  => ['bg' => 'bg-violet-50',  'text' => 'text-violet-600'],
        'rose'    => ['bg' => 'bg-rose-50',    'text' => 'text-rose-600'],
        'amber'   => ['bg' => 'bg-amber-50',   'text' => 'text-amber-600'],
    ];
@endphp

<div class="space-y-6">
    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Balasan WhatsApp</h2>
            <p class="mt-0.5 text-sm text-slate-500">Pilih nomor / target penerima dulu, lalu lihat & balas percakapannya.</p>
        </div>
        <a href="{{ route('admin.respon.manual') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Catat Manual
        </a>
    </div>

    @include('admin.respon._flash')

    @include('admin.respon._subnav', ['active' => 'balasan'])

    {{-- ===== Live: statistik + chat list (polling via JS, tanpa websocket) ===== --}}
    <div class="space-y-6"
         x-data="balasanLive({
             endpoint: @js(route('admin.respon.poll')),
             endpointKonten: @js(route('admin.respon.konten')),
             signature: @js($signature ?? ''),
             query: @js($queryString ?? ''),
             hasMore: @js($hasMore ?? false),
         })"
         x-init="init()">

        {{-- Kartu Statistik --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
            @foreach ($statCards as $s)
                @php($c = $toneColor[$s['tone']])
                <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-xs ring-1 ring-slate-200">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg {{ $c['bg'] }} {{ $c['text'] }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $s['icon'] }}" /></svg>
                    </div>
                    <div class="min-w-0">
                        <p id="{{ $s['id'] }}" data-nilai="{{ $s['raw'] }}" class="text-lg font-extrabold leading-tight tabular-nums text-slate-900">{{ $s['value'] }}</p>
                        <p class="truncate text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ $s['label'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ===== Chat list ala WhatsApp ===== --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-[#008069] px-5 py-4">
                <div class="flex items-center gap-2.5">
                    <svg class="h-5 w-5 text-white/90" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347Z"/><path d="M12 1.93a10.07 10.07 0 0 1 8.57 4.93 10 10 0 0 1-2.43 13.13l2.34 1.9a.5.5 0 0 1-.31.9H12A10.07 10.07 0 1 1 12 1.93Zm0 1.9A8.17 8.17 0 1 0 12 20.13h7.68l-1.67-1.35-.6-.49.4-.67A8.17 8.17 0 0 0 12 3.83Z"/></svg>
                    <h3 class="text-sm font-bold text-white">Daftar Percakapan</h3>
                </div>
                <div class="flex items-center gap-2.5">
                    <span x-show="aktif" x-cloak x-transition
                          class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-300 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                        </span>
                        Live
                    </span>
                    <span id="chat-total" class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white">{{ number_format($total) }} pesan</span>
                </div>
            </div>

            {{-- Pencarian & filter --}}
            <form method="GET" action="{{ route('admin.respon.index') }}"
                  class="flex flex-wrap items-end gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4">
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Cari kontak / pesan</label>
                    <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Nama / nomor / isi pesan…"
                           class="h-10 w-56 rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Status baca</label>
                    <select name="status_baca" class="h-10 rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Semua</option>
                        <option value="belum" {{ $filters['status_baca'] === 'belum' ? 'selected' : '' }}>Belum dibaca</option>
                        <option value="dibaca" {{ $filters['status_baca'] === 'dibaca' ? 'selected' : '' }}>Sudah dibaca</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Kontak</label>
                    <select name="asal" class="h-10 rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Semua</option>
                        <option value="terdaftar" {{ $filters['asal'] === 'terdaftar' ? 'selected' : '' }}>Terdaftar</option>
                        <option value="tak_terdaftar" {{ $filters['asal'] === 'tak_terdaftar' ? 'selected' : '' }}>Tak terdaftar</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Dari tanggal</label>
                    <input type="date" name="dari" value="{{ $filters['dari'] }}"
                           class="h-10 rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Sampai tanggal</label>
                    <input type="date" name="sampai" value="{{ $filters['sampai'] }}"
                           class="h-10 rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Template terkirim</label>
                    <select name="template" class="h-10 rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Semua</option>
                        @foreach ($templates as $tpl)
                            <option value="{{ $tpl->id }}" {{ $filters['template'] === (string) $tpl->id ? 'selected' : '' }}>{{ $tpl->judul }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="h-10 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">Terapkan</button>
                    @if (collect($filters)->filter(fn ($v) => $v !== '')->isNotEmpty())
                        <a href="{{ route('admin.respon.index') }}" class="h-10 rounded-lg bg-slate-200 px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-300">Reset</a>
                    @endif
                </div>
            </form>

            {{-- Banner pesan baru --}}
            <div x-show="notif" x-cloak x-transition
                 class="flex items-center justify-between gap-3 border-b border-emerald-100 bg-emerald-50 px-5 py-3">
                <div class="flex items-center gap-2.5 text-sm font-semibold text-emerald-700">
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    <span x-text="'Ada ' + notif + ' pesan baru masuk — daftar diperbarui otomatis.'"></span>
                </div>
                <button type="button" @click="notif = null"
                        class="text-xs font-semibold text-emerald-600 transition hover:text-emerald-800">Tutup</button>
            </div>

            {{-- Daftar percakapan (di-swap oleh JS saat ada perubahan) --}}
            @include('admin.respon._chatlist', ['konversasi' => $konversasi])
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('balasanLive', (config) => ({
            endpoint: config.endpoint,
            endpointKonten: config.endpointKonten,
            signature: config.signature,
            query: config.query,
            aktif: false,
            notif: 0,
            timer: null,
            notifTimer: null,
            observer: null,
            halaman: 1,
            hasMore: Boolean(config.hasMore),
            sibuk: false,
            prasimpan: {},

            init() {
                this.$nextTick(() => {
                    this.cek();
                    this.pasangObserver();
                });
                this.mulai();
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        this.henti();
                    } else {
                        this.mulai();
                        this.cek();
                    }
                });
            },

            mulai() {
                if (this.timer === null) {
                    this.timer = setInterval(() => this.cek(), 8000);
                }
            },

            henti() {
                if (this.timer !== null) {
                    clearInterval(this.timer);
                    this.timer = null;
                }
            },

            async cek() {
                try {
                    const url = this.endpoint + (this.query ? '?' + this.query : '');
                    const resp = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    });
                    if (!resp.ok) return;

                    const data = await resp.json();
                    this.aktif = true;

                    if (data.signature === this.signature) return;
                    this.signature = data.signature;

                    const sebelum = Number(document.getElementById('stat-belum')?.dataset.nilai ?? 0) || 0;
                    this.updateStats(data.stats);

                    const panel = document.getElementById('chat-list-section');
                    if (panel) panel.innerHTML = data.html;
                    this.aturPagination(data);

                    const sekarang = Number(data.stats.belumDibaca) || 0;
                    if (sekarang > sebelum) {
                        this.notif = sekarang - sebelum;
                        if (this.notifTimer) clearTimeout(this.notifTimer);
                        this.notifTimer = setTimeout(() => { this.notif = 0; }, 6000);
                    }
                } catch (e) {
                    // Abaikan — polling diulang berikutnya.
                }
            },

            aturPagination(data) {
                this.halaman = 1;
                this.prasimpan = {};
                this.sibuk = false;
                this.hasMore = typeof data.hasMore === 'boolean' ? data.hasMore : (document.getElementById('muat-lagi') !== null);
                this.pasangObserver();
            },

            pasangObserver() {
                this.lepasObserver();
                if (!this.hasMore) return;

                const sentinel = document.getElementById('muat-lagi');
                if (!sentinel) {
                    this.hasMore = false;
                    return;
                }

                this.observer = new IntersectionObserver((entri) => {
                    if (entri.some((e) => e.isIntersecting)) {
                        this.muat();
                    }
                }, { rootMargin: '240px 0px' });
                this.observer.observe(sentinel);
            },

            lepasObserver() {
                if (this.observer) {
                    this.observer.disconnect();
                    this.observer = null;
                }
            },

            async muat() {
                if (this.sibuk || !this.hasMore) return;

                const berikut = this.halaman + 1;
                if (this.prasimpan[berikut] !== undefined) {
                    this.sisipkan(this.prasimpan[berikut]);
                    delete this.prasimpan[berikut];
                    this.halaman = berikut;
                    if (this.prasimpan[berikut + 1] === undefined) {
                        this.prefetch(berikut + 1);
                    }
                    return;
                }

                this.sibuk = true;
                this.tampilPemuat();

                try {
                    const resp = await fetch(this.urlHalaman(berikut), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    });
                    if (!resp.ok) return;

                    const data = await resp.json();
                    this.sisipkan(data.html);
                    this.halaman = berikut;
                    this.hasMore = Boolean(data.hasMore);
                    if (!this.hasMore) {
                        this.sembunyikanPemuat();
                    } else {
                        this.kosongkanPemuat();
                        this.prefetch(this.halaman + 1);
                    }
                } catch (e) {
                    // Biarkan — pengguna bisa scroll lagi.
                } finally {
                    this.sibuk = false;
                }
            },

            async prefetch(halaman) {
                if (!this.hasMore || this.sibuk) return;
                const url = this.urlHalaman(halaman);
                const resp = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                if (!resp.ok) return;
                const data = await resp.json();
                this.prasimpan[halaman] = data.html;
                if (!data.hasMore && this.hasMore && halaman === this.halaman + 1) {
                    this.hasMore = false;
                }
            },

            urlHalaman(halaman) {
                return this.endpointKonten + '?' + (this.query ? this.query + '&' : '') + 'page=' + halaman;
            },

            sisipkan(html) {
                const daftar = document.getElementById('daftar-konversasi');
                const sentinel = document.getElementById('muat-lagi');
                if (!daftar) {
                    this.hasMore = false;
                    return;
                }
                if (html.trim() === '') {
                    this.hasMore = false;
                    this.sembunyikanPemuat();
                    return;
                }
                if (sentinel) {
                    sentinel.insertAdjacentHTML('beforebegin', html);
                } else {
                    daftar.insertAdjacentHTML('beforeend', html);
                }
            },

            tampilPemuat() {
                const sentinel = document.getElementById('muat-lagi');
                if (sentinel) {
                    sentinel.classList.add('flex', 'items-center', 'justify-center');
                    sentinel.innerHTML = '<span class="inline-flex items-center gap-2 text-xs font-medium text-slate-400"><svg class="h-4 w-4 animate-spin text-slate-300" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>Memuat lebih banyak…</span>';
                }
            },

            kosongkanPemuat() {
                const sentinel = document.getElementById('muat-lagi');
                if (sentinel) sentinel.innerHTML = '';
            },

            sembunyikanPemuat() {
                const sentinel = document.getElementById('muat-lagi');
                if (sentinel) sentinel.style.display = 'none';
                this.lepasObserver();
            },

            updateStats(stats) {
                const daftar = {
                    'stat-total': stats.total,
                    'stat-belum': stats.belumDibaca,
                    'stat-hari': stats.hariIni,
                    'stat-pasien': stats.pasienUnik,
                    'stat-tak': stats.takTerdaftar,
                };

                for (const [id, nilai] of Object.entries(daftar)) {
                    const el = document.getElementById(id);
                    if (el) {
                        el.textContent = formatAngka(Number(nilai) || 0);
                        el.dataset.nilai = nilai;
                    }
                }

                const totalEl = document.getElementById('chat-total');
                if (totalEl) totalEl.textContent = formatAngka(Number(daftar['stat-total']) || 0) + ' pesan';

                const judul = document.title.replace(/^\(\d+\) /, '');
                document.title = (Number(daftar['stat-belum']) > 0 ? '(' + Number(daftar['stat-belum']) + ') ' : '') + judul;
            },
        }));

        function formatAngka(n) {
            return new Intl.NumberFormat('id-ID').format(n);
        }
    });
</script>
@endsection