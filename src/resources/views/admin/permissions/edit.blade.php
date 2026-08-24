@extends('layouts.app')

@section('title', 'Edit Permission — ' . $permission->name)
@section('page-title', 'Edit Permission')

@section('content')
<div x-data="permissionForm()" class="mx-auto max-w-5xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.permissions.index') }}" class="rounded transition hover:text-sky-600">Manajemen Permission</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-mono font-semibold text-slate-600">{{ $permission->name }}</span>
        </nav>
        <h2 class="text-xl font-bold tracking-tight text-slate-900">Edit Permission</h2>
        <p class="mt-0.5 text-sm text-slate-500">Perubahan nama berlaku ke semua role yang memakainya.</p>
    </div>

    <form action="{{ route('admin.permissions.update', $permission) }}" method="POST" @submit="saving = true" x-cloak>
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- ===== Form ===== --}}
            <div class="lg:col-span-2">
                <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="border-b border-slate-100 p-6">
                        <h3 class="text-base font-bold text-slate-900">Informasi Permission</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Ubah nama permission bila diperlukan.</p>
                    </div>

                    <div class="space-y-5 p-6">
                        {{-- Warning role terdampak --}}
                        @if ($permission->roles->count() > 0)
                            <div class="flex items-start gap-3 rounded-xl bg-amber-50 p-4 ring-1 ring-amber-200/60">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                </svg>
                                <div class="min-w-0 text-xs leading-relaxed text-amber-800">
                                    <p><strong>Permission ini dipakai oleh {{ $permission->roles->count() }} role:</strong></p>
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @foreach ($permission->roles as $role)
                                            <span class="inline-flex items-center rounded-md bg-white px-2 py-0.5 text-[11px] font-bold capitalize text-amber-700 shadow-xs ring-1 ring-amber-200">{{ $role->name }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200/70">
                                <svg class="h-4 w-4 flex-shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                </svg>
                                <p class="text-xs italic text-slate-500">Permission ini belum di-assign ke role mana pun, jadi aman untuk diedit bebas.</p>
                            </div>
                        @endif

                        <div>
                            <label for="name" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Nama Permission <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $permission->name) }}" x-model="name" required placeholder="cth. view pasien"
                                   class="block w-full rounded-xl border-0 py-2.5 px-3.5 font-mono text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('name') ring-rose-300 focus:ring-rose-500 @enderror">
                            @error('name')
                                <p class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-rose-600">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9 .75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                                    {{ $message }}
                                </p>
                            @enderror

                            {{-- Live preview --}}
                            <div x-show="isDirty" x-cloak class="mt-4 flex flex-wrap items-center gap-2.5 rounded-xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Berubah dari:</span>
                                <span class="inline-flex items-center rounded-md bg-white px-2 py-1 font-mono text-xs text-slate-400 line-through ring-1 ring-slate-200">{{ $permission->name }}</span>
                                <svg class="h-3 w-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                                <span class="inline-flex items-center gap-1.5 rounded-md bg-violet-50 px-2 py-1 font-mono text-xs font-bold text-violet-700 ring-1 ring-violet-200">
                                    <span x-text="name"></span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 rounded-b-2xl border-t border-slate-100 bg-slate-50/70 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="flex items-center gap-1.5 text-xs font-bold text-amber-600" x-show="isDirty" x-cloak>
                            <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-amber-500"></span></span>
                            Ada perubahan belum disimpan
                        </p>
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.permissions.index') }}"
                               class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Batal</a>
                            <button type="submit" :disabled="!isDirty || saving"
                                    class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-sky-600">
                                <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span x-text="saving ? 'Menyimpan...' : 'Update Permission'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== Panduan ===== --}}
            <div class="lg:col-span-1">
                <div class="sticky top-20 space-y-4">
                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                        <div class="border-b border-slate-100 px-5 py-3">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Detail</p>
                        </div>
                        <dl class="space-y-3 p-5 text-xs">
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-400">ID</dt>
                                <dd class="font-mono font-semibold text-slate-700">#{{ str_pad($permission->id, 4, '0', STR_PAD_LEFT) }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-400">Guard</dt>
                                <dd><code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono font-semibold text-slate-700">{{ $permission->guard_name }}</code></dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-400">Dibuat</dt>
                                <dd class="font-semibold text-slate-700">{{ $permission->created_at->translatedFormat('d M Y') }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-400">Dipakai Role</dt>
                                <dd class="font-semibold tabular-nums text-slate-700">{{ $permission->roles->count() }} role</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-xl bg-white p-4 text-xs leading-relaxed text-slate-500 shadow-xs ring-1 ring-slate-200">
                        <p class="mb-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400">Catatan</p>
                        Mengganti nama permission tidak merusak relasi — Spatie Permission akan tetap mengenali izin ini di role yang memakainya.
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('permissionForm', () => ({
            name: @js(old('name', $permission->name)),
            original: @js(old('name', $permission->name)),
            saving: false,

            get isDirty() {
                return this.name !== this.original;
            },

            init() {
                window.addEventListener('beforeunload', (e) => {
                    if (this.isDirty && !this.saving) {
                        e.preventDefault();
                        e.returnValue = '';
                    }
                });
            }
        }));
    });
</script>
@endsection
