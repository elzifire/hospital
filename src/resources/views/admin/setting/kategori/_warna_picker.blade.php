{{--
    Picker warna badge kategori.
    Butuh: $selectedWarna (string) — warna yang sedang terpilih.
--}}
@php
    $colorMap = [
        'emerald' => ['bg' => 'bg-emerald-50', 'dot' => 'bg-emerald-500'],
        'sky'     => ['bg' => 'bg-sky-50',     'dot' => 'bg-sky-500'],
        'amber'   => ['bg' => 'bg-amber-50',   'dot' => 'bg-amber-500'],
        'rose'    => ['bg' => 'bg-rose-50',    'dot' => 'bg-rose-500'],
        'purple'  => ['bg' => 'bg-purple-50',  'dot' => 'bg-purple-500'],
        'indigo'  => ['bg' => 'bg-indigo-50',  'dot' => 'bg-indigo-500'],
        'teal'    => ['bg' => 'bg-teal-50',    'dot' => 'bg-teal-500'],
    ];
@endphp

<label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Pilih Warna Badge Aksen</label>
<div class="flex flex-wrap gap-2.5">
    @foreach ($colorMap as $w => $c)
        <label class="flex flex-col items-center gap-1 cursor-pointer">
            <input type="radio" name="warna" value="{{ $w }}" {{ old('warna', $selectedWarna) === $w ? 'checked' : '' }} class="sr-only">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $c['bg'] }} ring-2 transition"
                  :class="selectedWarna === '{{ $w }}' ? 'ring-slate-900 scale-110 shadow-sm' : 'ring-transparent opacity-70 hover:opacity-100'">
                <span class="h-3.5 w-3.5 rounded-full {{ $c['dot'] }}"></span>
            </span>
            <span class="text-[9px] font-bold capitalize text-slate-500">{{ $w }}</span>
        </label>
    @endforeach
</div>