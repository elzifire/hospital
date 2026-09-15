@if ($timeline->isEmpty())
    <p class="py-12 text-center text-sm text-slate-400">Belum ada pesan untuk percakapan ini.</p>
@else
    <div class="space-y-3">
        @php($prevDate = null)
        @foreach ($timeline as $t)
            @php($tgi = $t['waktu']?->timezone('Asia/Jakarta'))
            @if ($tgi && $tgi->toDateString() !== $prevDate)
                <div class="flex items-center gap-3 py-1">
                    <div class="h-px flex-1 bg-slate-300/50"></div>
                    <span class="whitespace-nowrap text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                        @if ($tgi->isToday())
                            Hari ini
                        @elseif ($tgi->isYesterday())
                            Kemarin
                        @else
                            {{ $tgi->translatedFormat('d M Y') }}
                        @endif
                    </span>
                    <div class="h-px flex-1 bg-slate-300/50"></div>
                </div>
            @endif
            @php($prevDate = $tgi?->toDateString())
            @if (($t['arah'] ?? '') === 'keluar')
                <div class="flex justify-end" title="{{ isset($t['status']) ? 'Status: '.ucfirst($t['status']).(isset($t['jenis']) ? ' · '.$t['jenis'] : '') : '' }}">
                    <div class="max-w-[80%] rounded-xl rounded-tr-sm bg-[#d9fdd3] px-4 py-2.5 shadow-sm">
                        <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-800">{{ $t['isi'] }}</p>
                        <p class="mt-1 flex items-center justify-end gap-1 text-[10px] text-slate-400">
                            {{ $tgi?->format('H:i') }}
                            @if (isset($t['status']))
                                @if ($t['status'] === 'terkirim')
                                    <svg class="h-3 w-3 text-sky-500" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                @elseif ($t['status'] === 'mengirim')
                                    <svg class="h-3 w-3 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                @elseif ($t['status'] === 'gagal')
                                    <svg class="h-3 w-3 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                @elseif ($t['status'] === 'menunggu')
                                    <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l3.75 3.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                @endif
                            @endif
                        </p>
                    </div>
                </div>
            @else
                <div class="flex justify-start">
                    <div class="max-w-[80%] rounded-xl rounded-tl-sm bg-white px-4 py-2.5 shadow-sm ring-1 ring-slate-100">
                        <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-800">{{ $t['isi'] }}</p>
                        <p class="mt-1 text-left text-[10px] text-slate-400">{{ $tgi?->format('H:i') }}</p>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endif