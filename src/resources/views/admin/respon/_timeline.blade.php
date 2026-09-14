@if ($timeline->isEmpty())
    <p class="py-12 text-center text-sm text-slate-400">Belum ada pesan tersimpan untuk nomor ini.</p>
@else
    <div class="space-y-3">
        @foreach ($timeline as $t)
            @if (($t['arah'] ?? '') === 'keluar')
                {{-- Pesan keluar (broadcast RS / balasan petugas) --}}
                <div class="flex justify-end" title="{{ isset($t['status']) ? 'Status: '.$t['status'].(isset($t['jenis']) ? ' · '.$t['jenis'] : '') : '' }}">
                    <div class="max-w-[80%] rounded-xl rounded-tr-sm bg-[#dcf8c6] px-4 py-2.5 shadow-sm">
                        <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-800">{{ $t['isi'] }}</p>
                        <p class="mt-1 text-right text-[10px] text-slate-400">
                            {{ $t['waktu']->format('d M H:i') }}
                            @isset($t['status']) · {{ ucfirst($t['status']) }} @endisset
                        </p>
                    </div>
                </div>
            @else
                {{-- Balasan pasien --}}
                <div class="flex justify-start">
                    <div class="max-w-[80%] rounded-xl rounded-tl-sm bg-white px-4 py-2.5 shadow-sm">
                        <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-800">{{ $t['isi'] }}</p>
                        <p class="mt-1 text-left text-[10px] text-slate-400">{{ $t['waktu']->format('d M H:i') }}</p>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endif