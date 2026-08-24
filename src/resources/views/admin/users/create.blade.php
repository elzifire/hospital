@extends('layouts.app')

@section('title', 'Tambah User')
@section('page-title', 'Tambah User')

@section('content')
<div x-data="userCreate()" class="mx-auto max-w-6xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.users.index') }}" class="rounded transition hover:text-sky-600">Manajemen Pengguna</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">Tambah Baru</span>
        </nav>
        <h2 class="text-xl font-bold tracking-tight text-slate-900">Tambah Pengguna Baru</h2>
        <p class="mt-0.5 text-sm text-slate-500">Buat akun baru lalu tentukan role aksesnya.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ===== Preview Kartu (live mengikuti input) ===== --}}
        <div class="space-y-4">
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="h-16 bg-linear-to-r from-sky-600 via-indigo-600 to-violet-600"></div>
                <div class="-mt-8 flex flex-col items-center px-5 pb-5 text-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-linear-to-br from-sky-500 to-indigo-500 text-2xl font-extrabold uppercase text-white shadow-lg ring-4 ring-white"
                         x-text="initials || '?'">?</div>
                    <h3 class="mt-3 max-w-full truncate text-base font-bold text-slate-900" x-text="name || 'Nama Pengguna'"></h3>
                    <p class="max-w-full truncate text-sm text-slate-500" x-text="email || 'email@hospital.test'"></p>
                    <span class="mt-2.5 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-bold capitalize ring-1 ring-inset"
                          :class="role ? badgeClass(role) : 'bg-slate-100 text-slate-500 ring-slate-300'">
                        <span class="h-1.5 w-1.5 rounded-full" :class="role ? dotClass(role) : 'bg-slate-400'"></span>
                        <span x-text="role || 'Tanpa Role'"></span>
                    </span>
                </div>
            </div>

            {{-- Checklist syarat password --}}
            <div class="rounded-xl bg-white p-4 shadow-xs ring-1 ring-slate-200">
                <p class="mb-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-400">Syarat Password</p>
                <ul class="space-y-1.5 text-xs">
                    <li class="flex items-center gap-2" :class="checks.length ? 'text-emerald-600' : 'text-slate-400'">
                        <span class="flex h-4 w-4 items-center justify-center rounded-full transition-colors" :class="checks.length ? 'bg-emerald-100' : 'bg-slate-100'">
                            <svg x-show="checks.length" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            <span x-show="!checks.length" class="h-1 w-1 rounded-full bg-slate-400"></span>
                        </span>
                        Minimal 8 karakter
                    </li>
                    <li class="flex items-center gap-2" :class="cases.mixed ? 'text-emerald-600' : 'text-slate-400'">
                        <span class="flex h-4 w-4 items-center justify-center rounded-full transition-colors" :class="cases.mixed ? 'bg-emerald-100' : 'bg-slate-100'">
                            <svg x-show="cases.mixed" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            <span x-show="!cases.mixed" class="h-1 w-1 rounded-full bg-slate-400"></span>
                        </span>
                        Ada huruf besar & kecil
                    </li>
                    <li class="flex items-center gap-2" :class="checks.number ? 'text-emerald-600' : 'text-slate-400'">
                        <span class="flex h-4 w-4 items-center justify-center rounded-full transition-colors" :class="checks.number ? 'bg-emerald-100' : 'bg-slate-100'">
                            <svg x-show="checks.number" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            <span x-show="!checks.number" class="h-1 w-1 rounded-full bg-slate-400"></span>
                        </span>
                        Mengandung angka
                    </li>
                    <li class="flex items-center gap-2" :class="match ? 'text-emerald-600' : 'text-slate-400'">
                        <span class="flex h-4 w-4 items-center justify-center rounded-full transition-colors" :class="match ? 'bg-emerald-100' : 'bg-slate-100'">
                            <svg x-show="match" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            <span x-show="!match" class="h-1 w-1 rounded-full bg-slate-400"></span>
                        </span>
                        Konfirmasi password cocok
                    </li>
                </ul>
            </div>
        </div>

        {{-- ===== Form ===== --}}
        <div class="lg:col-span-2">
            <form action="{{ route('admin.users.store') }}" method="POST" @submit="saving = true" x-cloak>
                @csrf

                <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="border-b border-slate-100 p-6">
                        <h3 class="text-base font-bold text-slate-900">Informasi Akun</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Lengkapi data di bawah ini untuk membuat akun baru.</p>
                    </div>

                    <div class="space-y-5 p-6">
                        {{-- Nama & Email --}}
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label for="name" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Nama Lengkap <span class="text-rose-500">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" x-model="name" required placeholder="cth. Budi Santoso"
                                       class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('name') ring-rose-300 focus:ring-rose-500 @enderror">
                                @error('name')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="email" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Email <span class="text-rose-500">*</span></label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" x-model="email" required placeholder="nama@hospital.test"
                                       class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('email') ring-rose-300 focus:ring-rose-500 @enderror">
                                @error('email')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Role --}}
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Role Akses</label>
                            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                                @foreach ($roles as $role)
                                    <label class="relative cursor-pointer rounded-xl border-2 p-3 transition-all duration-200"
                                           :class="role === '{{ $role->name }}'
                                               ? 'border-sky-600 bg-sky-50/60 ring-1 ring-sky-600/20'
                                               : 'border-slate-200 hover:border-sky-300 hover:bg-slate-50'">
                                        <input type="radio" name="role" value="{{ $role->name }}" x-model="role" class="sr-only">
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg transition-colors"
                                                  :class="role === '{{ $role->name }}' ? 'bg-sky-600 text-white' : 'bg-slate-100 text-slate-500'">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                                            </span>
                                            <span class="truncate text-sm font-bold capitalize text-slate-900">{{ $role->name }}</span>
                                        </div>
                                        <p class="mt-1 text-[11px] leading-snug text-slate-400">
                                            {{ $role->permissions_count }} permission · {{ match ($role->name) {
                                                'superadmin' => 'akses penuh',
                                                'admin'      => 'kelola pengguna',
                                                default      => 'akses standar',
                                            } }}
                                        </p>
                                    </label>
                                @endforeach
                            </div>
                            @error('role')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        {{-- Password --}}
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label for="password" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Password <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <input :type="showPw ? 'text' : 'password'" name="password" id="password" x-model="password" required autocomplete="new-password" placeholder="Minimal 8 karakter"
                                           class="block w-full rounded-xl border-0 py-2.5 pl-3.5 pr-10 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('password') ring-rose-300 focus:ring-rose-500 @enderror">
                                    <button type="button" @click="showPw = !showPw" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600" title="Tampilkan/Sembunyikan">
                                        <svg x-show="!showPw" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                        <svg x-show="showPw" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                    </button>
                                </div>

                                {{-- Strength meter --}}
                                <div class="mt-2.5 flex items-center gap-2">
                                    <div class="flex h-1.5 flex-1 gap-1">
                                        <template x-for="i in 4" :key="i">
                                            <div class="h-full flex-1 rounded-full transition-colors duration-300"
                                                 :class="strength >= i ? ['bg-rose-400','bg-orange-400','bg-yellow-400','bg-emerald-500'][strength - 1] : 'bg-slate-100'"></div>
                                        </template>
                                    </div>
                                    <span class="w-14 text-right text-[10px] font-bold uppercase"
                                          :class="['text-slate-300','text-rose-500','text-orange-500','text-yellow-600','text-emerald-600'][strength]"
                                          x-text="['Kosong','Lemah','Cukup','Bagus','Kuat'][strength]"></span>
                                </div>
                                @error('password')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="password_confirmation" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Konfirmasi Password <span class="text-rose-500">*</span></label>
                                <input :type="showPw ? 'text' : 'password'" name="password_confirmation" id="password_confirmation" x-model="confirmation" required autocomplete="new-password" placeholder="Ulangi password"
                                       class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset transition placeholder:text-slate-400 focus:ring-2 focus:ring-inset"
                                       :class="confirmation === '' ? 'ring-slate-200 focus:ring-sky-500' : (match ? 'ring-emerald-300 focus:ring-emerald-500' : 'ring-rose-300 focus:ring-rose-500')">
                                <p class="mt-1.5 h-4 text-xs font-medium"
                                   :class="confirmation !== '' && !match ? 'text-rose-600' : ''"
                                   x-text="confirmation !== '' ? (match ? '✓ Password cocok' : 'Password belum cocok') : ''"></p>
                            </div>
                        </div>

                        {{-- Verified checkbox --}}
                        <label class="flex w-fit cursor-pointer items-center gap-2.5 rounded-xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200 transition hover:bg-slate-100">
                            <input type="checkbox" name="verified" value="1" {{ old('verified') ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <span class="text-sm font-medium text-slate-600">Tandai email sudah terverifikasi</span>
                        </label>
                    </div>

                    {{-- Action bar --}}
                    <div class="flex items-center justify-end gap-3 rounded-b-2xl border-t border-slate-100 bg-slate-50/70 p-4">
                        <a href="{{ route('admin.users.index') }}"
                           class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Batal</a>
                        <button type="submit" :disabled="saving"
                                class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                            <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="saving ? 'Menyimpan...' : 'Simpan User'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('userCreate', () => ({
            name: @js(old('name')),
            email: @js(old('email')),
            role: @js(old('role')),
            password: '',
            confirmation: '',
            showPw: false,
            saving: false,

            get initials() {
                return this.name.trim().split(/\s+/).map(w => w[0]).slice(0, 2).join('').toUpperCase();
            },
            get checks() {
                return {
                    length: this.password.length >= 8,
                    number: /\d/.test(this.password),
                };
            },
            get cases() {
                return { mixed: /[a-z]/.test(this.password) && /[A-Z]/.test(this.password) };
            },
            get match() {
                return this.password !== '' && this.password === this.confirmation;
            },
            get strength() {
                const c = this.checks;
                let score = 0;
                if (c.length) score++;
                if (this.password.length >= 12) score++;
                if (c.number) score++;
                if (this.cases.mixed) score++;
                return Math.min(4, score);
            },
            badgeClass(role) {
                return {
                    superadmin: 'bg-rose-50 text-rose-700 ring-rose-600/20',
                    admin:      'bg-amber-50 text-amber-700 ring-amber-600/20',
                    user:       'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                }[role] ?? 'bg-sky-50 text-sky-700 ring-sky-600/20';
            },
            dotClass(role) {
                return { superadmin: 'bg-rose-500', admin: 'bg-amber-500', user: 'bg-emerald-500' }[role] ?? 'bg-sky-500';
            },
        }));
    });
</script>
@endsection
