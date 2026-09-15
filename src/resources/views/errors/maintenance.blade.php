@extends('errors._layout')

@section('title', 'Sistem Sedang Dalam Perbaikan')

@section('content')
    <div class="w-full max-w-lg">
        {{-- Logo --}}
        <div class="mb-8 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl shadow-lg ring-2 ring-white/20">
                <img src="{{ asset('image/RSB.png') }}" alt="Logo Rumah Sakit Bhayangkara Bogor" class="h-16 w-16 rounded-2xl object-cover">
            </div>
            <h2 class="mt-4 text-xl font-bold text-white">RUMAH SAKIT BHAYANGKARA BOGOR</h2>
            <p class="mt-1 text-sm text-slate-400">Sistem sedang istirahat sejenak</p>
        </div>

        {{-- Kartu --}}
        <div class="rounded-2xl bg-white/10 p-6 shadow-2xl ring-1 ring-white/20 backdrop-blur-lg sm:p-8">
            <div class="flex justify-center">
                <span class="relative flex h-24 w-24 items-center justify-center rounded-full bg-sky-500/15 text-sky-300 ring-1 ring-inset ring-sky-400/30">
                    <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085" />
                    </svg>
                    {{-- Detil putar halus biar terasa "sedang bekerja" --}}
                    <span class="absolute -right-1 -top-1 flex h-4 w-4">
                        <span class="relative inline-flex h-4 w-4 rounded-full bg-emerald-400 ring-2 ring-white/10 animate-pulse"></span>
                    </span>
                </span>
            </div>

            <div class="mt-6 text-center">
                <h2 class="text-2xl font-bold tracking-tight text-white">Sistem Sedang Dalam Perbaikan</h2>
                <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-slate-300">
                    Kami sedang melakukan pemeliharaan singkat agar sistem tetap aman dan lancar.
                    Tenang saja — data kamu tetap aman, dan sistem akan kembali otomatis.
                </p>
            </div>

            @if ((is_numeric($retryAfter ?? null) && $retryAfter > 0) || (is_numeric($refresh ?? null) && $refresh > 0))
                <div class="mt-6 flex items-center justify-center gap-3 rounded-xl bg-white/5 px-4 py-3.5 ring-1 ring-white/10">
                    <svg class="h-6 w-6 flex-shrink-0 text-sky-300" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <div class="text-center">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Perkiraan kembali</p>
                        <p id="mt-countdown" class="mt-0.5 font-mono text-sm font-bold tabular-nums text-sky-200">--:--:--</p>
                    </div>
                </div>
            @endif

            <div class="mt-6 rounded-xl bg-white/5 p-4 ring-1 ring-white/10">
                <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-400">
                    <svg class="h-4 w-4 text-sky-300" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    Terima kasih atas kesabaranmu
                </p>
                <ul class="mt-3 space-y-2.5">
                    <li class="flex items-start gap-2.5 text-sm leading-relaxed text-slate-300">
                        <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        <span>Halaman ini akan dimuat ulang otomatis begitu perbaikan selesai.</span>
                    </li>
                    <li class="flex items-start gap-2.5 text-sm leading-relaxed text-slate-300">
                        <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        <span>Kalau sistem belum normal, coba buka kembali lewat tombol di bawah.</span>
                    </li>
                </ul>
            </div>

            <div class="mt-7">
                <a href="#" onclick="window.location.reload(); return false;"
                   class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-sky-600/30 transition hover:bg-sky-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                    Coba Lagi
                </a>
            </div>
        </div>

        <p class="mt-6 text-center text-xs text-slate-500">
            &copy; {{ date('Y') }} Rumah Sakit Bhayangkara Bogor &middot; Butuh bantuan? Hubungi admin sistem.
        </p>
    </div>

    @if ((is_numeric($retryAfter ?? null) && $retryAfter > 0) || (is_numeric($refresh ?? null) && $refresh > 0))
        <script>
            (function () {
                var retry   = Math.max(0, {{ is_numeric($retryAfter ?? null) ? (int) $retryAfter : 0 }});
                var refresh = {{ is_numeric($refresh ?? null) ? (int) $refresh : 0 }};
                var sisa    = retry;
                var el      = document.getElementById('mt-countdown');

                function pad(n) { return String(n).padStart(2, '0'); }

                function tick() {
                    if (el) {
                        el.textContent = pad(Math.floor(sisa / 3600)) + ':' + pad(Math.floor((sisa % 3600) / 60)) + ':' + pad(sisa % 60);
                    }
                    if (sisa <= 0) {
                        window.location.reload();
                        return;
                    }
                    sisa--;
                }

                if (el) {
                    tick();
                    setInterval(tick, 1000);
                }
                if (refresh > 0) {
                    setTimeout(function () { window.location.reload(); }, refresh * 1000);
                }
            })();
        </script>
    @endif
@endsection