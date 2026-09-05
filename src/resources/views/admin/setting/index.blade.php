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
@endphp

<div x-data="{
    activeTab: '{{ $tab }}',
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
    templateModal: {
        open: false,
        isEdit: false,
        id: null,
        category_id: '',
        judul: '',
        channel: 'WhatsApp',
        konten: '',
        deskripsi: '',
        is_active: true
    },
    categoryModal: {
        open: false,
        isEdit: false,
        id: null,
        nama: '',
        warna: 'sky',
        deskripsi: '',
        is_active: true
    },
    deleteModal: {
        open: false,
        actionUrl: '',
        itemName: '',
        itemType: ''
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

    insertVariable(v, targetField = 'templateModal.konten') {
        const textarea = document.getElementById('templateTextarea');
        if (!textarea) {
            this.templateModal.konten += ' ' + v;
            return;
        }
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const current = this.templateModal.konten;
        this.templateModal.konten = current.substring(0, start) + ' ' + v + ' ' + current.substring(end);
        this.$nextTick(() => {
            textarea.focus();
            textarea.setSelectionRange(start + v.length + 2, start + v.length + 2);
        });
        this.notify('Variabel ' + v + ' ditambahkan ke teks');
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

    openCreateTemplate() {
        this.templateModal.isEdit = false;
        this.templateModal.id = null;
        this.templateModal.category_id = '{{ $categories->first()?->id ?? '' }}';
        this.templateModal.judul = '';
        this.templateModal.channel = 'WhatsApp';
        this.templateModal.konten = '';
        this.templateModal.deskripsi = '';
        this.templateModal.is_active = true;
        this.templateModal.open = true;
    },

    openEditTemplate(t) {
        this.templateModal.isEdit = true;
        this.templateModal.id = t.id;
        this.templateModal.category_id = t.template_category_id || '';
        this.templateModal.judul = t.judul;
        this.templateModal.channel = t.channel;
        this.templateModal.konten = t.konten;
        this.templateModal.deskripsi = t.deskripsi || '';
        this.templateModal.is_active = !!t.is_active;
        this.templateModal.open = true;
    },

    openCreateCategory() {
        this.categoryModal.isEdit = false;
        this.categoryModal.id = null;
        this.categoryModal.nama = '';
        this.categoryModal.warna = 'sky';
        this.categoryModal.deskripsi = '';
        this.categoryModal.is_active = true;
        this.categoryModal.open = true;
    },

    openEditCategory(c) {
        this.categoryModal.isEdit = true;
        this.categoryModal.id = c.id;
        this.categoryModal.nama = c.nama;
        this.categoryModal.warna = c.warna || 'sky';
        this.categoryModal.deskripsi = c.deskripsi || '';
        this.categoryModal.is_active = !!c.is_active;
        this.categoryModal.open = true;
    },

    confirmDelete(url, name, type) {
        this.deleteModal.actionUrl = url;
        this.deleteModal.itemName = name;
        this.deleteModal.itemType = type;
        this.deleteModal.open = true;
    }
}" class="space-y-6">

    {{-- Toast Notification --}}
    <div x-cloak x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
         x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed top-5 right-5 z-50 flex items-center gap-3 rounded-2xl bg-slate-900/95 px-4 py-3.5 text-white shadow-xl backdrop-blur ring-1 ring-white/10">
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
                <p class="mt-1 text-sm text-slate-500">Kelola draft pesan otomatis WhatsApp/SMS, variabel dinamis, dan kategori peruntukan.</p>
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
                         class="absolute right-0 mt-2 w-52 rounded-2xl bg-white p-1.5 shadow-xl ring-1 ring-slate-200 z-30">
                        <a href="{{ route('admin.setting.export.download', ['format' => 'xlsx']) }}"
                           class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-700">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 font-mono text-[10px] font-bold">XLS</span>
                            Export Excel (.xlsx)
                        </a>
                        <a href="{{ route('admin.setting.export.download', ['format' => 'csv']) }}"
                           class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-sky-50 hover:text-sky-700">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-sky-100 text-sky-700 font-mono text-[10px] font-bold">CSV</span>
                            Export CSV (.csv)
                        </a>
                    </div>
                </div>

                {{-- Import Button --}}
                <a href="{{ route('admin.setting.import.index') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-xs transition hover:bg-slate-50">
                    <svg class="h-4 w-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                    Import Data
                </a>

                {{-- Tambah Template Button --}}
                <button @click="openCreateTemplate()"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Tambah Template
                </button>
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
                <p class="text-xl font-black text-slate-900 tabular-nums">{{ $stats['total_template'] }}</p>
                <p class="truncate text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Template</p>
            </div>
        </div>
        <div class="flex items-center gap-3.5 rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-200/80">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" /></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xl font-black text-slate-900 tabular-nums">{{ $stats['total_kategori'] }}</p>
                <p class="truncate text-[11px] font-bold uppercase tracking-wider text-slate-400">Kategori Pesan</p>
            </div>
        </div>
        <div class="flex items-center gap-3.5 rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-200/80">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600 ring-1 ring-purple-100">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xl font-black text-slate-900 tabular-nums">{{ $stats['template_aktif'] }}</p>
                <p class="truncate text-[11px] font-bold uppercase tracking-wider text-slate-400">Template Aktif</p>
            </div>
        </div>
        <div class="flex items-center gap-3.5 rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-200/80">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xl font-black text-slate-900 tabular-nums">{{ number_format($stats['total_dipakai'], 0, ',', '.') }}</p>
                <p class="truncate text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Pengiriman</p>
            </div>
        </div>
    </div>

    {{-- ===== Tab Navigasi Interaktif ===== --}}
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex gap-6" aria-label="Tabs">
            <button @click="activeTab = 'template'"
                    :class="activeTab === 'template' 
                        ? 'border-sky-600 text-sky-600 font-bold' 
                        : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 font-medium'"
                    class="group inline-flex items-center gap-2 border-b-2 py-3 px-1 text-sm transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.269Z" /></svg>
                <span>Daftar Template Pesan</span>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600 font-bold tabular-nums">{{ $stats['total_template'] }}</span>
            </button>

            <button @click="activeTab = 'kategori'"
                    :class="activeTab === 'kategori' 
                        ? 'border-sky-600 text-sky-600 font-bold' 
                        : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 font-medium'"
                    class="group inline-flex items-center gap-2 border-b-2 py-3 px-1 text-sm transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /></svg>
                <span>Kelola Kategori</span>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600 font-bold tabular-nums">{{ $stats['total_kategori'] }}</span>
            </button>
        </nav>
    </div>

    {{-- ======================================================== --}}
    {{-- TAB 1: TEMPLATE PESAN --}}
    {{-- ======================================================== --}}
    <div x-show="activeTab === 'template'" class="space-y-6">

        {{-- Box Variabel Tersedia --}}
        <div class="rounded-2xl bg-gradient-to-br from-white to-slate-50/60 p-5 shadow-xs ring-1 ring-slate-200">
            <div class="flex items-start gap-3.5">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600 ring-1 ring-sky-100">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" /></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <p class="text-sm font-bold text-slate-900">Variabel Dinamis PNPP</p>
                            <p class="text-xs text-slate-500">Klik salah satu token di bawah untuk langsung menyalin variabel ke pesan pengingat Anda.</p>
                        </div>
                        <span class="inline-flex items-center text-[11px] font-semibold text-slate-400">Nilai otomatis terisi saat pesan dikirim</span>
                    </div>
                    <div class="mt-3.5 flex flex-wrap gap-2">
                        @foreach ($variables as $v)
                            <button type="button"
                                    @click="copyText('{{ $v['var'] }}')"
                                    title="Klik untuk salin: {{ $v['desc'] }} (Contoh: {{ $v['contoh'] }})"
                                    class="group inline-flex items-center gap-2 rounded-xl bg-white px-3 py-1.5 text-xs shadow-xs ring-1 ring-slate-200 transition hover:border-sky-300 hover:bg-sky-50/50 hover:ring-sky-300">
                                <code class="font-mono text-[11px] font-extrabold text-sky-700 group-hover:text-sky-800">{{ $v['var'] }}</code>
                                <span class="text-[11px] font-medium text-slate-500 group-hover:text-slate-700">{{ $v['desc'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Server-Side Filter & Search Bar (Dengan Event JS Debounce) --}}
        <form id="filterForm" method="GET" action="{{ route('admin.setting.index') }}" class="rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-200">
            <input type="hidden" name="tab" value="template">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                {{-- Search Box dengan JS event input debounce --}}
                <div class="md:col-span-5 relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    </div>
                    <input type="text" 
                           id="searchInput"
                           name="q"
                           value="{{ $filters['q'] }}"
                           placeholder="Cari judul, kata kunci isi pesan, atau kode..."
                           class="block w-full rounded-xl border-0 py-2.5 pl-10 pr-9 text-xs text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500">
                    @if($filters['q'])
                        <button type="button" onclick="document.getElementById('searchInput').value = ''; document.getElementById('filterForm').submit();" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>
                    @endif
                </div>

                {{-- Filter Kategori --}}
                <div class="md:col-span-3">
                    <select name="category_id" onchange="document.getElementById('filterForm').submit();"
                            class="block w-full rounded-xl border-0 py-2.5 px-3 text-xs text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 cursor-pointer">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (string)$filters['category_id'] === (string)$cat->id ? 'selected' : '' }}>
                                {{ $cat->nama }} ({{ $cat->templates_count }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Channel --}}
                <div class="md:col-span-2">
                    <select name="channel" onchange="document.getElementById('filterForm').submit();"
                            class="block w-full rounded-xl border-0 py-2.5 px-3 text-xs text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 cursor-pointer">
                        <option value="">Semua Saluran</option>
                        <option value="WhatsApp" {{ $filters['channel'] === 'WhatsApp' ? 'selected' : '' }}>WhatsApp</option>
                        <option value="SMS" {{ $filters['channel'] === 'SMS' ? 'selected' : '' }}>SMS</option>
                        <option value="Email" {{ $filters['channel'] === 'Email' ? 'selected' : '' }}>Email</option>
                    </select>
                </div>

                {{-- Filter Status --}}
                <div class="md:col-span-2 flex gap-2">
                    <select name="status" onchange="document.getElementById('filterForm').submit();"
                            class="block w-full rounded-xl border-0 py-2.5 px-3 text-xs text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 cursor-pointer">
                        <option value="">Semua Status</option>
                        <option value="1" {{ $filters['status'] === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ $filters['status'] === '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>

                    @if($filters['q'] || $filters['category_id'] || $filters['channel'] || $filters['status'] !== '')
                        <a href="{{ route('admin.setting.index') }}" title="Reset filter"
                           class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-800">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                        </a>
                    @endif
                </div>
            </div>
        </form>

        {{-- Tabel Template Pesan --}}
        @if ($templates->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                </div>
                <h3 class="mt-4 text-base font-bold text-slate-800">Tidak ada template ditemukan</h3>
                <p class="mt-1 text-xs text-slate-400">Coba sesuaikan kata kunci pencarian atau filter yang dipilih.</p>
                <div class="mt-4 flex gap-2">
                    <a href="{{ route('admin.setting.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Reset Filter</a>
                    <button @click="openCreateTemplate()" class="rounded-xl bg-sky-600 px-4 py-2 text-xs font-bold text-white hover:bg-sky-700">Tambah Template Baru</button>
                </div>
            </div>
        @else
            <div class="rounded-2xl bg-white shadow-xs ring-1 ring-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-100 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4 w-10 text-center">No</th>
                                <th class="py-3 px-4 w-24">Status</th>
                                <th class="py-3 px-4 w-40">Kategori</th>
                                <th class="py-3 px-4 w-56">Judul Template</th>
                                <th class="py-3 px-4 w-24">Channel</th>
                                <th class="py-3 px-4">Isi Pesan</th>
                                <th class="py-3 px-4 w-32 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @foreach ($templates as $t)
                                @php
                                    $cat = $t->category;
                                    $catColor = $cat ? ($colorMap[$cat->warna] ?? $colorMap['sky']) : $colorMap['sky'];
                                @endphp
                                <tr class="{{ $t->is_active ? 'hover:bg-slate-50/60' : 'bg-slate-50/40 hover:bg-slate-50/70' }}">
                                    <td class="py-3 px-4 text-center font-mono text-slate-400">{{ ($templates->currentPage() - 1) * $templates->perPage() + $loop->iteration }}</td>
                                    <td class="py-3 px-4">
                                        @if ($t->is_active)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-600/20">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500 ring-1 ring-slate-300/60">
                                                Nonaktif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-[10px] font-bold ring-1 ring-inset {{ $catColor['badge'] }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $catColor['dot'] }}"></span>
                                            {{ $cat?->nama ?? 'Tanpa Kategori' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <p class="font-bold text-slate-900" title="{{ $t->judul }}">{{ $t->judul }}</p>
                                        <p class="mt-0.5 font-mono text-[10px] font-semibold text-slate-400">{{ $t->kode }}</p>
                                        @if($t->deskripsi)
                                            <p class="mt-0.5 max-w-[13rem] truncate text-[11px] text-slate-400" title="{{ $t->deskripsi }}">{{ $t->deskripsi }}</p>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 ring-inset {{ $channelStyle[$t->channel] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                            {{ $t->channel }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <p class="line-clamp-2 max-w-md leading-relaxed text-slate-700">
                                            {!! $highlight($t->konten) !!}
                                        </p>
                                        <p class="mt-1.5 flex items-center gap-1.5 text-[10px] text-slate-400">
                                            <span>Dipakai <strong class="font-bold text-slate-500 tabular-nums">{{ $t->dipakai_count }}x</strong></span>
                                            <span>&middot;</span>
                                            <span>{{ $t->updated_at?->diffForHumans() }}</span>
                                        </p>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center justify-center gap-0.5">
                                            {{-- Tombol Live Smartphone WhatsApp Preview --}}
                                            <button type="button"
                                                    @click="openLivePreview(@js($t))"
                                                    title="Pratinjau Live WhatsApp"
                                                    class="rounded-lg p-1.5 text-emerald-600 transition hover:bg-emerald-50">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                            </button>

                                            {{-- Salin Pesan --}}
                                            <button type="button"
                                                    @click="copyText(@js($t->konten))"
                                                    title="Salin Teks Pesan"
                                                    class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" /></svg>
                                            </button>

                                            {{-- Duplicate --}}
                                            <form method="POST" action="{{ route('admin.setting.template.duplicate', $t) }}">
                                                @csrf
                                                <button type="submit" title="Duplikasi Template" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 8.25V6a2.25 2.25 0 0 0-2.25-2.25H6A2.25 2.25 0 0 0 3.75 6v8.25A2.25 2.25 0 0 0 6 16.5h2.25m8.25-8.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-7.5A2.25 2.25 0 0 1 8.25 18v-1.5m8.25-8.25h-6a2.25 2.25 0 0 0-2.25 2.25v6" /></svg>
                                                </button>
                                            </form>

                                            {{-- Edit --}}
                                            <button type="button"
                                                    @click="openEditTemplate(@js($t))"
                                                    title="Edit Template"
                                                    class="rounded-lg p-1.5 text-slate-400 transition hover:bg-sky-50 hover:text-sky-600">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                            </button>

                                            {{-- Delete --}}
                                            <button type="button"
                                                    @click="confirmDelete('{{ route('admin.setting.template.destroy', $t) }}', '{{ $t->judul }}', 'Template Pesan')"
                                                    title="Hapus Template"
                                                    class="rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination Links Server-Side --}}
            <div class="mt-6">
                {{ $templates->links() }}
            </div>
        @endif
    </div>

    {{-- ======================================================== --}}
    {{-- TAB 2: KATEGORI TEMPLATE --}}
    {{-- ======================================================== --}}
    <div x-show="activeTab === 'kategori'" class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Daftar Kategori Pesan</h3>
                <p class="text-xs text-slate-500">Kelola klasifikasi template pesan untuk memudahkan integrasi broadcast modul terkait.</p>
            </div>
            <button @click="openCreateCategory()"
                    class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-sky-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Tambah Kategori
            </button>
        </div>

        <div class="rounded-2xl bg-white shadow-xs ring-1 ring-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4 w-10 text-center">No</th>
                            <th class="py-3 px-4">Kategori</th>
                            <th class="py-3 px-4 w-28">Warna</th>
                            <th class="py-3 px-4">Deskripsi</th>
                            <th class="py-3 px-4 w-36 text-center">Jumlah Template</th>
                            <th class="py-3 px-4 w-20 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @foreach ($categories as $c)
                            @php
                                $color = $colorMap[$c->warna] ?? $colorMap['sky'];
                            @endphp
                            <tr class="hover:bg-slate-50/60">
                                <td class="py-3 px-4 text-center font-mono text-slate-400">{{ $loop->iteration }}</td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg {{ $color['bg'] }} {{ $color['text'] }} ring-1 {{ $color['ring'] }}">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /></svg>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900">{{ $c->nama }}</p>
                                            <p class="font-mono text-[10px] text-slate-400">{{ $c->slug }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="inline-block h-2 w-2 rounded-full {{ $color['dot'] }}"></span>
                                        <span class="text-[11px] font-semibold capitalize text-slate-500">{{ $c->warna }}</span>
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <p class="max-w-sm truncate text-slate-500" title="{{ $c->deskripsi }}">{{ $c->deskripsi ?: 'Tidak ada deskripsi' }}</p>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-700 tabular-nums">
                                        {{ $c->templates_count }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center justify-center gap-0.5">
                                        <button type="button"
                                                @click="openEditCategory(@js($c))"
                                                title="Edit Kategori"
                                                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-sky-50 hover:text-sky-600">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                        </button>
                                        <button type="button"
                                                @click="confirmDelete('{{ route('admin.setting.kategori.destroy', $c) }}', '{{ $c->nama }}', 'Kategori Template')"
                                                title="Hapus Kategori"
                                                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
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
                <div class="flex items-center justify-between bg-slate-50 px-6 py-2.5 border-b border-slate-100">
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
                        <div class="min-h-[220px] py-4 px-2 flex flex-col justify-end space-y-3">
                            <div class="self-center rounded-lg bg-amber-100/90 px-3 py-1 text-[10px] text-amber-800 text-center font-medium shadow-xs">
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
    {{-- MODAL TAMBAH / EDIT TEMPLATE PESAN --}}
    {{-- ======================================================== --}}
    <div x-cloak x-show="templateModal.open" 
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <div x-show="templateModal.open" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="templateModal.open = false"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>

            <div x-show="templateModal.open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative w-full max-w-2xl transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all">
                
                <form :action="templateModal.isEdit ? '{{ url('admin/setting/template') }}/' + templateModal.id : '{{ route('admin.setting.template.store') }}'" method="POST">
                    @csrf
                    <template x-if="templateModal.isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900" x-text="templateModal.isEdit ? 'Edit Template Pesan' : 'Tambah Template Pesan Baru'"></h3>
                            <p class="text-xs text-slate-500">Isi pesan dan gunakan token variabel dinamis sesuai kebutuhan pengingat.</p>
                        </div>
                        <button type="button" @click="templateModal.open = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="space-y-4 p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Judul Template <span class="text-rose-500">*</span></label>
                                <input type="text" name="judul" x-model="templateModal.judul" required placeholder="Contoh: Pengingat Kontrol H-1"
                                       class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-xs text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Kategori Template</label>
                                <select name="template_category_id" x-model="templateModal.category_id"
                                        class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-xs text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 cursor-pointer">
                                    <option value="">— Tanpa Kategori —</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Saluran Pengiriman</label>
                                <select name="channel" x-model="templateModal.channel"
                                        class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-xs text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 cursor-pointer">
                                    <option value="WhatsApp">WhatsApp (Rekomendasi)</option>
                                    <option value="SMS">SMS Gateway</option>
                                    <option value="Email">Email Notifikasi</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Deskripsi Singkat</label>
                                <input type="text" name="deskripsi" x-model="templateModal.deskripsi" placeholder="Untuk keperluan pengingat kontrol rutin..."
                                       class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-xs text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500">
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500">Isi Konten Pesan <span class="text-rose-500">*</span></label>
                                <span class="text-[11px] text-slate-400 font-mono" x-text="(templateModal.konten ? templateModal.konten.length : 0) + ' karakter'"></span>
                            </div>
                            
                            {{-- Variable Quick Inserters --}}
                            <div class="mb-2 flex flex-wrap gap-1.5 rounded-xl bg-slate-50 p-2.5 ring-1 ring-slate-200">
                                <span class="text-[11px] font-bold text-slate-400 self-center mr-1">Sisipkan Token:</span>
                                @foreach ($variables as $v)
                                    <button type="button"
                                            @click="insertVariable('{{ $v['var'] }}')"
                                            class="rounded-lg bg-white px-2 py-1 text-[10px] font-mono font-bold text-sky-700 shadow-2xs ring-1 ring-slate-200 hover:bg-sky-50 hover:ring-sky-300">
                                        {{ $v['var'] }}
                                    </button>
                                @endforeach
                            </div>

                            <textarea id="templateTextarea" name="konten" x-model="templateModal.konten" rows="4" required
                                      placeholder="Ketik isi pesan di sini. Gunakan tombol token di atas untuk menyisipkan variabel otomatis..."
                                      class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-xs text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500"></textarea>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <input type="checkbox" id="modal_is_active" name="is_active" value="1" x-model="templateModal.is_active"
                                   class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500 cursor-pointer">
                            <label for="modal_is_active" class="text-xs font-medium text-slate-700 cursor-pointer">Aktifkan template ini sekarang agar dapat dipilih pada pengiriman pengingat.</label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4">
                        <button type="button" @click="templateModal.open = false"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit"
                                class="rounded-xl bg-sky-600 px-5 py-2 text-xs font-bold text-white hover:bg-sky-700">
                            Simpan Template
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- MODAL TAMBAH / EDIT KATEGORI TEMPLATE --}}
    {{-- ======================================================== --}}
    <div x-cloak x-show="categoryModal.open" 
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <div x-show="categoryModal.open" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="categoryModal.open = false"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>

            <div x-show="categoryModal.open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative w-full max-w-md transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all">
                
                <form :action="categoryModal.isEdit ? '{{ url('admin/setting/kategori') }}/' + categoryModal.id : '{{ route('admin.setting.kategori.store') }}'" method="POST">
                    @csrf
                    <template x-if="categoryModal.isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900" x-text="categoryModal.isEdit ? 'Edit Kategori' : 'Tambah Kategori Baru'"></h3>
                            <p class="text-xs text-slate-500">Tentukan nama kategori dan warna aksen untuk pengelompokan.</p>
                        </div>
                        <button type="button" @click="categoryModal.open = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="space-y-4 p-6">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Nama Kategori <span class="text-rose-500">*</span></label>
                            <input type="text" name="nama" x-model="categoryModal.nama" required placeholder="Contoh: Jadwal & Kontrol"
                                   class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-xs text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Pilih Warna Badge Aksen</label>
                            <div class="grid grid-cols-6 gap-2">
                                @foreach (['emerald', 'sky', 'amber', 'rose', 'purple', 'indigo'] as $w)
                                    @php $c = $colorMap[$w]; @endphp
                                    <label class="flex flex-col items-center gap-1 cursor-pointer">
                                        <input type="radio" name="warna" value="{{ $w }}" x-model="categoryModal.warna" class="sr-only">
                                        <span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $c['bg'] }} ring-2 transition"
                                              :class="categoryModal.warna === '{{ $w }}' ? 'ring-slate-900 scale-110 shadow-sm' : 'ring-transparent opacity-70 hover:opacity-100'">
                                            <span class="h-3 w-3 rounded-full {{ $c['dot'] }}"></span>
                                        </span>
                                        <span class="text-[9px] font-bold capitalize text-slate-500">{{ $w }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Deskripsi</label>
                            <textarea name="deskripsi" x-model="categoryModal.deskripsi" rows="2" placeholder="Jelaskan tujuan atau konteks kategori ini..."
                                      class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-xs text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4">
                        <button type="button" @click="categoryModal.open = false"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit"
                                class="rounded-xl bg-sky-600 px-5 py-2 text-xs font-bold text-white hover:bg-sky-700">
                            Simpan Kategori
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- MODAL KONFIRMASI HAPUS --}}
    {{-- ======================================================== --}}
    <div x-cloak x-show="deleteModal.open" 
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <div x-show="deleteModal.open" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="deleteModal.open = false"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>

            <div x-show="deleteModal.open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative w-full max-w-sm transform overflow-hidden rounded-3xl bg-white p-6 text-center shadow-2xl transition-all">
                
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 ring-1 ring-rose-100">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                </div>

                <h3 class="mt-4 text-base font-bold text-slate-900">Konfirmasi Hapus</h3>
                <p class="mt-1 text-xs text-slate-500">
                    Apakah Anda yakin ingin menghapus <span class="font-bold text-slate-800" x-text="deleteModal.itemType"></span>:
                    <br><strong class="text-rose-600 text-sm" x-text="deleteModal.itemName"></strong>?
                </p>
                <p class="mt-2 text-[11px] text-slate-400">Tindakan ini permanen dan tidak dapat dibatalkan.</p>

                <form :action="deleteModal.actionUrl" method="POST" class="mt-6 flex items-center justify-center gap-2">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="deleteModal.open = false"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-rose-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-rose-700">
                        Ya, Hapus Data
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

{{-- JS Event Debounce untuk Server-Side Search --}}
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
