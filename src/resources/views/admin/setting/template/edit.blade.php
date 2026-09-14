@extends('layouts.app')

@section('title', 'Edit Template Pesan')
@section('page-title', 'Edit Template Pesan')

@section('content')
<div x-data="editTemplateForm()" class="space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
                <a href="{{ route('admin.setting.index') }}" class="rounded transition hover:text-sky-600">Template Pesan</a>
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                <span class="max-w-[18rem] truncate font-semibold text-slate-600">{{ $template->judul }}</span>
            </nav>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Edit Template Pesan</h2>
            <p class="mt-0.5 text-sm text-slate-500">Perbarui isi pesan, kategori, dan status template.</p>
        </div>
        <div class="inline-flex w-fit items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-xs ring-1 ring-slate-200">
            <span class="font-mono text-[11px] font-bold text-sky-700">{{ $template->kode }}</span>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.setting.template.update', $template) }}" @submit="saving = true" x-cloak>
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- ===== Kolom Form ===== --}}
            <div class="space-y-6 lg:col-span-2">
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="border-b border-slate-100 p-6">
                        <h3 class="text-base font-bold text-slate-900">Informasi Template</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Perubahan hanya berlaku lokal dan tersimpan di database.</p>
                    </div>

                    <div class="space-y-5 p-6">
                        <div>
                            <label for="judul" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Judul Template <span class="text-rose-500">*</span></label>
                            <input type="text" name="judul" id="judul" value="{{ old('judul', $template->judul) }}" required placeholder="cth. Pengingat Kontrol Rawat Jalan"
                                   class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('judul') ring-rose-300 focus:ring-rose-500 @enderror">
                            @error('judul')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label for="template_category_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Kategori Pesan</label>
                                <select name="template_category_id" id="template_category_id"
                                        class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition cursor-pointer @error('template_category_id') ring-rose-300 focus:ring-rose-500 @enderror">
                                    <option value="">— Tanpa Kategori —</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ (string) old('template_category_id', $template->template_category_id) === (string) $cat->id ? 'selected' : '' }}>
                                            {{ $cat->nama }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('template_category_id')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="channel" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Channel <span class="text-rose-500">*</span></label>
                                <select name="channel" id="channel"
                                        class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition cursor-pointer @error('channel') ring-rose-300 focus:ring-rose-500 @enderror">
                                    @foreach (['WhatsApp', 'SMS', 'Email'] as $ch)
                                        <option value="{{ $ch }}" {{ old('channel', $template->channel) === $ch ? 'selected' : '' }}>{{ $ch }}</option>
                                    @endforeach
                                </select>
                                @error('channel')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <div class="mb-1.5 flex items-center justify-between">
                                <label for="konten" class="block text-xs font-bold uppercase tracking-wide text-slate-500">Isi Pesan <span class="text-rose-500">*</span></label>
                                <span class="text-[11px] text-slate-400">Klik token pada kolom kanan / di bawah untuk menyisipkan.</span>
                            </div>
                            <textarea x-ref="kontenInput" x-model="konten" name="konten" id="konten" rows="9" required
                                      placeholder="Tulis isi pesan template di sini..."
                                      class="block w-full rounded-xl border-0 py-3 px-3.5 font-mono text-sm leading-relaxed text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('konten') ring-rose-300 focus:ring-rose-500 @enderror"></textarea>
                            @error('konten')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror

                            <div class="mt-2.5 flex flex-wrap gap-1.5">
                                @foreach ($variables as $v)
                                    <button type="button" @click="insertToken('{{ $v['var'] }}')"
                                            title="{{ $v['desc'] }} — contoh: {{ $v['contoh'] }}"
                                            class="rounded-lg bg-sky-50 px-2.5 py-1.5 font-mono text-[11px] font-bold text-sky-700 ring-1 ring-inset ring-sky-200 transition hover:bg-sky-100 hover:ring-sky-300">
                                        {{ $v['var'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label for="deskripsi" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Deskripsi</label>
                            <textarea name="deskripsi" id="deskripsi" rows="2" placeholder="Jelaskan tujuan atau konteks template ini..."
                                      class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('deskripsi') ring-rose-300 focus:ring-rose-500 @enderror">{{ old('deskripsi', $template->deskripsi) }}</textarea>
                            @error('deskripsi')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <label class="flex cursor-pointer items-center gap-2.5 text-sm font-semibold text-slate-700">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active) ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            Template aktif — siap digunakan untuk broadcast pesan
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 rounded-b-2xl border-t border-slate-100 bg-slate-50/70 p-4">
                        <a href="{{ route('admin.setting.index') }}"
                           class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Batal</a>
                        <button type="submit" :disabled="saving"
                                class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                            <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="saving ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ===== Kolom Pratinjau (sticky) ===== --}}
            <div class="lg:col-span-1">
                <div class="lg:sticky lg:top-6 space-y-4">
                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
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
                            <p class="text-[11px] text-slate-400">
                                <template x-if="kontenToken.length === 0">Tidak ada variabel dinamis di isi pesan.</template>
                                <template x-if="kontenToken.length > 0"><span>Variabel terdeteksi: <strong x-text="kontenToken.join(', ')"></strong>.</span></template>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('editTemplateForm', () => ({
            saving: false,
            konten: @js(old('konten', $template->konten)),
            variables: @js($variables),

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
        }));
    });
</script>
@endsection