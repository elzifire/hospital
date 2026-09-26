@php
    $showRoute = 'admin.monitoring.'.$entity;

    $filterKeys = collect($config['filters'] ?? [])->pluck('key')->all();
    $hasActive  = trim((string) request('search')) !== ''
        || collect($filterKeys)->contains(fn ($k) => trim((string) request($k)) !== '');

    $currentSort = (string) request('sort', '');
    if (! isset($config['sorts'][$currentSort])) {
        $currentSort = $config['defaultSort'] ?? array_key_first($config['sorts'] ?? []);
    }

    $noSearch = collect(request()->except('search', 'page'))->filter(fn ($v) => $v !== null && $v !== '')->all();
    $noSearchUrl = route($showRoute) . (count($noSearch) ? '?' . http_build_query($noSearch) : '');
@endphp

<div class="border-b border-slate-100 bg-slate-50/60 p-4">
    <form method="GET" action="{{ route($showRoute) }}"
          class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">

        {{-- Pencarian --}}
        <div class="relative w-full lg:max-w-sm">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
            </div>
            <input type="text" name="search" value="{{ request('search') }}"
                   class="block w-full rounded-xl border-0 bg-white py-2.5 pl-10 pr-9 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 transition focus:ring-2 focus:ring-inset focus:ring-sky-500"
                   placeholder="{{ $config['searchHint'] ?? 'Cari data...' }}">
            @if (trim((string) request('search')) !== '')
                <a href="{{ $noSearchUrl }}" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 transition hover:text-slate-600" title="Bersihkan pencarian">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </a>
            @endif
        </div>

        <div class="flex flex-wrap items-end gap-2">
            @foreach ($config['filters'] ?? [] as $filter)
                @if ($filter['key'] === 'pilihan' && (string) request('jenis', '') === 'teks')
                    @continue
                @endif
                @if (($filter['type'] ?? 'select') === 'date')
                    <label class="flex flex-col gap-1">
                        <span class="pl-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $filter['label'] }}</span>
                        <input type="date" name="{{ $filter['key'] }}" value="{{ request($filter['key']) }}" onchange="this.form.submit()"
                               class="rounded-lg border-0 bg-white py-2 pl-3 pr-2.5 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 transition focus:ring-2 focus:ring-sky-500">
                    </label>
                @else
                    <select name="{{ $filter['key'] }}" onchange="this.form.submit()"
                            class="{{ $filter['key'] === 'jenis' ? 'border-violet-300 bg-violet-50 font-bold text-violet-900' : '' }} cursor-pointer rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 transition focus:ring-2 focus:ring-sky-500">
                        <option value="">{{ $filter['label'] }}</option>
                        @foreach (($filterOptions[$filter['key']] ?? []) as $value => $label)
                            <option value="{{ $value }}" @selected(request($filter['key']) == $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                @endif
            @endforeach

            <select name="sort" onchange="this.form.submit()"
                    class="cursor-pointer rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 transition focus:ring-2 focus:ring-sky-500">
                @foreach ($config['sorts'] ?? [] as $key => $sort)
                    <option value="{{ $key }}" @selected($currentSort === $key)>{{ $sort['label'] }}</option>
                @endforeach
            </select>

            <select name="per_page" onchange="this.form.submit()"
                    class="cursor-pointer rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 transition focus:ring-2 focus:ring-sky-500">
                <option value="10" @selected(request('per_page', '10') == '10')>10 / halaman</option>
                <option value="25" @selected(request('per_page') == '25')>25 / halaman</option>
                <option value="50" @selected(request('per_page') == '50')>50 / halaman</option>
                <option value="100" @selected(request('per_page') == '100')>100 / halaman</option>
            </select>

            @if ($hasActive)
                <a href="{{ route($showRoute) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3.5 py-2 text-xs font-bold text-white transition hover:bg-slate-700"
                   title="Hapus pencarian &amp; penyaringan">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                    Reset
                </a>
            @endif
        </div>
    </form>

    {{-- Pemilih cepat pilihan tombol (chips) saat filter aktif --}}
    @if (trim((string) request('jenis')) === 'tombol' && count($filterOptions['pilihan'] ?? []) > 0)
        <div class="mt-3 flex flex-wrap items-center gap-1.5">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pilihan tombol:</span>
            <a href="{{ route($showRoute, ['jenis' => 'tombol']) }}"
               class="{{ request('pilihan') === '' || request('pilihan') === null ? 'bg-violet-600 text-white ring-violet-600' : 'bg-white text-slate-600 ring-slate-200 hover:bg-violet-50' }} rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset transition">
                Semua
            </a>
            @foreach (($filterOptions['pilihan'] ?? []) as $value => $label)
                <a href="{{ route($showRoute, ['jenis' => 'tombol', 'pilihan' => $value]) }}"
                   class="{{ request('pilihan') === $value ? 'bg-violet-600 text-white ring-violet-600' : 'bg-white text-slate-600 ring-slate-200 hover:bg-violet-50' }} rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset transition">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    @endif
</div>