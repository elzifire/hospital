@php
    $formatN = fn ($n) => is_int($n) || is_float($n) ? number_format($n, 0, ',', '.') : $n;
    $tonePilihan = [
        'hadir' => 'emerald',
        'iya' => 'emerald',
        'ya' => 'emerald',
        'bisa' => 'emerald',
        'datang' => 'emerald',
        'tidak' => 'rose',
        'gagal' => 'rose',
        'jadwal ulang' => 'amber',
        'mengulang' => 'amber',
    ];
    $toneFor = fn (string $label) => $tonePilihan[strtolower(trim($label))] ?? 'violet';
@endphp

<div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
    @foreach ($stats as $s)
        @php $sc = $tileTone[$s['tone']] ?? $tileTone['slate']; @endphp
        <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-xs ring-1 ring-slate-200">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg {{ $sc }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $s['icon'] }}" /></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-lg font-extrabold leading-tight tabular-nums text-slate-900" title="{{ $s['value'] }}">{{ $formatN($s['value']) }}</p>
                <p class="truncate text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ $s['label'] }}</p>
            </div>
        </div>
    @endforeach
</div>

{{-- ===== Rekap pilihan tombol ===== --}}
@if (count($rekapPilihan) > 0 && (string) request('jenis', '') !== 'teks')
    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
        <div class="mb-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 12a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9m5.5 0a4.5 4.5 0 1 0 0 9m8.5 0v0a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" /></svg>
                </span>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Rekap Pilihan Tombol</h3>
                    <p class="text-[11px] text-slate-400">Pilihan apa yang paling sering diklik pasien ({{ count($rekapPilihan) }} teratas)</p>
                </div>
            </div>
            @if ((string) request('pilihan', '') === '')
                <a href="{{ route('admin.monitoring.report.show', ['respon', 'jenis' => 'tombol']) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-[11px] font-bold text-white transition hover:bg-slate-700">
                    Lihat semua balasan tombol
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </a>
            @endif
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach ($rekapPilihan as $pilihan)
                <a href="{{ route('admin.monitoring.report.show', ['respon', 'jenis' => 'tombol', 'pilihan' => $pilihan['label']]) }}"
                   class="group inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 transition hover:border-violet-300 hover:bg-violet-50/50"
                   title="Lihat balasan dengan pilihan &quot;{{ $pilihan['label'] }}&quot;">
                    <span class="inline-flex h-2 w-2 rounded-full {{ $badgeTone[$toneFor($pilihan['label'])] ?? $badgeTone['slate'] }}"></span>
                    <span class="text-xs font-bold text-slate-800 group-hover:text-violet-800">{{ $pilihan['label'] }}</span>
                    <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-extrabold tabular-nums text-slate-500 group-hover:bg-white">{{ $formatN($pilihan['jumlah']) }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endif