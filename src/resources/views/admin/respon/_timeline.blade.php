@if ($timeline->isEmpty())
    <p class="py-12 text-center text-sm text-slate-400">Belum ada pesan untuk percakapan ini.</p>
@else
    @php
        $prevDate = null;

        $ikonMedia = [
            'image'    => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z',
            'audio'    => 'M9 9l10.5-3m0 6.553v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 1 1-.99-3.467l2.31-.66a2.25 2.25 0 0 0 1.632-2.163Zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 0 1-.99-3.467l2.31-.66A2.25 2.25 0 0 0 9 15.553Z',
            'video'    => 'M15.75 10.5l4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z',
            'document' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
            'sticker'  => 'M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10',
        ];
    @endphp
    <div class="space-y-3">

        @foreach ($timeline as $t)
            @php
                $tgi = $t['waktu'] ?->timezone('Asia/Jakarta');
            @endphp
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
            @php
                $prevDate = $tgi?->toDateString();
            @endphp

            @if (($t['arah'] ?? '') === 'keluar')
                @php
                    $meta = $t['meta_payload'] ?? null;
                    $media = isset($meta['kind']) && $meta['kind'] === 'media' ? $meta : null;
                    $interaktif = isset($meta['kind']) && $meta['kind'] === 'interactive' ? $meta : null;
                @endphp
                <div class="flex justify-end" title="{{ isset($t['status']) ? 'Status: '.ucfirst($t['status']).(isset($t['jenis']) ? ' · '.$t['jenis'] : '') : '' }}">
                    <div class="max-w-[82%] rounded-xl rounded-tr-sm bg-[#d9fdd3] px-4 py-2.5 shadow-sm">
                        @if ($media !== null)
                            <div class="space-y-1.5">
                                @if ($media['tipe'] === 'image')
                                    <a href="{{ asset('storage/'.$media['path']) }}" target="_blank" rel="noopener noreferrer" title="Buka gambar di tab baru">
                                        <img src="{{ asset('storage/'.$media['path']) }}" alt="{{ $media['caption'] ?? '' }}"
                                             class="h-64 w-full rounded-lg object-cover">
                                    </a>
                                @else
                                    <div class="flex items-center gap-3 rounded-lg bg-white/70 px-3 py-2.5 ring-1 ring-black/5">
                                        <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-600">
                                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ikonMedia[$media['tipe']] ?? $ikonMedia['document'] }}"/></svg>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-semibold text-slate-700">{{ $media['nama'] ?? 'Lampiran' }}</p>
                                            <p class="text-[11px] text-slate-400">{{ strtoupper($media['tipe'] ?? '') }}</p>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-1">
                                            <a href="{{ asset('storage/'.$media['path']) }}" target="_blank" rel="noopener noreferrer"
                                               class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-[11px] font-bold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-200">Buka</a>
                                            <a href="{{ asset('storage/'.$media['path']) }}" download
                                               class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-[11px] font-bold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-200">Unduh</a>
                                        </div>
                                    </div>
                                @endif
                                @if (filled($media['caption'] ?? null))
                                    <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-800">{{ $media['caption'] }}</p>
                                @endif
                                @if ($media['tipe'] === 'image')
                                    <div class="flex justify-end">
                                        <a href="{{ asset('storage/'.$media['path']) }}" download
                                           class="inline-flex items-center gap-1 rounded-lg bg-white/70 px-2.5 py-1.5 text-[11px] font-bold text-slate-500 ring-1 ring-black/5 transition hover:bg-white">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            Unduh gambar
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @elseif ($interaktif !== null)
                            <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-800">{{ $interaktif['body'] ?? $t['isi'] }}</p>
                            @if (filled($interaktif['url'] ?? null))
                                <a href="{{ $interaktif['url'] }}" target="_blank" rel="noopener noreferrer"
                                   class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-sky-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-sky-700">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                    {{ $interaktif['label'] ?? 'Buka' }}
                                </a>
                            @endif
                        @else
                            <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-800">{{ $t['isi'] }}</p>
                        @endif
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
                @php
                    $keterangan = preg_replace('/^\[[^\]]+\](?: (.+))?$/', '$1', (string) $t['isi']);
                @endphp
                <div class="flex justify-start">
                    <div class="max-w-[82%] rounded-xl rounded-tl-sm bg-white px-4 py-2.5 shadow-sm ring-1 ring-slate-100">
                        @if (($min = $t['media_in'] ?? null) !== null)
                            <div class="space-y-1.5">
                                @if ($min['kind'] === 'image')
                                    <a href="{{ $min['url'] }}" target="_blank" rel="noopener noreferrer" title="Buka gambar di tab baru">
                                        <img src="{{ $min['url'] }}" alt="{{ $keterangan }}" class="h-64 w-full rounded-lg object-cover">
                                    </a>
                                @else
                                    <div class="flex items-center gap-3 rounded-lg bg-emerald-50/60 px-3 py-2.5 ring-1 ring-emerald-100">
                                        <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ikonMedia[$min['kind']] ?? $ikonMedia['document'] }}"/></svg>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-semibold text-slate-700">{{ $min['nama'] }}</p>
                                            <p class="text-[11px] text-slate-400">{{ strtoupper($min['kind']) }} dari {{ $t['nama'] ?? 'kontak' }}</p>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-1">
                                            <a href="{{ $min['url'] }}" target="_blank" rel="noopener noreferrer"
                                               class="rounded-lg bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-600 ring-1 ring-emerald-200 transition hover:bg-emerald-50">Buka</a>
                                            <a href="{{ $min['unduh'] }}" download
                                               class="rounded-lg bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-600 ring-1 ring-emerald-200 transition hover:bg-emerald-50">Unduh</a>
                                        </div>
                                    </div>
                                @endif
                                @if ($keterangan !== '')
                                    <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-800">{{ $keterangan }}</p>
                                @endif
                                @if ($min['kind'] === 'image')
                                    <div class="flex justify-end">
                                        <a href="{{ $min['unduh'] }}" download
                                           class="inline-flex items-center gap-1 rounded-lg bg-emerald-50/60 px-2.5 py-1.5 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-100 transition hover:bg-emerald-50">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            Unduh gambar
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @elseif (filled($t['media_kind'] ?? null))
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ikonMedia[$t['media_kind']] ?? $ikonMedia['document'] }}"/></svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-700">Lampiran {{ strtoupper($t['media_kind']) }}</p>
                                    <p class="text-[11px] text-slate-400">Dari {{ $t['nama'] ?? 'kontak' }}</p>
                                </div>
                            </div>
                            @if ($keterangan !== '')
                                <p class="mt-1.5 whitespace-pre-wrap text-sm leading-relaxed text-slate-800">{{ $keterangan }}</p>
                            @endif
                        @else
                            <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-800">{{ $t['isi'] }}</p>
                        @endif
                        <p class="mt-1 text-left text-[10px] text-slate-400">{{ $tgi?->format('H:i') }}</p>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endif