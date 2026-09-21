@extends('layouts.app')

@section('title', 'Tambah Template Pesan')
@section('page-title', 'Tambah Template Pesan')

@section('content')
@php
    // Token variabel PNPP yang bisa disisipkan ke isi pesan.
    $varsGrouped = collect($variables)->sortBy('var')->groupBy(fn ($v) => strtoupper(trim($v['var'], '{}')[0] ?? '#'));

    $inputClass = 'block w-full rounded-xl border-0 px-3.5 py-2.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition';
    $labelClass = 'mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500';
    $errorClass = 'mt-1.5 text-xs font-medium text-rose-600';
@endphp

<div x-data="createTemplateForm({
    metaSiap: @js($metaSiap),
    bahasaDefault: @js($bahasaDefaultMeta)
})" class="space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                <a href="{{ route('admin.setting.index') }}" class="rounded transition hover:text-sky-600">Template Pesan</a>
                <svg class="h-3.5 w-3.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                <span class="font-semibold text-slate-700">Tambah Template</span>
            </nav>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Tambah Template Pesan</h2>
            <p class="mt-0.5 text-sm text-slate-500">Buat template baru dan (opsional) daftarkan langsung ke Meta WhatsApp untuk disetujui.</p>
        </div>
        <a href="{{ route('admin.setting.index') }}"
           class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-xs transition hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Kembali ke Daftar
        </a>
    </div>

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

    <form method="POST" action="{{ route('admin.setting.template.store') }}" @submit="saving = true" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        @csrf

        {{-- ===== Kolom Form ===== --}}
        <div class="space-y-6 lg:col-span-2">
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 p-6">
                    <h3 class="text-base font-bold text-slate-900">Informasi Template</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Isi mendasar yang menentukan identitas dan isi pesan.</p>
                </div>

                <div class="space-y-5 p-6">
                    <div>
                        <label for="judul" class="{{ $labelClass }}">Judul Template <span class="text-rose-500">*</span>
                            <span class="font-normal normal-case text-slate-400">(akan jadi nama template di Meta, otomatis diubah ke format Meta)</span></label>
                        <input x-model="judul" type="text" name="judul" id="judul" value="{{ old('judul') }}" required placeholder="cth. Pengingat Kontrol Rawat Jalan"
                               class="{{ $inputClass }} @error('judul') ring-rose-300 focus:ring-rose-500 @enderror">
                        <div class="mt-1.5 flex items-center gap-2 text-[11px]" x-show="judul.trim() !== '' && channel === 'WhatsApp'">
                            <svg class="h-3.5 w-3.5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                            <span class="font-semibold text-sky-600">Nama di Meta:</span>
                            <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono font-bold text-slate-600" x-text="metaName"></code>
                        </div>
                        @error('judul')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label for="template_category_id" class="{{ $labelClass }}">Kategori Pesan</label>
                            <select name="template_category_id" id="template_category_id"
                                    class="{{ $inputClass }} cursor-pointer @error('template_category_id') ring-rose-300 focus:ring-rose-500 @enderror">
                                <option value="">— Tanpa Kategori —</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('template_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->nama }}</option>
                                @endforeach
                            </select>
                            @error('template_category_id')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="channel" class="{{ $labelClass }}">Channel <span class="text-rose-500">*</span></label>
                            <div class="grid grid-cols-3 gap-1.5 rounded-xl bg-slate-100 p-1">
                                @foreach (['WhatsApp', 'SMS', 'Email'] as $ch)
                                    <button type="button" @click="channel = '{{ $ch }}'"
                                            :class="channel === '{{ $ch }}' ? 'bg-white text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200' : 'text-slate-500 hover:text-slate-700'"
                                            class="rounded-lg px-2 py-2 text-xs font-bold transition">
                                        {{ $ch }}
                                    </button>
                                @endforeach
                            </div>
                            <input type="hidden" name="channel" :value="channel">
                            @error('channel')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label for="konten" class="{{ $labelClass }}">Isi Pesan <span class="text-rose-500">*</span></label>
                            <span class="text-[11px] text-slate-400">Klik token variabel untuk menyisipkan otomatis.</span>
                        </div>
                        <textarea x-ref="kontenInput" x-model="konten" name="konten" id="konten" rows="9" required
                                  placeholder="Tulis isi pesan template di sini..."
                                  class="{{ $inputClass }} font-mono leading-relaxed @error('konten') ring-rose-300 focus:ring-rose-500 @enderror"></textarea>
                        @error('konten')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror

                        <div class="mt-2.5">
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($variables as $v)
                                    <button type="button" @click="insertToken('{{ $v['var'] }}')"
                                            title="{{ $v['desc'] }} — contoh: {{ $v['contoh'] }}"
                                            class="rounded-lg bg-sky-50 px-2.5 py-1.5 font-mono text-[11px] font-bold text-sky-700 ring-1 ring-inset ring-sky-200 transition hover:bg-sky-100 hover:ring-sky-300">
                                        {{ $v['var'] }}
                                    </button>
                                @endforeach
                            </div>
                            <p class="mt-2 text-[11px] text-slate-400" x-show="kontenToken.length > 0">
                                Variabel terdeteksi (diubah menjadi parameter Meta): <strong x-text="kontenToken.join(', ')"></strong>
                            </p>
                        </div>
                    </div>

                    <div>
                        <label for="deskripsi" class="{{ $labelClass }}">Deskripsi</label>
                        <textarea name="deskripsi" id="deskripsi" rows="2" placeholder="Jelaskan tujuan atau konteks template ini..."
                                  class="{{ $inputClass }} @error('deskripsi') ring-rose-300 focus:ring-rose-500 @enderror">{{ old('deskripsi') }}</textarea>
                        @error('deskripsi')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex cursor-pointer items-center gap-2.5 text-sm font-semibold text-slate-700">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}
                               class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                        Langsung aktif — siap digunakan untuk broadcast
                        <span class="text-[11px] font-normal text-slate-400">(untuk WhatsApp, otomatis nonaktif sampai disetujui Meta)</span>
                    </label>
                </div>

                {{-- ===== Bagian Sinkronisasi Meta ===== --}}
                <div x-show="channel === 'WhatsApp'" x-cloak x-transition class="border-t border-slate-100 bg-sky-50/40">
                    <div class="p-6">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Daftarkan ke Meta WhatsApp</h3>
                                <p class="mt-0.5 text-xs text-slate-500">Template dikirim ke Meta untuk getol persetujuan lalu bisa dipakai kirim official.</p>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" name="daftar_ke_meta" value="1" x-model="daftarMeta" :checked="daftarMeta"
                                       class="peer sr-only">
                                <div class="h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-sky-600 after:absolute after:top-0.5 after:left-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5"></div>
                            </label>
                        </div>

                        {{-- Status konfigurasi Meta --}}
                        <div class="mb-4 flex items-start gap-2.5 rounded-xl px-3.5 py-2.5 text-xs ring-1 ring-inset"
                             :class="metaSiap ? 'bg-emerald-50 text-emerald-800 ring-emerald-200' : 'bg-amber-50 text-amber-800 ring-amber-200'">
                            <template x-if="metaSiap">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            </template>
                            <template x-if="!metaSiap">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 8.25h.008v.008H12v-.008Z" /></svg>
                            </template>
                            <p x-text="metaSiap
                                ? 'Meta WhatsApp sudah dikonfigurasi. Template akan langsung didaftarkan saat disimpan.'
                                : 'Meta belum dikonfigurasi (WA_META_BUSINESS_ACCOUNT_ID / WA_META_TOKEN). Template tetap disimpan lokal, pendaftaran ke Meta dilewati.'">
                            </p>
                        </div>

                        <div x-show="daftarMeta" x-cloak x-transition class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="meta_language" class="{{ $labelClass }}">Bahasa Template (Meta)</label>
                                <select name="meta_language" id="meta_language"
                                        class="{{ $inputClass }} cursor-pointer">
                                    <option value="id" {{ old('meta_language', $bahasaDefaultMeta) === 'id' ? 'selected' : '' }}>Indonesia (id)</option>
                                    <option value="id_ID" {{ old('meta_language', $bahasaDefaultMeta) === 'id_ID' ? 'selected' : '' }}>Indonesia — id_ID</option>
                                    <option value="en_US" {{ old('meta_language', $bahasaDefaultMeta) === 'en_US' ? 'selected' : '' }}>English (US)</option>
                                    <option value="en_GB" {{ old('meta_language', $bahasaDefaultMeta) === 'en_GB' ? 'selected' : '' }}>English (UK)</option>
                                </select>
                            </div>
                            <div>
                                <label for="meta_category" class="{{ $labelClass }}">Kategori Meta</label>
                                <select name="meta_category" id="meta_category"
                                        class="{{ $inputClass }} cursor-pointer">
                                    @foreach (['UTILITY', 'MARKETING', 'AUTHENTICATION'] as $cat)
                                        <option value="{{ $cat }}" {{ old('meta_category', 'UTILITY') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-[11px] leading-relaxed text-slate-400">UTILITY untuk pesan transaksional (pengingat jadwal, konfirmasi) — paling cepat disetujui.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 rounded-b-2xl border-t border-slate-100 bg-slate-50/70 p-4">
                    <a href="{{ route('admin.setting.index') }}"
                       class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Batal</a>
                    <button type="submit" :disabled="saving"
                            class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                        <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span x-text="saving ? 'Menyimpan...' : 'Simpan Template'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ===== Kolom Kanan: Pratinjau + Alur ===== --}}
        <div class="space-y-4 lg:col-span-1">
            {{-- Alur pendaftaran --}}
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h3 class="text-sm font-bold text-slate-900">Alur Pendaftaran ke Meta</h3>
                <ol class="mt-3 space-y-0">
                    @foreach ([
                        'Simpan ke sistem (database)',
                        'Daftarkan ke Meta WhatsApp',
                        'Menunggu persetujuan Meta',
                        'Otomatis aktif & siap kirim',
                    ] as $i => $step)
                        <li class="relative flex items-start gap-3 pb-4 last:pb-0">
                            @if (! $loop->last)
                                <span class="absolute left-[11px] top-[26px] h-full w-px bg-slate-200"></span>
                            @endif
                            <span class="z-10 flex h-[22px] w-[22px] flex-shrink-0 items-center justify-center rounded-full text-[11px] font-black ring-1 ring-inset {{ $i === 0 ? 'bg-sky-600 text-white ring-sky-600' : 'bg-white ring-slate-200 text-slate-500' }}">
                                {{ $i + 1 }}
                            </span>
                            <p class="pt-0.5 text-xs font-semibold {{ $i === 0 ? 'text-slate-800' : 'text-slate-500' }}">{{ $step }}</p>
                        </li>
                    @endforeach
                </ol>
                <div class="mt-3 flex items-start gap-2 rounded-xl bg-sky-50 px-3 py-2.5 text-[11px] leading-relaxed text-sky-800 ring-1 ring-inset ring-sky-100">
                    <svg class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                    <span>Meta tidak mendukung "edit" template jadi. Untuk mengubah isi yang sudah terdaftar, buat template baru atau duplikat lalu daftarkan ulang.</span>
                </div>
            </div>

            {{-- Pratinjau WhatsApp --}}
            <div x-show="channel === 'WhatsApp'" x-cloak x-transition class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Pratinjau WhatsApp</h3>
                    <p class="mt-0.5 text-[11px] text-slate-400">Simulasi nilai variabel mengikuti contoh.</p>
                </div>
                <div class="bg-[#E5DDD5] p-5">
                    <div class="mb-2 rounded-lg bg-amber-100/90 px-3 py-1.5 text-center text-[10px] font-medium text-amber-800 shadow-xs">
                        Pesan ini dikirim secara otomatis via WhatsApp Gateway
                    </div>
                    <div class="max-w-full self-end rounded-2xl rounded-tr-xs bg-[#DCF8C6] p-3.5 text-slate-800 shadow-sm">
                        <p class="whitespace-pre-line text-xs leading-relaxed" x-text="simulated"></p>
                        <div class="mt-1.5 flex items-center justify-end gap-1 text-[9px] text-slate-500">
                            <span>{{ date('H:i') }}</span>
                            <svg class="h-3.5 w-3.5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5m-5 13.5 6-6" /></svg>
                        </div>
                    </div>
                </div>
                <div class="border-t border-slate-100 bg-slate-50/70 px-5 py-3">
                    <button type="button" class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-sky-600 transition hover:text-sky-700"
                            @click="copyPreview">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" /></svg>
                        Salin teks pratinjau
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('createTemplateForm', (opts) => ({
            saving: false,
            judul: @js(old('judul', '')),
            channel: @js(old('channel', 'WhatsApp')),
            konten: @js(old('konten', '')),
            variables: @js($variables),
            metaSiap: opts.metaSiap,
            daftarMeta: opts.metaSiap,
            bahasaMeta: '{{ old('meta_language', $bahasaDefaultMeta) }}',

            get metaName() {
                const nama = this.judul.toLowerCase().replace(/[^a-z0-9]+/gi, '_').replace(/^_+|_+$/g, '');
                return nama ? nama : 'nama_template';
            },

            get simulated() {
                let sim = this.konten;
                this.variables.forEach(v => {
                    const regex = new RegExp(v.var.replace(/([{}])/g, '\\$1'), 'g');
                    sim = sim.replace(regex, v.contoh);
                });
                return sim;
            },

            get kontenToken() {
                const match = this.konten.match(/\{([a-z_]+)\}/gi) || [];
                return [...new Set(match)];
            },

            insertToken(token) {
                const el = this.$refs.kontenInput;
                const start = el.selectionStart ?? this.konten.length;
                const end = el.selectionEnd ?? this.konten.length;
                this.konten = this.konten.slice(0, start) + token + this.konten.slice(end);
                this.$nextTick(() => el.focus());
            },

            copyPreview() {
                const text = this.simulated;
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text);
                } else {
                    const area = document.createElement('textarea');
                    area.value = text;
                    area.style.position = 'fixed';
                    area.style.left = '-999999px';
                    document.body.appendChild(area);
                    area.select();
                    document.execCommand('copy');
                    area.remove();
                }
            },
        }));
    });
</script>
@endsection