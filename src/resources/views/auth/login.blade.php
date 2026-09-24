@extends('layouts.guest')

@section('title', 'Login')

@section('content')
<div class="flex min-h-screen w-full flex-col lg:flex-row">

    {{-- LEFT: Branding / image panel (hidden on mobile) --}}
    <div class="relative hidden w-full items-center justify-center overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-sky-900 lg:flex lg:w-1/2">

        {{-- decorative glow --}}
        <div class="pointer-events-none absolute -left-24 -top-24 h-96 w-96 rounded-full bg-sky-500/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -right-16 h-96 w-96 rounded-full bg-sky-400/10 blur-3xl"></div>

        {{-- subtle dot pattern --}}
        <div class="pointer-events-none absolute inset-0 opacity-[0.05]"
             style="background-image:radial-gradient(circle,#fff 1px,transparent 1px);background-size:24px 24px;"></div>

        <div class="relative z-10 max-w-md px-10 text-center">
            <div class="mx-auto mb-8 flex h-28 w-28 items-center justify-center rounded-3xl bg-white/10 p-4 shadow-2xl ring-1 ring-white/20 backdrop-blur-sm">
                <img src="{{ asset('image/logo-proaktif.jpeg') }}" alt="Logo"
                     class="h-full w-full rounded-xl object-cover">
            </div>

            <h1 class="text-2xl font-bold leading-snug text-white">
                PROAKTIF
            </h1>
            <p class="mt-3 text-sm leading-relaxed text-slate-300">
                RUMAH SAKIT BHAYANGKARA BOGOR
            </p>

            <div class="mx-auto mt-10 h-px w-full bg-white/10"></div>
            <p class="mt-6 text-xs text-slate-400">
                &copy; {{ date('Y') }} RS Bhayangkara Bogor. All rights reserved.
            </p>
        </div>
    </div>

    {{-- RIGHT: Login form panel --}}
    <div class="flex w-full flex-1 items-center justify-center bg-white px-6 py-12 sm:px-10 lg:w-1/2">
        <div class="w-full max-w-sm">

            {{-- Logo shown only on mobile, since the left panel is hidden there --}}
            <div class="mb-8 text-center lg:hidden">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 p-2 ring-1 ring-slate-200">
                    <img src="{{ asset('image/logo-proaktif.jpeg') }}" alt="Logo"
                         class="h-full w-full rounded-lg object-cover">
                </div>
                <h2 class="mt-4 text-lg font-bold text-slate-900">RS BHAYANGKARA BOGOR</h2>
            </div>

            <div class="mb-8">
                <h2 class="text-2xl font-bold text-slate-900">Selamat Datang</h2>
                <p class="mt-1 text-sm text-slate-500">Silakan login untuk melanjutkan</p>
            </div>

            {{-- Error messages --}}
            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-red-600">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                    <div class="mt-1.5 relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                        </div>
                        <input id="email" name="email" type="email" autocomplete="email" required
                               value="{{ old('email') }}"
                               class="block w-full rounded-lg border-0 bg-slate-50 py-2.5 pl-10 pr-4 text-slate-900 placeholder-slate-400 ring-1 ring-slate-200 focus:ring-2 focus:ring-sky-500 transition sm:text-sm"
                               placeholder="nama@email.com">
                    </div>
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                    <div class="mt-1.5 relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                        </div>
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                               class="block w-full rounded-lg border-0 bg-slate-50 py-2.5 pl-10 pr-4 text-slate-900 placeholder-slate-400 ring-1 ring-slate-200 focus:ring-2 focus:ring-sky-500 transition sm:text-sm"
                               placeholder="••••••••">
                    </div>
                </div>

                {{-- Remember me --}}
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500 focus:ring-offset-0">
                        <span class="text-sm text-slate-500">Ingat saya</span>
                    </label>
                </div>

                {{-- Submit --}}
                <button type="submit"
                        class="flex w-full justify-center rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-sky-600/20 hover:bg-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition">
                    Login
                </button>
            </form>

            {{-- Footer shown only on mobile --}}
            <p class="mt-8 text-center text-xs text-slate-400 lg:hidden">
                &copy; {{ date('Y') }} RS Bhayangkara Bogor. All rights reserved.
            </p>
        </div>
    </div>

</div>
@endsection