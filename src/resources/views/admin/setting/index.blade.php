@extends('layouts.app')

@section('title', 'Template Pesan & Kategori')
@section('page-title', 'Pengaturan Template')

@section('content')
@php
    $highlight = fn (string $t) => preg_replace(
        '/\{([a-zA-Z0-9_]+)\}/',
        '<span class="inline-block rounded-md bg-sky-100/90 px-1.5 py-0.5 font-mono text-[11px] font-bold text-sky-700 ring-1 ring-inset ring-sky-300/50">$0</span>',
        e($t)
    );

    $colorMap = [
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'ring' => 'ring-emerald-300/60', 'dot' => 'bg-emerald-500', 'badge' => 'bg-emerald-500/10 text-emerald-700 ring-emerald-600/20'],
        'sky'     => ['bg' => 'bg-sky-50',     'text' => 'text-sky-700',     'ring' => 'ring-sky-300/60',     'dot' => 'bg-sky-500',     'badge' => 'bg-sky-500/10 text-sky-700 ring-sky-600/20'],
        'amber'   => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'ring' => 'ring-amber-300/60',   'dot' => 'bg-amber-500',   'badge' => 'bg-amber-500/10 text-amber-700 ring-amber-600/20'],
        'rose'    => ['bg' => 'bg-rose-50',    'text' => 'text-rose-700',    'ring' => 'ring-rose-300/60',    'dot' => 'bg-rose-500',    'badge' => 'bg-rose-500/10 text-rose-700 ring-rose-600/20'],
        'purple'  => ['bg' => 'bg-purple-50',  'text' => 'text-purple-700',  'ring' => 'ring-purple-300/60',  'dot' => 'bg-purple-500',  'badge' => 'bg-purple-500/10 text-purple-700 ring-purple-600/20'],
        'indigo'  => ['bg' => 'bg-indigo-50',  'text' => 'text-indigo-700',  'ring' => 'ring-indigo-300/60',  'dot' => 'bg-indigo-500',  'badge' => 'bg-indigo-500/10 text-indigo-700 ring-indigo-600/20'],
        'teal'    => ['bg' => 'bg-teal-50',    'text' => 'text-teal-700',    'ring' => 'ring-teal-300/60',    'dot' => 'bg-teal-500',    'badge' => 'bg-teal-500/10 text-teal-700 ring-teal-600/20'],
    ];

    $channelStyle = [
        'WhatsApp' => 'bg-emerald-50 text-emerald-700 ring-emerald-300/60',
        'SMS'      => 'bg-amber-50 text-amber-700 ring-amber-300/60',
        'Email'    => 'bg-blue-50 text-blue-700 ring-blue-300/60',
    ];

    $adaFilter = (bool) ($filters['q'] || $filters['category_id'] || $filters['channel'] || $filters['status'] !== '');
@endphp

<div x-data="{
    toast: { show: false, message: '', type: 'success' },
    previewModal: {
        open: false,
        title: '',
        category: '',
        channel: 'WhatsApp',
        rawText: '',
        simulatedText: '',
        viewMode: 'simulated'
    },
    imageModal: {
        open: false,
        url: '',
        title: '',
        scale: 1,
        tx: 0,
        ty: 0,
        dragging: false,
        startX: 0,
        startY: 0,
    },
    variables: @js($variables),

    notify(msg, type = 'success') {
        this.toast.message = msg;
        this.toast.type = type;
        this.toast.show = true;
        setTimeout(() => { this.toast.show = false; }, 3500);
    },

    copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text);
        } else {
            let textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            textArea.remove();
        }
        this.notify('Pesan berhasil disalin ke clipboard!');
    },

    openLivePreview(t) {
        this.previewModal.title = t.judul;
        this.previewModal.category = t.category ? t.category.nama : 'Umum';
        this.previewModal.channel = t.channel;
        this.previewModal.rawText = t.konten;

        let sim = t.konten;
        this.variables.forEach(v => {
            const regex = new RegExp(v.var.replace(/([{}])/g, '\\$1'), 'g');
            sim = sim.replace(regex, v.contoh);
        });
        this.previewModal.simulatedText = sim;
        this.previewModal.viewMode = 'simulated';
        this.previewModal.open = true;
    },

    openImage(url, title) {
        this.imageModal.url = url;
        this.imageModal.title = title;
        this.imageModal.scale = 1;
        this.imageModal.tx = 0;
        this.imageModal.ty = 0;
        this.imageModal.open = true;
    },

    closeImage() {
        this.imageModal.open = false;
        this.imageModal.dragging = false;
    },

    imageZoomIn() {
        this.imageModal.scale = Math.min(this.imageModal.scale + 0.25, 5);
    },

    imageZoomOut() {
        this.imageModal.scale = Math.max(this.imageModal.scale - 0.25, 0.5);
        if (this.imageModal.scale <= 1) { this.imageModal.tx = 0; this.imageModal.ty = 0; }
    },

    imageResetZoom() {
        this.imageModal.scale = 1;
        this.imageModal.tx = 0;
        this.imageModal.ty = 0;
    },

    imageWheel(e) {
        const next = Math.min(Math.max(this.imageModal.scale + (e.deltaY < 0 ? 0.15 : -0.15), 0.5), 5);
        this.imageModal.scale = next;
        if (next <= 1) { this.imageModal.tx = 0; this.imageModal.ty = 0; }
    },

    imageStartDrag(e) {
        if (this.imageModal.scale <= 1) return;
        this.imageModal.dragging = true;
        this.imageModal.startX = e.clientX - this.imageModal.tx;
        this.imageModal.startY = e.clientY - this.imageModal.ty;
    },

    imageDrag(e) {
        if (!this.imageModal.dragging) return;
        this.imageModal.tx = e.clientX - this.imageModal.startX;
        this.imageModal.ty = e.clientY - this.imageModal.startY;
    },

    imageEndDrag() {
        this.imageModal.dragging = false;
    },

    downloadImage() {
        const url = this.imageModal.url;
        const base = (this.imageModal.title || 'foto-header').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
        fetch(url)
            .then(r => { if (!r.ok) throw new Error('fetch failed'); return r.blob(); })
            .then(blob => {
                const extMap = { 'image/png': 'png', 'image/jpeg': 'jpg', 'image/webp': 'webp', 'image/gif': 'gif', 'image/svg+xml': 'svg', 'image/avif': 'avif', 'image/bmp': 'bmp' };
                const ext = extMap[blob.type] || 'jpg';
                const objUrl = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = objUrl;
                a.download = base + '.' + ext;
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(objUrl);
                this.notify('Foto berhasil diunduh.');
            })
            .catch(() => { window.open(url, '_blank'); });
    }
}" class="space-y-6">

    {{-- ===== Toast Notification ===== --}}
    <div x-cloak x-show="toast.show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
         x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed top-5 right-5 z-50 flex items-center gap-3 rounded-2xl bg-slate-900/95 px-4 py-3.5 text-white shadow-xl ring-1 ring-white/10 backdrop-blur">
        <div class="flex h-8 w-8 items-center justify-center rounded-xl"
             :class="toast.type === 'success' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400'">
            <template x-if="toast.type === 'success'">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
            </template>
            <template x-if="toast.type !== 'success'">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 8.25h.008v.008H12v-.008Z" /></svg>
            </template>
        </div>
        <div class="text-xs font-semibold" x-text="toast.message"></div>
        <button @click="toast.show = false" class="ml-2 text-slate-400 hover:text-white">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
        </button>
    </div>

    {{-- ===== Flash Session Alerts ===== --}}
    @if (session('success'))
        <div class="flex items-center gap-3 rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-200">
            <svg class="h-5 w-5 flex-shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            <span class="flex-1">{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="flex items-center gap-3 rounded-2xl bg-rose-50 p-4 text-sm font-semibold text-rose-800 ring-1 ring-rose-200">
            <svg class="h-5 w-5 flex-shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 8.25h.008v.008H12v-.008Z" /></svg>
            <span class="flex-1">{{ session('error') }}</span>
        </div>
    @endif

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}" class="rounded transition hover:text-sky-600">Dashboard</a>
            <svg class="h-3.5 w-3.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="text-slate-400">Pengaturan</span>
            <svg class="h-3.5 w-3.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-700">Template Pesan & Kategori</span>
        </nav>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-black tracking-tight text-slate-900">Manajemen Template & Kategori</h2>
                <p class="mt-1 text-sm text-slate-500">Kelola template pesan WhatsApp dan kategori peruntukan.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                {{-- Dropdown Export --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.away="open = false"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-xs transition hover:bg-slate-50">
                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        <span>Export</span>
                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </button>
                    <div x-cloak x-show="open"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute right-0 z-30 mt-2 w-52 rounded-2xl bg-white p-1.5 shadow-xl ring-1 ring-slate-200">
                        <a href="{{ route('admin.setting.export.download', ['format' => 'xlsx']) }}"
                           class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-700">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-100 font-mono text-[10px] font-bold text-emerald-700">XLS</span>
                            Export Excel (.xlsx)
                        </a>
                        <a href="{{ route('admin.setting.export.download', ['format' => 'csv']) }}"
                           class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-sky-50 hover:text-sky-700">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-sky-100 font-mono text-[10px] font-bold text-sky-700">CSV</span>
                            Export CSV (.csv)
                        </a>
                    </div>
                </div>

                {{-- Tambah Template --}}
                <a href="{{ route('admin.setting.template.create') }}"
                   title="Buat template baru dan daftarkan ke Meta"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-sky-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Tambah Template
                </a>

                {{-- Sinkronkan dari Meta --}}
                <form method="POST" action="{{ route('admin.setting.template.sync-meta') }}">
                    @csrf
                    <button type="submit" title="Tarik template langsung dari Meta WhatsApp (message_templates)"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-xs transition hover:bg-emerald-50">
                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                        Sinkron Meta
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ===== Statistik Cepat ===== --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <div class="flex items-center gap-3.5 rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-200/80">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600 ring-1 ring-sky-100">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.269Z" /></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xl font-black tabular-nums text-slate-900">{{ $stats['total_template'] }}</p>
                <p class="truncate text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Template</p>
            </div>
        </div>
        <div class="flex items-center gap-3.5 rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-200/80">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" /></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xl font-black tabular-nums text-slate-900">{{ $stats['total_kategori'] }}</p>
                <p class="truncate text-[11px] font-bold uppercase tracking-wider text-slate-400">Kategori Pesan</p>
            </div>
        </div>
        <div class="flex items-center gap-3.5 rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-200/80">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600 ring-1 ring-purple-100">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xl font-black tabular-nums text-slate-900">{{ $stats['template_aktif'] }}</p>
                <p class="truncate text-[11px] font-bold uppercase tracking-wider text-slate-400">Template Aktif</p>
            </div>
        </div>
        <div class="flex items-center gap-3.5 rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-200/80">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xl font-black tabular-nums text-slate-900">{{ number_format($stats['total_dipakai'], 0, ',', '.') }}</p>
                <p class="truncate text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Pengiriman</p>
            </div>
        </div>
    </div>

    {{-- ===== Sub-Navigasi Pengaturan ===== --}}
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex gap-6 overflow-x-auto" aria-label="Tabs">
            <a href="{{ route('admin.setting.index') }}"
               class="inline-flex items-center gap-2 whitespace-nowrap border-b-2 border-sky-600 px-1 py-3 text-sm font-bold text-sky-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.269Z" /></svg>
                <span>Daftar Template Pesan</span>
                <span class="rounded-full bg-sky-50 px-2 py-0.5 text-xs font-bold tabular-nums text-sky-700">{{ $stats['total_template'] }}</span>
            </a>
            <a href="{{ route('admin.setting.kategori.index') }}"
               class="inline-flex items-center gap-2 whitespace-nowrap border-b-2 border-transparent px-1 py-3 text-sm font-medium text-slate-500 transition hover:border-slate-300 hover:text-slate-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /></svg>
                <span>Kelola Kategori</span>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold tabular-nums text-slate-600">{{ $stats['total_kategori'] }}</span>
            </a>
            <a href="{{ route('admin.setting.biaya') }}"
               class="inline-flex items-center gap-2 whitespace-nowrap border-b-2 border-transparent px-1 py-3 text-sm font-medium text-slate-500 transition hover:border-slate-300 hover:text-slate-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg>
                <span>Biaya Template</span>
            </a>
        </nav>
    </div>

    {{-- ===== Daftar Template Pesan ===== --}}
    <div class="space-y-6">

        {{-- Filter & Pencarian (server-side, auto-submit + debounce) --}}
        <form id="filterForm" method="GET" action="{{ route('admin.setting.index') }}"
              class="rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-200">
            <div class="grid grid-cols-1 items-center gap-3 md:grid-cols-12">
                {{-- Pencarian --}}
                <div class="relative md:col-span-4">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    </div>
                    <input type="text"
                           id="searchInput"
                           name="q"
                           value="{{ $filters['q'] }}"
                           placeholder="Cari judul, isi pesan, atau kode..."
                           class="block h-10 w-full rounded-xl border-0 pl-10 pr-9 text-xs text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500">
                    @if ($filters['q'])
                        <button type="button" title="Hapus kata kunci"
                                onclick="document.getElementById('searchInput').value = ''; document.getElementById('filterForm').submit();"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>
                    @endif
                </div>

                {{-- Filter Kategori --}}
                <div class="md:col-span-3">
                    <select name="category_id" onchange="document.getElementById('filterForm').submit();"
                            class="block h-10 w-full cursor-pointer rounded-xl border-0 px-3 text-xs text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (string) $filters['category_id'] === (string) $cat->id ? 'selected' : '' }}>
                                {{ $cat->nama }} ({{ $cat->templates_count }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Channel --}}
                <div class="md:col-span-2">
                    <select name="channel" onchange="document.getElementById('filterForm').submit();"
                            class="block h-10 w-full cursor-pointer rounded-xl border-0 px-3 text-xs text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500">
                        <option value="">Semua Saluran</option>
                        <option value="WhatsApp" {{ $filters['channel'] === 'WhatsApp' ? 'selected' : '' }}>WhatsApp</option>
                        <option value="SMS" {{ $filters['channel'] === 'SMS' ? 'selected' : '' }}>SMS</option>
                        <option value="Email" {{ $filters['channel'] === 'Email' ? 'selected' : '' }}>Email</option>
                    </select>
                </div>

                {{-- Filter Status --}}
                <div class="md:col-span-2">
                    <select name="status" onchange="document.getElementById('filterForm').submit();"
                            class="block h-10 w-full cursor-pointer rounded-xl border-0 px-3 text-xs text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500">
                        <option value="">Semua Status</option>
                        <option value="1" {{ $filters['status'] === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ $filters['status'] === '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                {{-- Reset Filter --}}
                <div class="flex justify-end md:col-span-1">
                    @if ($adaFilter)
                        <a href="{{ route('admin.setting.index') }}" title="Reset filter"
                           class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-800">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                        </a>
                    @endif
                </div>
            </div>

            @if ($adaFilter)
                <p class="mt-3 flex items-center gap-1.5 border-t border-slate-100 pt-3 text-[11px] font-medium text-slate-400">
                    <svg class="h-3.5 w-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" /></svg>
                    Filter aktif — menampilkan {{ $templates->total() }} template.
                    <a href="{{ route('admin.setting.index') }}" class="font-bold text-sky-600 hover:text-sky-700">Reset</a>
                </p>
            @endif
        </form>

        {{-- Daftar Template / Empty State --}}
        @if ($templates->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                </div>
                <h3 class="mt-4 text-base font-bold text-slate-800">{{ $adaFilter ? 'Tidak ada template yang cocok' : 'Belum ada template pesan' }}</h3>
                <p class="mt-1 max-w-sm text-xs text-slate-400">
                    {{ $adaFilter
                        ? 'Coba sesuaikan kata kunci pencarian atau reset filter untuk melihat semua template.'
                        : 'Mulai dengan membuat template pesan pertama, lalu daftarkan ke Meta WhatsApp.' }}
                </p>
                <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                    @if ($adaFilter)
                        <a href="{{ route('admin.setting.index') }}"
                           class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Reset Filter</a>
                    @endif
                    <a href="{{ route('admin.setting.template.create') }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-sky-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Tambah Template
                    </a>
                </div>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($templates as $t)
                    @php
                        $cat = $t->category;
                        $catColor = $cat ? ($colorMap[$cat->warna] ?? $colorMap['sky']) : $colorMap['sky'];
                        $metaTone = match ($t->meta_status) {
                            'APPROVED' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                            'PENDING' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                            'REJECTED' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                            default => 'bg-slate-100 text-slate-600 ring-slate-300/60',
                        };
                        $metaButtons = collect($t->meta_components ?? [])
                            ->where('type', 'BUTTON')
                            ->flatMap(fn ($c) => $c['buttons'] ?? [])
                            ->pluck('text')
                            ->filter()
                            ->values();
                        $imageRendered = $t->image_url ? (\Str::startsWith($t->image_url, ['http://', 'https://']) ? $t->image_url : asset($t->image_url)) : null;
                    @endphp
                    <article x-data="{ open: false }"
                             class="group overflow-hidden rounded-2xl bg-white shadow-xs ring-1 ring-slate-200 transition hover:shadow-sm">
                        {{-- Baris utama (klik untuk buka detail) --}}
                        <div role="button" tabindex="0" :aria-expanded="open"
                             @click="open = !open"
                             @keydown.enter="open = !open"
                             @keydown.space.prevent="open = !open"
                             class="flex w-full cursor-pointer items-center gap-4 p-4 text-left sm:p-5">
                            {{-- Thumbnail / foto header (klik untuk lihat besar) --}}
                            <div class="h-14 w-14 flex-shrink-0 overflow-hidden rounded-xl ring-1 ring-slate-200">
                                @if ($imageRendered)
                                    <button type="button"
                                            @click.stop="openImage(@js($imageRendered), @js($t->judul))"
                                            title="Lihat foto header"
                                            class="group/ph relative block h-full w-full">
                                        <img src="{{ $imageRendered }}" alt="Foto header {{ $t->judul }}"
                                             class="h-full w-full object-cover transition duration-200 group-hover/ph:scale-110"
                                             loading="lazy" draggable="false">
                                        <span class="absolute inset-0 flex items-center justify-center bg-slate-900/0 text-transparent transition duration-200 group-hover/ph:bg-slate-900/40 group-hover/ph:text-white">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                                        </span>
                                    </button>
                                @else
                                    <div class="flex h-full w-full items-center justify-center bg-slate-100 text-slate-400">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.269Z" /></svg>
                                    </div>
                                @endif
                            </div>

                            {{-- Info utama --}}
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <h3 class="text-sm font-bold text-slate-900" title="{{ $t->judul }}">{{ $t->judul }}</h3>
                                    @if ($t->is_active)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-600/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500 ring-1 ring-slate-300/60">Nonaktif</span>
                                    @endif
                                    @if ($t->meta_status)
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 ring-inset {{ $metaTone }}">
                                            {{ ucfirst(strtolower($t->meta_status)) }} · Meta
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-[10px] font-bold ring-1 ring-inset {{ $catColor['badge'] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $catColor['dot'] }}"></span>
                                        {{ $cat?->nama ?? 'Tanpa Kategori' }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 ring-inset {{ $channelStyle[$t->channel] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                        {{ $t->channel }}
                                    </span>
                                    <span class="font-mono text-[10px] font-semibold text-slate-400">{{ $t->kode }}</span>
                                </div>

                                <p class="mt-1.5 line-clamp-1 max-w-3xl text-xs leading-relaxed text-slate-500">
                                    {!! $highlight($t->konten) !!}
                                </p>
                            </div>

                            {{-- Aksi + chevron --}}
                            <div class="flex flex-shrink-0 items-center gap-0.5" @click.stop>
                                <button type="button"
                                        @click="openLivePreview(@js($t))"
                                        title="Pratinjau Live WhatsApp"
                                        class="rounded-lg p-2 text-emerald-600 transition hover:bg-emerald-50">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                </button>
                                <button type="button"
                                        @click="copyText(@js($t->konten))"
                                        title="Salin Teks Pesan"
                                        class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" /></svg>
                                </button>
                                <a href="{{ route('admin.setting.template.edit', $t) }}"
                                   title="Edit Template"
                                   class="rounded-lg p-2 text-slate-400 transition hover:bg-sky-50 hover:text-sky-600">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                </a>
                                <svg class="h-4 w-4 flex-shrink-0 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                                     fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </div>
                        </div>

                        {{-- Detail expandable --}}
                        <div x-cloak x-show="open"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="border-t border-slate-100 bg-slate-50/60 px-4 py-4 sm:px-5">
                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Isi Pesan</p>
                                    <p class="mt-1.5 rounded-xl bg-white p-3 text-xs leading-relaxed text-slate-700 ring-1 ring-inset ring-slate-200">
                                        {!! $highlight($t->konten) !!}
                                    </p>
                                    @if ($t->deskripsi)
                                        <p class="mt-2 text-[11px] text-slate-500"><span class="font-bold text-slate-600">Deskripsi:</span> {{ $t->deskripsi }}</p>
                                    @endif
                                </div>

                                <div class="space-y-3">
                                    @if ($metaButtons->isNotEmpty())
                                        <div>
                                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Tombol (Meta)</p>
                                            <div class="mt-1.5 flex flex-wrap gap-1.5">
                                                @foreach ($metaButtons as $b)
                                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-700 ring-1 ring-inset ring-slate-200">
                                                        <svg class="h-3.5 w-3.5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                                        {{ $b }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if ($t->meta_template_id)
                                        <div class="rounded-xl bg-white p-3 ring-1 ring-inset ring-slate-200">
                                            <dl class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-[11px]">
                                                <dt class="font-medium text-slate-400">Nama Meta</dt>
                                                <dd class="truncate font-mono font-semibold text-slate-700">{{ $t->meta_template_name }}</dd>
                                                <dt class="font-medium text-slate-400">Bahasa</dt>
                                                <dd class="font-semibold text-slate-700">{{ $t->meta_language ?? '—' }}</dd>
                                                @if ($t->last_synced_at)
                                                    <dt class="font-medium text-slate-400">Sinkron terakhir</dt>
                                                    <dd class="text-slate-700">{{ $t->last_synced_at->format('d M Y H:i') }}</dd>
                                                @endif
                                            </dl>
                                        </div>
                                    @endif

                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[11px] text-slate-400">
                                        <span>Dipakai <strong class="font-bold tabular-nums text-slate-600">{{ $t->dipakai_count }}x</strong></span>
                                        <span>&middot;</span>
                                        <span>Diperbarui {{ $t->updated_at?->diffForHumans() }}</span>
                                        @if ($imageRendered)
                                            <button type="button" @click="openImage(@js($imageRendered), @js($t->judul))"
                                                    title="Lihat & unduh foto header"
                                                    class="inline-flex items-center gap-1 font-semibold text-sky-600 transition hover:text-sky-700 hover:underline">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                                                Foto header
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Pagination (server-side) --}}
            <div>
                {{ $templates->links() }}
            </div>
        @endif
    </div>

    {{-- ======================================================== --}}
    {{-- MODAL LIVE SMARTPHONE PREVIEW WHATSAPP --}}
    {{-- ======================================================== --}}
    <div x-cloak x-show="previewModal.open"
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <div x-show="previewModal.open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="previewModal.open = false"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>

            <div x-show="previewModal.open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative w-full max-w-lg transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all">

                {{-- Modal Header --}}
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900" x-text="previewModal.title"></h3>
                        <p class="text-xs text-slate-500">Pratinjau tampilan aktual pesan di layar pasien.</p>
                    </div>
                    <button @click="previewModal.open = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Mode Switcher --}}
                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-6 py-2.5">
                    <span class="text-xs font-semibold text-slate-600">Mode Pratinjau:</span>
                    <div class="flex rounded-lg bg-slate-200/70 p-0.5 text-[11px] font-bold">
                        <button @click="previewModal.viewMode = 'simulated'"
                                :class="previewModal.viewMode === 'simulated' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                class="rounded-md px-3 py-1 transition">
                            Data Pasien (Simulasi)
                        </button>
                        <button @click="previewModal.viewMode = 'token'"
                                :class="previewModal.viewMode === 'token' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                class="rounded-md px-3 py-1 transition">
                            Kode Token Asli
                        </button>
                    </div>
                </div>

                {{-- Smartphone Frame Simulation --}}
                <div class="p-6">
                    <div class="mx-auto max-w-sm rounded-[32px] border-4 border-slate-800 bg-[#E5DDD5] p-3 shadow-xl">
                        {{-- WhatsApp Header --}}
                        <div class="flex items-center justify-between rounded-t-2xl bg-[#075E54] px-4 py-2.5 text-white">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-700 text-xs font-bold text-white ring-1 ring-white/30">
                                    RS
                                </div>
                                <div>
                                    <p class="text-xs font-bold leading-tight">RS Bhayangkara Bogor</p>
                                    <p class="text-[10px] text-emerald-200">Online • Terverifikasi</p>
                                </div>
                            </div>
                            <svg class="h-4 w-4 text-white/80" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/></svg>
                        </div>

                        {{-- Chat Area --}}
                        <div class="flex min-h-[220px] flex-col justify-end space-y-3 px-2 py-4">
                            <div class="self-center rounded-lg bg-amber-100/90 px-3 py-1 text-center text-[10px] font-medium text-amber-800 shadow-xs">
                                Pesan ini dikirim secara otomatis via WhatsApp Gateway
                            </div>

                            <div class="max-w-[90%] self-end rounded-2xl rounded-tr-xs bg-[#DCF8C6] p-3 text-slate-800 shadow-xs">
                                <p class="text-xs leading-relaxed whitespace-pre-line"
                                   x-text="previewModal.viewMode === 'simulated' ? previewModal.simulatedText : previewModal.rawText">
                                </p>
                                <div class="mt-1 flex items-center justify-end gap-1 text-[9px] text-slate-500">
                                    <span>{{ date('H:i') }}</span>
                                    <svg class="h-3.5 w-3.5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5m-5 13.5 6-6" /></svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-6 py-4">
                    <button type="button" @click="copyText(previewModal.viewMode === 'simulated' ? previewModal.simulatedText : previewModal.rawText)"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                        <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" /></svg>
                        Salin Teks Preview
                    </button>
                    <button type="button" @click="previewModal.open = false"
                            class="rounded-xl bg-slate-900 px-5 py-2 text-xs font-bold text-white hover:bg-slate-800">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- MODAL FOTO HEADER (lightbox + zoom + unduh) --}}
    {{-- ======================================================== --}}
    <div x-cloak x-show="imageModal.open"
         class="fixed inset-0 z-[60] flex items-center justify-center p-4"
         role="dialog" aria-modal="true" aria-label="Foto header"
         @keydown.escape.window="closeImage()">
        <div x-show="imageModal.open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="closeImage()"
             class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm"></div>

        <div x-show="imageModal.open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
             class="relative flex w-full max-w-4xl flex-col overflow-hidden rounded-3xl bg-slate-900 shadow-2xl ring-1 ring-white/10">

            {{-- Header --}}
            <div class="flex items-center justify-between gap-3 border-b border-white/10 px-5 py-3">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-sky-500/15 text-sky-400">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-white" x-text="imageModal.title"></p>
                        <p class="text-[11px] text-slate-400">Foto header template</p>
                    </div>
                </div>
                <button type="button" @click="closeImage()" title="Tutup (Esc)"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-white/10 hover:text-white">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            {{-- Toolbar zoom / unduh --}}
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-white/10 bg-white/5 px-4 py-2.5">
                <p class="text-[11px] font-medium text-slate-400">Gulir roda mouse untuk zoom · geser untuk menggeser gambar.</p>
                <div class="flex flex-wrap items-center gap-1.5">
                    <button type="button" @click="imageZoomOut()" title="Perkecil (−)"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-300 transition hover:bg-white/10 hover:text-white">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12h-6m9-9a9 9 0 1 1-6.364-2.636" /></svg>
                    </button>
                    <span class="w-14 text-center text-xs font-bold tabular-nums text-white" x-text="Math.round(imageModal.scale * 100) + '%'"></span>
                    <button type="button" @click="imageZoomIn()" title="Perbesar (+)"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-300 transition hover:bg-white/10 hover:text-white">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12h-6m9-9a9 9 0 1 1-6.364-2.636M12 15v-6" /></svg>
                    </button>
                    <button type="button" @click="imageResetZoom()" title="Kembalikan ukuran"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-300 transition hover:bg-white/10 hover:text-white">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25" /></svg>
                    </button>
                    <span class="mx-1 h-5 w-px bg-white/15"></span>
                    <button type="button" @click="downloadImage()" title="Unduh gambar"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-sky-500 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-sky-400">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        Unduh
                    </button>
                </div>
            </div>

            {{-- Stage gambar (zoom + pan) --}}
            <div class="relative flex min-h-[380px] flex-1 items-center justify-center overflow-hidden bg-slate-950/70 p-5"
                 @wheel.prevent="imageWheel($event)"
                 @mousedown="imageStartDrag($event)"
                 @mousemove="imageDrag($event)"
                 @mouseup="imageEndDrag()"
                 @mouseleave="imageEndDrag()"
                 :class="imageModal.dragging ? 'cursor-grabbing' : (imageModal.scale > 1 ? 'cursor-move' : 'cursor-zoom-in')">
                <img :src="imageModal.url"
                     :alt="imageModal.title"
                     :style="{ transform: 'translate(' + imageModal.tx + 'px, ' + imageModal.ty + 'px) scale(' + imageModal.scale + ')' }"
                     :class="imageModal.dragging ? 'transition-none' : 'transition-transform duration-150 ease-out'"
                     class="max-h-[62vh] max-w-full select-none object-contain"
                     draggable="false">
            </div>
        </div>
    </div>

</div>

{{-- JS Debounce untuk Server-Side Search --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const filterForm = document.getElementById('filterForm');
    let debounceTimer;

    if (searchInput && filterForm) {
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                filterForm.submit();
            }, 450);
        });
    }
});
</script>
@endsection
