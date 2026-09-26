@php
    $formatN = fn ($n) => is_int($n) || is_float($n) ? number_format($n, 0, ',', '.') : $n;
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