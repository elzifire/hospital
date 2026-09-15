@php
    $avatarColors = [
        'bg-emerald-100 text-emerald-700',
        'bg-sky-100 text-sky-700',
        'bg-violet-100 text-violet-700',
        'bg-amber-100 text-amber-700',
        'bg-rose-100 text-rose-700',
        'bg-teal-100 text-teal-700',
        'bg-indigo-100 text-indigo-700',
        'bg-orange-100 text-orange-700',
    ];
@endphp
<div id="chat-list-section">
    @if ($konversasi->isEmpty())
        <div class="px-5 py-14 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                <svg class="h-6 w-6 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-2.029 2.115 2.115 0 0 0-1.661-.586 48.744 48.744 0 0 0-8.983 0 2.115 2.115 0 0 0-1.661.586 2.126 2.126 0 0 0-.476 2.029c.172.714.308 1.44.41 2.174m3.923-2.174a41.03 41.03 0 0 0-.41 2.174c-.058.35-.088.706-.088 1.066v4.286c0 .36.03.716.088 1.066" /></svg>
            </div>
            <p class="mt-3 text-sm font-medium text-slate-500">Belum ada percakapan.</p>
            <p class="mt-0.5 text-xs text-slate-400">Balasan pasien akan muncul di sini secara otomatis.</p>
        </div>
    @else
        <ul class="divide-y divide-slate-100">
            @foreach ($konversasi as $c)
                @php
                    $namaTampil = $c->pnpp?->nama ?? $c->nama_pengirim ?? null;
                    $inisial = mb_substr($namaTampil ?? $c->no_hp, 0, 1, 'UTF-8');
                    $adaBelum = $c->belum_dibaca > 0;
                    $warna = $avatarColors[crc32($c->no_hp) % count($avatarColors)];
                @endphp
                <li>
                    <a href="{{ route('admin.respon.show', $c->no_hp) }}"
                       class="flex items-center gap-3.5 px-5 py-3.5 transition {{ $adaBelum ? 'bg-emerald-50/60 hover:bg-emerald-50' : 'hover:bg-slate-50' }}">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full {{ $adaBelum ? $warna . ' font-bold' : 'bg-slate-200 text-slate-500 font-semibold' }}">
                            <span class="text-sm">{{ strtoupper($inisial) }}</span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="{{ $adaBelum ? 'font-extrabold text-slate-900' : 'font-semibold text-slate-700' }} truncate text-sm">
                                    {{ $namaTampil ?? 'Nomor Tak Dikenal' }}
                                </p>
                                @if (! $c->pnpp)
                                    <span class="inline-flex flex-shrink-0 rounded-full bg-rose-50 px-1.5 py-0.5 text-[10px] font-semibold text-rose-500 ring-1 ring-rose-100">tak terdaftar</span>
                                @endif
                            </div>
                            <div class="mt-0.5 flex items-center gap-1.5">
                                <p class="truncate text-xs {{ $adaBelum ? 'font-medium text-slate-600' : 'text-slate-400' }}">
                                    {{ $c->isi_terakhir ?? '—' }}
                                </p>
                            </div>
                            <p class="mt-0.5 font-mono text-[11px] text-slate-400">{{ $c->no_hp }}</p>
                        </div>

                        <div class="flex flex-col items-end gap-1.5">
                            @if ($c->waktu_terakhir)
                                <p class="whitespace-nowrap text-[11px] {{ $adaBelum ? 'font-bold text-slate-700' : 'text-slate-400' }}">
                                    @if (optional($c->waktu_terakhir)->isToday())
                                        {{ optional($c->waktu_terakhir)->format('H:i') }}
                                    @elseif (optional($c->waktu_terakhir)->isYesterday())
                                        Kemarin
                                    @else
                                        {{ optional($c->waktu_terakhir)->format('d/m') }}
                                    @endif
                                </p>
                            @endif
                            @if ($adaBelum)
                                <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-emerald-500 px-1.5 text-[10px] font-bold text-white">
                                    {{ $c->belum_dibaca > 99 ? '99+' : number_format($c->belum_dibaca) }}
                                </span>
                            @endif
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="border-t border-slate-100 px-5 py-3">
            {{ $konversasi->links() }}
        </div>
    @endif
</div>