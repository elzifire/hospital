<div id="chat-list-section" data-has-more="{{ $konversasi->hasMorePages() ? '1' : '0' }}">
    @if ($konversasi->isEmpty())
        <div class="px-5 py-14 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                <svg class="h-6 w-6 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-2.029 2.115 2.115 0 0 0-1.661-.586 48.744 48.744 0 0 0-8.983 0 2.115 2.115 0 0 0-1.661.586 2.126 2.126 0 0 0-.476 2.029c.172.714.308 1.44.41 2.174m3.923-2.174a41.03 41.03 0 0 0-.41 2.174c-.058.35-.088.706-.088 1.066v4.286c0 .36.03.716.088 1.066" /></svg>
            </div>
            <p class="mt-3 text-sm font-medium text-slate-500">Belum ada percakapan.</p>
            <p class="mt-0.5 text-xs text-slate-400">Balasan pasien akan muncul di sini secara otomatis.</p>
        </div>
    @else
        <ul id="daftar-konversasi" class="divide-y divide-slate-100">
            @include('admin.respon._chatrows', ['konversasi' => $konversasi])
        </ul>

        @if ($konversasi->hasMorePages())
            <div id="muat-lagi" data-pemuat data-has-more="1" class="min-h-10 px-5 py-2"></div>
        @endif
    @endif
</div>