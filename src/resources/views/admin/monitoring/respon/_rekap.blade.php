@php
    $activePilihan = (string) request('pilihan');
    $hasPilihan    = trim($activePilihan) !== '';
    $rekapKosong   = count($rekapPilihan) === 0;
    $totalRekap    = (int) collect($rekapPilihan)->sum('count');

    // Paginasi mini dalam panel rekap: 2 pilihan per halaman.
    $langkahPerHal = 2;
    $langkahTotal  = $rekapKosong ? 0 : max(1, (int) ceil(count($rekapPilihan) / $langkahPerHal));
    $langkahAktif  = $langkahTotal ? max(1, min((int) request('rekap'), $langkahTotal)) : 1;
    $langkahAwal   = ($langkahAktif - 1) * $langkahPerHal;
    $kolomHalaman  = array_slice($rekapPilihan, $langkahAwal, $langkahPerHal);

    // Blok statistik mini di atas daftar pilihan saat filter pilihan aktif.
    $prevAvailableBlocks = $hasPilihan && count($kolomHalaman) > 0;
@endphp

@if (! $rekapKosong && (string) request('jenis', '') !== 'teks')
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between gap-3 border-b border-violet-100 bg-violet-50/60 px-5 py-3">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" /></svg>
                <h3 class="text-sm font-extrabold text-violet-900">Rekap Respons Pilihan Tombol</h3>
            </div>
            <span class="rounded-full bg-violet-100 px-2.5 py-1 text-[11px] font-bold text-violet-700">{{ count($rekapPilihan) }} pilihan</span>
        </div>

        @if ($prevAvailableBlocks)
            <div class="grid grid-cols-2 gap-3 border-b border-slate-100 px-5 py-4 lg:grid-cols-4">
                @foreach ($kolomHalaman as $kol)
                    <div class="rounded-xl border border-slate-100 bg-slate-50/60 px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $kol['label'] }}</p>
                        <p class="mt-0.5 text-sm font-extrabold tabular-nums text-slate-900">{{ $kol['count'] }}</p>
                        @if (! empty($kol['incoming']))
                            <a href="{{ route('admin.monitoring.'.$entity, ['search' => $kol['incoming']]) }}"
                               class="mt-1 inline-block text-[10px] font-bold text-emerald-600 transition hover:text-emerald-700">
                                {{ $kol['incoming_label'] ?? 'respon' }} →⟩
                            </a>
                        @else
                            <p class="mt-1 text-[10px] font-medium text-slate-400">{{ $kol['keterangan'] ?? '' }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="divide-y divide-slate-50">
            @foreach ($kolomHalaman as $kol)
                @php
                    $pad   = max($kol['count'], 1);
                    $bagi  = $totalRekap > 0 ? round(($kol['count'] / $totalRekap) * 100) : 0;
                    $sebar = ($kol['count'] > 0 && $totalRekap > 0) ? round(log($pad + 2, 1.8) * 14) : 0;
                    $peta  = ['rose', 'amber', 'sky', 'emerald', 'violet'];
                    $indeks = $loop->index % count($peta);
                    $warna = $peta[$indeks];
                @endphp
                <div class="px-5 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="inline-flex h-2 w-2 flex-shrink-0 rounded-full bg-{{ $warna }}-400"></span>
                            <span class="truncate text-sm font-bold text-slate-800">{{ $kol['label'] }}</span>
                        </div>
                        <div class="flex flex-shrink-0 items-baseline gap-3">
                            <span class="inline-flex items-baseline gap-1 text-sm font-extrabold tabular-nums text-slate-900">
                                {{ number_format($kol['count'], 0, ',', '.') }}
                                <span class="text-[10px] font-bold text-slate-400">{{ $totalRekap > 0 ? round($kol['count'] / $totalRekap * 100) : 0 }}%</span>
                            </span>
                            <a href="{{ route('admin.monitoring.'.$entity, array_filter(['jenis' => 'tombol', 'pilihan' => $kol['kunci'] ?? $kol['label'], 'sort' => request('sort'), 'per_page' => request('per_page')])) }}"
                               class="rounded-full bg-violet-50 px-3 py-1 text-[11px] font-bold text-violet-700 ring-1 ring-inset ring-violet-200/60 transition hover:bg-violet-100"
                               title="Tampilkan detail respons dengan pilihan ini">
                                Detail →
                            </a>
                        </div>
                    </div>
                    <div class="mt-2.5 flex items-center gap-2">
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100 ring-1 ring-inset ring-slate-200/50">
                            <div class="h-full rounded-full bg-{{ $warna }}-400" style="width: {{ max($sebar, 2) }}%"></div>
                        </div>
                        <span class="w-10 text-right text-[11px] font-bold tabular-nums text-slate-400">{{ $bagi }}%</span>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($langkahTotal > 1)
            <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50/60 px-5 py-2.5">
                <p class="text-[11px] font-medium text-slate-400">Pilihan {{ $langkahAwal + 1 }}–{{ min($langkahAwal + $langkahPerHal, count($rekapPilihan)) }} dari {{ count($rekapPilihan) }}</p>
                <div class="flex items-center gap-1.5">
                    @if ($langkahAktif > 1)
                        <a href="{{ route('admin.monitoring.'.$entity, request()->merge(['rekap' => $langkahAktif - 1])->except('page')) }}"
                           class="rounded-lg bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-600 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-50"
                           title="Pilihan sebelumnya">⟨ Sebelumnya</a>
                    @endif
                    <span class="text-[11px] font-bold tabular-nums text-slate-500">{{ $langkahAktif }}/{{ $langkahTotal }}</span>
                    @if ($langkahAktif < $langkahTotal)
                        <a href="{{ route('admin.monitoring.'.$entity, request()->merge(['rekap' => $langkahAktif + 1])->except('page')) }}"
                           class="rounded-lg bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-600 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-50"
                           title="Pilihan berikutnya">Berikutnya ⟩</a>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endif