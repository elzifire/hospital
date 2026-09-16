@php
    $formatN = fn ($n) => is_int($n) || is_float($n) ? number_format($n, 0, ',', '.') : $n;
    $columns = $config['columns'] ?? [];
    $tableMinW = max(720, 130 * count($columns) + 90);
@endphp

<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
    @include('admin.monitoring.report.respon._toolbar')

    <div class="overflow-x-auto">
        <table class="w-full min-w-[{{ $tableMinW }}px] text-left">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/40 text-[11px] uppercase tracking-wider text-slate-400">
                    <th class="px-4 py-3.5 text-center font-semibold">#</th>
                    <th class="px-5 py-3.5 font-semibold">Waktu</th>
                    <th class="px-5 py-3.5 font-semibold">Pengirim</th>
                    <th class="px-5 py-3.5 font-semibold">Pasien</th>
                    <th class="px-5 py-3.5 font-semibold">Sumber</th>
                    <th class="px-5 py-3.5 font-semibold">Pilihan</th>
                    <th class="px-5 py-3.5 font-semibold">Isi Balasan</th>
                    <th class="px-5 py-3.5 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($rows as $row)
                    @php
                        $no = $rows->firstItem() + $loop->index;
                        $tipe = $row->payload['pesan']['type'] ?? null;
                        $dariTombol = in_array($tipe, ['button', 'interactive'], true);
                        $toneFor = [
                            'hadir' => 'emerald', 'iya' => 'emerald', 'ya' => 'emerald', 'datang' => 'emerald',
                            'tidak' => 'rose', 'gagal' => 'rose',
                            'jadwal ulang' => 'amber', 'mengulang' => 'amber',
                            'tunda' => 'amber', 'nanti' => 'amber',
                        ];
                        $pilihanTone = $toneFor[strtolower(trim((string) $row->isi_pesan))] ?? 'violet';
                        $sumber = $dariTombol ? ['Tombol', 'violet'] : ($row->payload !== null ? ['Teks', 'sky'] : ['Manual', 'slate']);
                    @endphp
                    <tr class="group bg-white transition-colors hover:bg-violet-50/30">
                        <td class="px-4 py-4 text-center text-xs font-bold tabular-nums text-slate-300">{{ $no }}</td>

                        <td class="whitespace-nowrap px-5 py-4">
                            <span class="text-sm font-bold text-slate-900">{{ $row->waktu_masuk?->translatedFormat('d M Y') }}</span>
                            <p class="font-mono text-[11px] text-slate-400">{{ $row->waktu_masuk?->translatedFormat('H:i') }} WIB</p>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full {{ $tileTone['violet'] }} text-xs font-bold uppercase">
                                    {{ strtoupper(Str::substr((string) ($row->nama ?? $row->no_hp), 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-900">{{ $row->nama ?? 'Nomor Tak Dikenal' }}</p>
                                    <p class="truncate font-mono text-[11px] text-slate-400">{{ $row->no_hp }}</p>
                                </div>
                            </div>
                        </td>

                        <td class="whitespace-nowrap px-5 py-4">
                            @if ($row->pnpp)
                                <div class="flex flex-col gap-0.5">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $badgeTone['emerald'] }}">{{ $row->pnpp->nama }}</span>
                                    @if ($row->pnpp->nip)
                                        <span class="font-mono text-[11px] text-slate-400">{{ $row->pnpp->nip }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $badgeTone['slate'] }}">Tidak Terdaftar</span>
                            @endif
                        </td>

                        <td class="whitespace-nowrap px-5 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $badgeTone[$sumber[1]] ?? $badgeTone['slate'] }}">
                                @if ($sumber[1] === 'violet')
                                    <svg class="mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 12a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9m5.5 0a4.5 4.5 0 1 0 0 9m8.5 0v0a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" /></svg>
                                @endif
                                {{ $sumber[0] }}
                            </span>
                        </td>

                        <td class="whitespace-nowrap px-5 py-4">
                            @if ($dariTombol)
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $badgeTone[$pilihanTone] }}">
                                    {{ $row->isi_pesan }}
                                </span>
                            @else
                                <span class="text-xs italic text-slate-300">—</span>
                            @endif
                        </td>

                        <td class="px-5 py-4">
                            <p class="max-w-[280px] whitespace-pre-line text-sm text-slate-600">{{ $row->isi_pesan }}</p>
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 text-right">
                            <a href="{{ route('admin.respon.show', $row->no_hp) }}"
                               class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-600 transition hover:border-violet-300 hover:text-violet-700">
                                Percakapan
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-16 text-center">
                            <div class="mx-auto flex max-w-sm flex-col items-center">
                                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 ring-8 ring-slate-50">
                                    <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $config['icon'] }}" /></svg>
                                </div>
                                <h3 class="text-base font-bold text-slate-900">Tidak ada data balasan</h3>
                                <p class="mt-1 text-sm text-slate-500">Balasan otomatis dari pasien (termasuk pilihan tombol) akan tampil di sini begitu tiba. Ubah kata kunci, filter, atau muat ulang tanpa penyaringan.</p>
                                <a href="{{ route('admin.monitoring.report.show', $entity) }}" class="mt-5 rounded-lg bg-slate-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-700">Tampilkan Semua Data</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($rows->total() > 0)
        <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:flex-row">
            <p class="text-xs font-medium text-slate-500">
                Menampilkan <span class="font-bold text-slate-800">{{ $rows->firstItem() }}</span>–<span class="font-bold text-slate-800">{{ $rows->lastItem() }}</span>
                dari <span class="font-bold text-slate-800">{{ $formatN($rows->total()) }}</span> balasan
            </p>
            {{ $rows->links() }}
        </div>
    @endif
</div>