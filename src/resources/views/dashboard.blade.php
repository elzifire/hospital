@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Welcome Card --}}
    <div class="rounded-2xl bg-gradient-to-r from-sky-600 to-indigo-600 p-6 text-white shadow-lg">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold">Selamat Datang, {{ $user->name }}! 👋</h2>
                <p class="mt-1 text-sky-100">
                    Anda login sebagai
                    <span class="inline-flex items-center rounded-full bg-white/20 px-3 py-0.5 text-sm font-medium backdrop-blur-sm">
                        {{ ucfirst($role) }}
                    </span>
                </p>
            </div>
            <div class="text-sm text-sky-100">
                <p>{{ now()->translatedFormat('l, d F Y') }}</p>
                <p>{{ now()->format('H:i') }} WIB</p>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Card 1 --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-sky-50 text-sky-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Users</p>
                    <p class="text-2xl font-bold text-gray-900">{{ \App\Models\User::count() }}</p>
                </div>
            </div>
        </div>

        {{-- Card 2 --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Roles</p>
                    <p class="text-2xl font-bold text-gray-900">{{ \Spatie\Permission\Models\Role::count() }}</p>
                </div>
            </div>
        </div>

        {{-- Card 3 --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Permissions</p>
                    <p class="text-2xl font-bold text-gray-900">{{ \Spatie\Permission\Models\Permission::count() }}</p>
                </div>
            </div>
        </div>

        {{-- Card 4 --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Your Permissions</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $permissions->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Role-based sections --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Your Permissions Table --}}
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
            <div class="border-b border-gray-100 px-5 py-4">
                <h3 class="text-base font-semibold text-gray-900">Permissions Anda</h3>
                <p class="text-sm text-gray-500">Daftar hak akses yang dimiliki</p>
            </div>
            <div class="p-5">
                @if($permissions->isEmpty())
                    <p class="text-sm text-gray-500 italic">Tidak ada permissions.</p>
                @else
                    <div class="flex flex-wrap gap-2">
                        @foreach($permissions as $perm)
                            <span class="inline-flex items-center rounded-full bg-sky-50 px-3 py-1 text-xs font-medium text-sky-700 ring-1 ring-sky-600/20">
                                {{ $perm }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Account Info --}}
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
            <div class="border-b border-gray-100 px-5 py-4">
                <h3 class="text-base font-semibold text-gray-900">Informasi Akun</h3>
                <p class="text-sm text-gray-500">Detail akun Anda</p>
            </div>
            <div class="divide-y divide-gray-100">
                <div class="flex items-center justify-between px-5 py-3">
                    <span class="text-sm text-gray-500">Nama</span>
                    <span class="text-sm font-medium text-gray-900">{{ $user->name }}</span>
                </div>
                <div class="flex items-center justify-between px-5 py-3">
                    <span class="text-sm text-gray-500">Email</span>
                    <span class="text-sm font-medium text-gray-900">{{ $user->email }}</span>
                </div>
                <div class="flex items-center justify-between px-5 py-3">
                    <span class="text-sm text-gray-500">Role</span>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                        @if($role === 'superadmin') bg-red-50 text-red-700 ring-1 ring-red-600/20
                        @elseif($role === 'admin') bg-amber-50 text-amber-700 ring-1 ring-amber-600/20
                        @else bg-green-50 text-green-700 ring-1 ring-green-600/20
                        @endif">
                        {{ ucfirst($role) }}
                    </span>
                </div>
                <div class="flex items-center justify-between px-5 py-3">
                    <span class="text-sm text-gray-500">Bergabung</span>
                    <span class="text-sm font-medium text-gray-900">{{ $user->created_at->format('d M Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Superadmin-only section --}}
    @role('superadmin')
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="border-b border-gray-100 px-5 py-4">
            <h3 class="text-base font-semibold text-gray-900">🔧 Panel Super Admin</h3>
            <p class="text-sm text-gray-500">Fitur khusus Super Admin</p>
        </div>
        <div class="p-5">
            <div class="rounded-lg bg-red-50 border border-red-200 p-4">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-red-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-red-800">Full System Access</p>
                        <p class="mt-1 text-sm text-red-600">Anda memiliki akses penuh ke seluruh sistem. Gunakan dengan bijak.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endrole

    {{-- Admin-only section --}}
    @role('admin')
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="border-b border-gray-100 px-5 py-4">
            <h3 class="text-base font-semibold text-gray-900">📋 Panel Admin</h3>
            <p class="text-sm text-gray-500">Fitur khusus Admin</p>
        </div>
        <div class="p-5">
            <div class="rounded-lg bg-amber-50 border border-amber-200 p-4">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-amber-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-amber-800">Admin Access</p>
                        <p class="mt-1 text-sm text-amber-600">Anda memiliki akses untuk mengelola pengguna dalam sistem.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endrole

    {{-- User-only section --}}
    @role('user')
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="border-b border-gray-100 px-5 py-4">
            <h3 class="text-base font-semibold text-gray-900">📌 Informasi</h3>
            <p class="text-sm text-gray-500">Pemberitahuan untuk Anda</p>
        </div>
        <div class="p-5">
            <div class="rounded-lg bg-sky-50 border border-sky-200 p-4">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-sky-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-sky-800">Selamat Datang</p>
                        <p class="mt-1 text-sm text-sky-600">Anda login sebagai user biasa. Hubungi admin jika memerlukan akses tambahan.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endrole

</div>
@endsection

