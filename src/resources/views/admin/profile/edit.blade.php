@extends('layouts.app')

@section('title', 'Profil Saya')
@section('page-title', 'Profil Saya')

@section('content')
@php
    $currentRole = $user->roles->first()?->name ?? '';
    $roleBadge = match ($currentRole) {
        'superadmin' => ['bg-rose-100 text-rose-700 ring-rose-600/20', 'bg-rose-500'],
        'admin'      => ['bg-amber-100 text-amber-700 ring-amber-600/20', 'bg-amber-500'],
        'user'       => ['bg-emerald-100 text-emerald-700 ring-emerald-600/20', 'bg-emerald-500'],
        default      => ['bg-slate-100 text-slate-600 ring-slate-500/20', 'bg-slate-400'],
    };
@endphp

<div class="mx-auto max-w-6xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <span class="rounded text-slate-500">Akun</span>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">Profil Saya</span>
        </nav>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Pengaturan Akun</h2>
        <p class="mt-1 text-sm text-slate-500">Kelola nama tampilan dan kata sandi untuk akun <strong>{{ $user->email }}</strong>.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ===== Kartu Profil ===== --}}
        <div class="lg:col-span-1">
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="h-20 bg-linear-to-r from-sky-600 via-indigo-600 to-violet-600"></div>
                <div class="-mt-10 flex flex-col items-center px-6 pb-6 text-center">
                    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-linear-to-br from-sky-500 to-indigo-500 text-3xl font-extrabold uppercase text-white shadow-lg ring-4 ring-white">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                    <h3 class="mt-3 text-lg font-bold text-slate-900">{{ $user->name }}</h3>
                    <p class="text-sm text-slate-500">{{ $user->email }}</p>

                    <span class="mt-3 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold capitalize ring-1 ring-inset {{ $roleBadge[0] }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $roleBadge[1] }}"></span>
                        {{ $currentRole !== '' ? $currentRole : 'Tanpa Role' }}
                    </span>

                    <dl class="mt-6 w-full space-y-3 border-t border-slate-100 pt-5 text-left">
                        <div class="flex items-center justify-between text-sm">
                            <dt class="flex items-center gap-2 text-slate-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                                User ID
                            </dt>
                            <dd class="font-mono text-xs font-semibold text-slate-800">#{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}</dd>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <dt class="flex items-center gap-2 text-slate-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                                Email
                            </dt>
                            <dd>
                                @if ($user->email_verified_at)
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                        Terverifikasi
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9 .75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                                        Belum
                                    </span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <dt class="flex items-center gap-2 text-slate-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                                Bergabung
                            </dt>
                            <dd class="font-semibold text-slate-800">{{ $user->created_at->translatedFormat('d M Y') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

        </div>

        <div class="space-y-6 lg:col-span-2">

            {{-- ===== Form Ganti Nama ===== --}}
            <div x-data="profileName()" class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 p-6">
                    <h3 class="text-base font-bold text-slate-900">Informasi Akun</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Perbarui nama tampilan yang digunakan di seluruh sistem.</p>
                </div>

                <form action="{{ route('admin.profile.update') }}" method="POST" @submit="saving = true" class="space-y-5 p-6" x-cloak>
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-semibold text-slate-700">Nama Lengkap</label>
                        <input type="text" id="name" name="name" x-model="name"
                               class="block w-full rounded-xl border-0 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 transition placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-sky-600"
                               placeholder="Masukkan nama lengkap">
                        @error('name')
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-rose-600">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
                        <p class="flex items-center gap-1.5 text-xs font-bold text-amber-600" x-show="isDirty" x-cloak>
                            <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-amber-500"></span></span>
                            Ada perubahan belum disimpan
                        </p>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="resetName()" x-show="isDirty" x-cloak
                                    class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Batal</button>
                            <button type="submit" :disabled="!isDirty || saving"
                                    class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-sky-600">
                                <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span x-text="saving ? 'Menyimpan...' : 'Simpan Nama'"></span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- ===== Form Ganti Password ===== --}}
            <div x-data="profilePassword()" class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 p-6">
                    <h3 class="text-base font-bold text-slate-900">Keamanan</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Ubah kata sandi secara berkala untuk menjaga keamanan akun.</p>
                </div>

                <form action="{{ route('admin.profile.password.update') }}" method="POST" @submit="saving = true" class="space-y-5 p-6" x-cloak>
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="mb-1.5 block text-sm font-semibold text-slate-700">Kata Sandi Saat Ini</label>
                        <div class="relative">
                            <input :type="show ? 'text' : 'password'" id="current_password" name="current_password"
                                   class="block w-full rounded-xl border-0 bg-slate-50 px-4 py-2.5 pr-11 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 transition placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-sky-600"
                                   placeholder="Masukkan kata sandi saat ini" autocomplete="current-password">
                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 transition hover:text-slate-600" title="Tampilkan / sembunyikan">
                                <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                <svg x-show="show" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                            </button>
                        </div>
                        @error('current_password')
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-rose-600">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">Kata Sandi Baru</label>
                        <input type="password" id="password" name="password" x-model="password" @input="evaluate()"
                               class="block w-full rounded-xl border-0 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 transition placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-sky-600"
                               placeholder="Minimal 8 karakter" autocomplete="new-password">
                        @error('password')
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-rose-600">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                                {{ $message }}
                            </p>
                        @enderror

                        {{-- Strength meter --}}
                        <div class="mt-3" x-show="password.length > 0" x-cloak>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-200">
                                <div class="h-full rounded-full transition-all duration-300" :class="strength.color" :style="'width:' + strength.percent + '%'"></div>
                            </div>
                            <p class="mt-1.5 text-xs font-semibold" :class="strength.text">Kekuatan: <span x-text="strength.label"></span></p>
                        </div>

                        {{-- Checklist syarat --}}
                        <ul class="mt-3 grid grid-cols-2 gap-2">
                            <li class="flex items-center gap-1.5 text-xs" :class="checks.length ? 'text-emerald-600' : 'text-slate-400'">
                                <svg x-show="checks.length" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                <svg x-show="!checks.length" x-cloak class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                                Minimal 8 karakter
                            </li>
                            <li class="flex items-center gap-1.5 text-xs" :class="checks.upper ? 'text-emerald-600' : 'text-slate-400'">
                                <svg x-show="checks.upper" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                <svg x-show="!checks.upper" x-cloak class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                                Huruf besar (A-Z)
                            </li>
                            <li class="flex items-center gap-1.5 text-xs" :class="checks.number ? 'text-emerald-600' : 'text-slate-400'">
                                <svg x-show="checks.number" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                <svg x-show="!checks.number" x-cloak class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                                Angka (0-9)
                            </li>
                            <li class="flex items-center gap-1.5 text-xs" :class="checks.match ? 'text-emerald-600' : 'text-slate-400'">
                                <svg x-show="checks.match" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                <svg x-show="!checks.match" x-cloak class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                                Konfirmasi cocok
                            </li>
                        </ul>
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-slate-700">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" x-model="confirm" @input="evaluate()"
                               class="block w-full rounded-xl border-0 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 transition placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-sky-600"
                               placeholder="Ulangi kata sandi baru" autocomplete="new-password">
                    </div>

                    <div class="flex items-center justify-end border-t border-slate-100 pt-5">
                        <button type="submit" :disabled="!canSubmit || saving"
                                class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-sky-600">
                            <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="saving ? 'Memperbarui...' : 'Ubah Password'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </div>
    </div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('profileName', () => ({
            name: @js(old('name', $user->name)),
            original: @js($user->name),
            saving: false,

            get isDirty() {
                return this.name.trim() !== this.original.trim() && this.name.trim() !== '';
            },

            resetName() {
                this.name = this.original;
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

        Alpine.data('profilePassword', () => ({
            password: '',
            confirm: '',
            show: false,
            saving: false,
            checks: { length: false, upper: false, number: false, match: false },

            get strength() {
                let score = 0;
                if (this.checks.length) score++;
                if (this.checks.upper) score++;
                if (this.checks.number) score++;
                if (this.password.length >= 12) score++;

                const map = [
                    { percent: 25,  color: 'bg-rose-500',    text: 'text-rose-600',    label: 'Lemah' },
                    { percent: 50,  color: 'bg-amber-500',   text: 'text-amber-600',   label: 'Sedang' },
                    { percent: 75,  color: 'bg-sky-500',     text: 'text-sky-600',     label: 'Kuat' },
                    { percent: 100, color: 'bg-emerald-500', text: 'text-emerald-600', label: 'Sangat Kuat' },
                ];
                return map[Math.max(0, Math.min(3, score - 1))] ?? map[0];
            },

            get canSubmit() {
                return this.checks.length && this.checks.upper && this.checks.number
                    && this.confirm.length > 0 && this.checks.match;
            },

            evaluate() {
                this.checks.length = this.password.length >= 8;
                this.checks.upper  = /[A-Z]/.test(this.password);
                this.checks.number = /[0-9]/.test(this.password);
                this.checks.match  = this.confirm.length > 0 && this.password === this.confirm;
            },

            init() {
                this.$watch('password', () => this.evaluate());
                this.$watch('confirm', () => this.evaluate());
            }
        }));
    });
</script>
@endsection
