@php
    $showRoute = 'admin.monitoring.'.$entity;
    $responRoute = 'admin.respon.show';
    $columns = $config['columns'] ?? [];
    $tableMinW = max(760, 130 * count($columns) + 90);
    $formatN = fn ($n) => is_int($n) || is_float($n) ? number_format($n, 0, ',', '.') : $n;

    $filterKeys = collect($config['filters'] ?? [])->pluck('key')->all();
    $hasActive  = trim((string) request('search')) !== ''
        || collect($filterKeys)->contains(fn ($k) => trim((string) request($k)) !== '');
@endphp

<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
    @include('admin.monitoring.respon._toolbar')

    <div class="overflow-x-auto">
        <table class="w-full min-w-[{{ $tableMinW }}px] text-left">
            <thead>
                <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                    <th class="px-4 py-3.5 text-center font-semibold">#</th>
                    @foreach ($columns as $col)
                        <th class="px-5 py-3.5 font-semibold">{{ $col['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($rows as $row)
                    @php $no = $rows->firstItem() + $loop->index; @endphp
                    <tr class="group bg-white transition-colors hover:bg-violet-50/40">
                        <td class="px-4 py-4 text-center text-xs font-bold tabular-nums text-slate-300">{{ $no }}</td>

                        @foreach ($columns as $col)
                            @php
                                $v    = ($col['value'])($row);
                                $tone = $col['tone'] ?? 'slate';
                            @endphp

                            @if ($col['type'] === 'profile')
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route($responRoute, $row->id) }}" class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full {{ $tileTone[$tone] ?? $tileTone['slate'] }} text-xs font-bold uppercase transition hover:opacity-80">
                                            {{ strtoupper(substr((string) ($v[0] ?? '?'), 0, 1)) }}
                                        </a>
                                        <div class="min-w-0">
                                            <a href="{{ route($responRoute, $row->id) }}" class="truncate text-sm font-bold text-slate-900 transition hover:text-violet-700">
                                                {{ $v[0] ?? '—' }}
                                            </a>
                                            @if (! empty($v[1]))
                                                <p class="truncate font-mono text-[11px] text-slate-400">{{ $v[1] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                            @elseif ($col['type'] === 'strong')
                                <td class="whitespace-nowrap px-5 py-4">
                                    @if ($v !== null && $v !== '')
                                        <span class="text-sm font-bold text-slate-900">{{ $v }}</span>
                                    @else
                                        <span class="text-xs italic text-slate-300">—</span>
                                    @endif
                                </td>

                            @elseif ($col['type'] === 'mono')
                                <td class="whitespace-nowrap px-5 py-4">
                                    @if ($v !== null && $v !== '')
                                        <span class="font-mono text-xs font-semibold text-slate-600">{{ $v }}</span>
                                    @else
                                        <span class="text-xs italic text-slate-300">—</span>
                                    @endif
                                </td>

                            @elseif ($col['type'] === 'text')
                                <td class="px-5 py-4">
                                    @if ($v !== null && $v !== '')
                                        <p class="max-w-[240px] truncate text-sm text-slate-600" title="{{ $v }}">{{ $v }}</p>
                                    @else
                                        <span class="text-xs italic text-slate-300">—</span>
                                    @endif
                                </td>

                            @elseif ($col['type'] === 'number')
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold tabular-nums ring-1 ring-inset {{ $badgeTone[$tone] ?? $badgeTone['slate'] }}">{{ $v }}</span>
                                </td>

                            @elseif ($col['type'] === 'stat')
                                <td class="whitespace-nowrap px-5 py-4">
                                    <p class="text-sm font-extrabold tabular-nums text-slate-900">{{ $v[0] ?? '0' }}</p>
                                    @if (! empty($v[1]))
                                        <p class="text-[11px] text-slate-400">{{ $v[1] }}</p>
                                    @endif
                                </td>

                            @elseif ($col['type'] === 'badge')
                                @php $badgeToneKey = is_array($v) ? ($v[1] ?? $tone) : $tone; @endphp
                                <td class="whitespace-nowrap px-5 py-4">
                                    @if (is_array($v) && ! empty($v[0]))
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $badgeTone[$badgeToneKey] ?? $badgeTone['slate'] }}">{{ $v[0] }}</span>
                                    @else
                                        <span class="text-xs italic text-slate-300">—</span>
                                    @endif
                                </td>

                            @elseif ($col['type'] === 'tags')
                                <td class="px-5 py-4">
                                    @if (is_array($v) && count($v) > 0)
                                        <div class="flex max-w-[260px] flex-wrap gap-1.5">
                                            @foreach (array_slice($v, 0, 3) as $tag)
                                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $badgeTone[$tone] ?? $badgeTone['slate'] }}">{{ $tag }}</span>
                                            @endforeach
                                            @if (count($v) > 3)
                                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-500 ring-1 ring-inset ring-slate-200" title="{{ implode(', ', $v) }}">+{{ count($v) - 3 }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs italic text-slate-300">—</span>
                                    @endif
                                </td>

                            @else
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $v }}</td>
                            @endif
                        @endforeach
                    </tr>

                @empty
                    <tr>
                        <td colspan="{{ count($columns) + 1 }}" class="px-6 py-16 text-center">
                            <div class="mx-auto flex max-w-sm flex-col items-center">
                                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 ring-8 ring-slate-50">
                                    <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $config['icon'] }}" /></svg>
                                </div>
                                <h3 class="text-base font-bold text-slate-900">Tidak ada data Respon</h3>
                                <p class="mt-1 text-sm text-slate-500">Coba gunakan kata kunci lain, atau tampilkan semua data tanpa penyaringan.</p>
                                @if ($hasActive)
                                    <a href="{{ route($showRoute) }}" class="mt-5 rounded-lg bg-slate-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-700">Tampilkan Semua Data</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($rows->total() > 0)
        <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:flex-row">
            <p class="text-xs font-medium text-slate-500">
                Menampilkan <span class="font-bold text-slate-800">{{ $rows->firstItem() }}</span>–<span class="font-bold text-slate-800">{{ $rows->lastItem() }}</span>
                dari <span class="font-bold text-slate-800">{{ $formatN($rows->total()) }}</span> data Respon
            </p>
            {{ $rows->links() }}
        </div>
    @endif
</div>