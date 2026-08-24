@extends('layouts.app')

@section('title', 'Edit User — ' . $user->name)
@section('page-title', 'Edit User')

@section('content')
@php
    $currentRole = old('role', $user->roles->first()?->name ?? '');
    $roleBadge = match ($currentRole) {
        'superadmin' => ['bg-rose-100 text-rose-700 ring-rose-600/20', 'bg-rose-500'],
        'admin'      => ['bg-amber-100 text-amber-700 ring-amber-600/20', 'bg-amber-500'],
        'user'       => ['bg-emerald-100 text-emerald-700 ring-emerald-600/20', 'bg-emerald-500'],
        default      => ['bg-slate-100 text-slate-600 ring-slate-500/20', 'bg-slate-400'],
    };
@endphp

<div x-data="roleAssign()" class="mx-auto max-w-6xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.users.index') }}" class="rounded transition hover:text-sky-600">Manajemen Pengguna</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">{{ $user->name }}</span>
        </nav>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Atur Role Pengguna</h2>
        <p class="mt-1 text-sm text-slate-500">Pilih role untuk menentukan hak akses <strong>{{ $user->name }}</strong> di dalam sistem.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ===== Kartu Profil ===== --}}
        <div class="lg:col-span-1">
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                {{-- Banner gradien --}}
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
                        <div class="flex items-center justify-between text-sm">
                            <dt class="flex items-center gap-2 text-slate-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                Diupdate
                            </dt>
                            <dd class="font-semibold text-slate-800">{{ $user->updated_at->translatedFormat('d M Y') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Info box --}}
            <div class="mt-4 flex items-start gap-3 rounded-xl bg-amber-50 p-4 text-xs leading-relaxed text-amber-800 ring-1 ring-amber-200/60">
                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <p><strong>Penting:</strong> Perubahan role berlaku langsung setelah disimpan dan akan mengubah menu serta aksi yang bisa diakses user ini.</p>
            </div>
        </div>

        {{-- ===== Form Pilih Role ===== --}}
        <div class="lg:col-span-2">
            <form action="{{ route('admin.users.update', $user->id) }}" method="POST"
                  @submit="saving = true" x-cloak>
                @csrf
                @method('PUT')

                <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="border-b border-slate-100 p-6">
                        <h3 class="text-base font-bold text-slate-900">Pilih Role</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Setiap role membawa set permission yang berbeda.</p>
                    </div>

                    <div class="space-y-4 p-6">
                        @foreach ($roles as $role)
                            @php
                                $desc = match ($role->name) {
                                    'superadmin' => 'Akses tanpa batas ke seluruh sistem.',
                                    'admin'      => 'Mengelola pengguna & melihat dashboard.',
                                    'user'       => 'Akses standar ke fitur umum.',
                                    default      => 'Role kustom dengan permission tertentu.',
                                };
                            @endphp
                            <label class="relative block cursor-pointer rounded-2xl border-2 p-5 transition-all duration-200"
                                   :class="selected === '{{ $role->name }}'
                                       ? 'border-sky-600 bg-sky-50/60 shadow-md ring-1 ring-sky-600/20'
                                       : 'border-slate-200 bg-white hover:border-sky-300 hover:bg-slate-50'">
                                <input type="radio" name="role" value="{{ $role->name }}" x-model="selected" class="sr-only">

                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex items-start gap-4">
                                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl transition-colors"
                                             :class="selected === '{{ $role->name }}' ? 'bg-sky-600 text-white shadow-sm' : 'bg-slate-100 text-slate-500'">
                                            @if ($role->name === 'superadmin')
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                                            @elseif ($role->name === 'admin')
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z" /></svg>
                                            @else
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z" /></svg>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-base font-bold capitalize text-slate-900">{{ $role->name }}</p>
                                            <p class="mt-0.5 text-sm text-slate-500">{{ $desc }}</p>
                                            <div class="mt-3 flex flex-wrap gap-1.5">
                                                @forelse ($role->permissions as $permission)
                                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-medium transition-colors"
                                                          :class="selected === '{{ $role->name }}' ? 'bg-white text-sky-700 ring-1 ring-sky-200' : 'bg-slate-100 text-slate-600'"
                                                    >{{ $permission->name }}</span>
                                                @empty
                                                    <span class="inline-flex items-center rounded-md bg-slate-50 px-2 py-0.5 text-[11px] italic text-slate-400 ring-1 ring-slate-200">Tanpa permission eksplisit</span>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex-shrink-0 pt-0.5 transition-all duration-200"
                                         :class="selected === '{{ $role->name }}' ? 'scale-100 opacity-100' : 'scale-50 opacity-0'">
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-sky-600 text-white shadow-sm">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                        </span>
                                    </div>
                                </div>
                            </label>
                        @endforeach

                        @error('role')
                            <div class="flex items-start gap-2.5 rounded-xl bg-rose-50 p-4 ring-1 ring-rose-200">
                                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                                <div>
                                    <h3 class="text-sm font-bold text-rose-800">Validasi gagal</h3>
                                    <p class="mt-0.5 text-sm text-rose-700">{{ $message }}</p>
                                </div>
                            </div>
                        @enderror
                    </div>

                    {{-- Action Bar --}}
                    <div class="flex flex-col gap-3 rounded-b-2xl border-t border-slate-100 bg-slate-50/70 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-slate-500" x-show="!isDirty" x-cloak>
                            Role saat ini: <span class="font-bold capitalize text-slate-700">{{ $currentRole ?: 'tanpa role' }}</span>
                        </p>
                        <p class="flex items-center gap-1.5 text-xs font-bold text-amber-600" x-show="isDirty" x-cloak>
                            <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-amber-500"></span></span>
                            Ada perubahan belum disimpan
                        </p>
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.users.index') }}"
                               class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Batal</a>
                            <button type="submit" :disabled="!isDirty || saving"
                                    class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-sky-600">
                                <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span x-text="saving ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('roleAssign', () => ({
            selected: @js($currentRole),
            original: @js($currentRole),
            saving: false,

            get isDirty() {
                return this.selected !== this.original;
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
