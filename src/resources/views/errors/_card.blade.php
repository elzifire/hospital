@php
    $kode = $kode ?? null;
    $judul = $judul ?? 'Terjadi Kesalahan';
    $deskripsi = $deskripsi ?? '';
    $ikon = $ikon ?? 'warning';
    $warna = $warna ?? 'sky';
    $tips = $tips ?? [];
    $aksi = $aksi ?? [];
    $acuan = $acuan ?? null;
    $home = $home ?? '/';
    $maintRetry = $retryAfter ?? null;
    $maintRefresh = $refresh ?? null;

    $warnaIcon = [
        'sky'     => 'bg-sky-500/15 text-sky-300 ring-sky-400/30',
        'amber'   => 'bg-amber-500/15 text-amber-300 ring-amber-400/30',
        'rose'    => 'bg-rose-500/15 text-rose-300 ring-rose-400/30',
        'emerald' => 'bg-emerald-500/15 text-emerald-300 ring-emerald-400/30',
        'violet'  => 'bg-violet-500/15 text-violet-300 ring-violet-400/30',
        'slate'   => 'bg-white/10 text-slate-300 ring-white/15',
    ][$warna] ?? 'bg-white/10 text-slate-300 ring-white/15';

    $ikonPath = [
        '404'    => 'M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z',
        '403'    => 'M12 9v3.75m0 3h.007v.007H12v-.007Zm8.25-8.25v6a3 3 0 0 1-2.383 2.928l-5.25 1.5a1.5 1.5 0 0 1-.634 0l-5.25-1.5a3 3 0 0 1-2.383-2.928V6.75a3 3 0 0 1 2.383-2.928l5.25-1.5a1.5 1.5 0 0 1 .634 0l5.25 1.5a3 3 0 0 1 2.383 2.928Z',
        '401'    => 'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z',
        'clock'  => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        '500'    => 'M21.75 6.75a4.5 4.5 0 0 1-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 1 1-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 0 1 6.336-4.486l-3.276 3.276a3.004 3.004 0 0 0 2.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852Z',
        '503'    => 'M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9',
        'check'  => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'back'   => 'M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18',
        'home'   => 'm2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
        'reload' => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99',
        'login'  => 'M8.25 4.5l7.5 7.5-7.5 7.5m6-6H3',
        'info'   => 'm11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z',
        'warning'=> 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
    ];
@endphp

<div class="w-full max-w-lg">
    {{-- Logo --}}
    <div class="mb-8 text-center">
        <img src="{{ asset('image/RSB.png') }}" alt="Logo Rumah Sakit Bhayangkara Bogor"
             class="mx-auto h-14 w-14 rounded-2xl object-cover shadow-lg ring-2 ring-white/20">
    </div>

    {{-- Kartu --}}
    <div class="rounded-2xl bg-white/10 p-6 shadow-2xl ring-1 ring-white/20 backdrop-blur-lg sm:p-8">
        {{-- Ikon besar --}}
        <div class="flex justify-center">
            <span class="flex h-20 w-20 items-center justify-center rounded-full ring-1 ring-inset {{ $warnaIcon }}">
                <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $ikonPath[$ikon] ?? $ikonPath['warning'] }}" />
                </svg>
            </span>
        </div>

        {{-- Judul & deskripsi --}}
        <div class="mt-6 text-center">
            @if ($kode !== null)
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Kode {{ $kode }}</p>
            @endif
            <h2 class="mt-2 text-2xl font-bold tracking-tight text-white">{{ $judul }}</h2>
            <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-slate-300">{{ $deskripsi }}</p>
        </div>

        {{-- Countdown maintenance --}}
        @if ((is_numeric($maintRetry) && $maintRetry > 0) || (is_numeric($maintRefresh) && $maintRefresh > 0))
            <div class="mt-6 flex items-center justify-center gap-3 rounded-xl bg-white/5 px-4 py-3 ring-1 ring-white/10">
                <svg class="h-6 w-6 flex-shrink-0 text-sky-300" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $ikonPath['clock'] }}" />
                </svg>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Perkiraan kembali</p>
                    <p id="mt-countdown" class="mt-0.5 text-center font-mono text-sm font-bold tabular-nums text-sky-200">--:--:--</p>
                </div>
            </div>
        @endif

        {{-- Tips --}}
        @if (! empty($tips))
            <div class="mt-6 rounded-xl bg-white/5 p-4 ring-1 ring-white/10">
                <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-400">
                    <svg class="h-4 w-4 text-sky-300" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $ikonPath['info'] }}" />
                    </svg>
                    Yang bisa kamu lakukan
                </p>
                <ul class="mt-3 space-y-2.5">
                    @foreach ($tips as $tip)
                        <li class="flex items-start gap-2.5 text-sm leading-relaxed text-slate-300">
                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $ikonPath['check'] }}" />
                            </svg>
                            <span>{{ $tip }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Tombol aksi --}}
        @if (! empty($aksi))
            <div class="mt-7 flex flex-col gap-2.5 sm:flex-row">
                @foreach ($aksi as $a)
                    @php
                        $tipe = $a['tipe'] ?? 'sekunder';
                        $cara = $a['cara'] ?? 'link';
                        $href = $a['href'] ?? '#';
                        $ikonKecil = $a['ikon'] ?? ($ikonPath[$cara] ?? $ikonPath['home']);
                        $kelasUtama = 'bg-sky-600 text-white shadow-lg shadow-sky-600/30 hover:bg-sky-500';
                        $kelasLain  = 'bg-white/10 text-slate-200 ring-1 ring-inset ring-white/15 hover:bg-white/15';
                    @endphp
                    <a href="{{ $href }}"
                       data-home="{{ $home }}"
                       @if ($cara === 'back') onclick="kembaliAtauBeranda(this); return false;" @endif
                       @if ($cara === 'reload') onclick="window.location.reload(); return false;" @endif
                       class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition {{ $tipe === 'utama' ? $kelasUtama : $kelasLain }}">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $ikonKecil }}" />
                        </svg>
                        {{ $a['label'] }}
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Kode acuan untuk teknisi --}}
        @if ($acuan !== null)
            <p class="mt-6 text-center text-[11px] text-slate-500">
                Kode acuan untuk teknisi: <span class="font-mono font-semibold text-slate-400">{{ $acuan }}</span>
            </p>
        @endif
    </div>

    {{-- Footer --}}
    <p class="mt-6 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} Rumah Sakit Bhayangkara Bogor &middot; Butuh bantuan? Hubungi admin sistem.
    </p>
</div>

@if ((is_numeric($maintRetry) && $maintRetry > 0) || (is_numeric($maintRefresh) && $maintRefresh > 0))
    <script>
        (function () {
            var retry   = Math.max(0, {{ is_numeric($maintRetry) ? (int) $maintRetry : 0 }});
            var refresh = {{ is_numeric($maintRefresh) ? (int) $maintRefresh : 0 }};
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