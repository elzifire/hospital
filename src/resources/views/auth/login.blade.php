@extends('layouts.guest')

@section('title', 'Login')

@section('content')
<div class="w-full max-w-md">
    {{-- Logo & Title --}}
    <div class="mb-8 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-sky-500/20 backdrop-blur-sm">
            <svg class="h-9 w-9 text-sky-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21" />
            </svg>
        </div>
        <h2 class="mt-4 text-2xl font-bold text-white">Hospital Management</h2>
        <p class="mt-1 text-sm text-slate-400">Silakan login untuk melanjutkan</p>
    </div>

    {{-- Login Card --}}
    <div class="rounded-2xl bg-white/10 p-8 shadow-2xl ring-1 ring-white/20 backdrop-blur-lg">
        {{-- Error messages --}}
        @if ($errors->any())
            <div class="mb-6 rounded-lg bg-red-500/10 border border-red-500/30 p-4">
                @foreach ($errors->all() as $error)
                    <p class="text-sm text-red-300">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-slate-300">Email</label>
                <div class="mt-1.5 relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                    </div>
                    <input id="email" name="email" type="email" autocomplete="email" required
                           value="{{ old('email') }}"
                           class="block w-full rounded-lg border-0 bg-white/5 py-2.5 pl-10 pr-4 text-white placeholder-slate-500 ring-1 ring-white/10 focus:ring-2 focus:ring-sky-500 transition sm:text-sm"
                           placeholder="nama@email.com">
                </div>
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="block text-sm font-medium text-slate-300">Password</label>
                <div class="mt-1.5 relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                    </div>
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                           class="block w-full rounded-lg border-0 bg-white/5 py-2.5 pl-10 pr-4 text-white placeholder-slate-500 ring-1 ring-white/10 focus:ring-2 focus:ring-sky-500 transition sm:text-sm"
                           placeholder="••••••••">
                </div>
            </div>

            {{-- Remember me --}}
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-600 bg-white/5 text-sky-500 focus:ring-sky-500 focus:ring-offset-0">
                    <span class="text-sm text-slate-400">Ingat saya</span>
                </label>
            </div>

            {{-- Submit --}}
            <button type="submit"
                    class="flex w-full justify-center rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-sky-600/30 hover:bg-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 focus:ring-offset-slate-900 transition">
                Login
            </button>
        </form>
    </div>

    {{-- Footer --}}
    <p class="mt-6 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} Hospital Management System. All rights reserved.
    </p>
</div>
@endsection

