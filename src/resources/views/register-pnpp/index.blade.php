<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pendaftaran Terkirim — RS Bhayangkara Bogor</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none !important;}</style>
  </head>
  <body class="bg-slate-100 antialiased">

    <div class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6 lg:px-8">

        @php
            $chipClass = 'inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 ring-1 ring-sky-200';
            $labelClass = 'mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400';
            $valueClass = 'text-sm font-bold text-slate-900';
        @endphp

        <div class="w-full max-w-2xl overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-slate-900/5">

            {{-- ===== Kepala ===== --}}
            <div class="border-b border-slate-100 bg-gradient-to-r from-emerald-500 to-teal-500 px-8 py-8 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/20 ring-4 ring-white/30">
                    <svg class="h-9 w-9 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </div>
                <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-white">Pendaftaran Terkirim!</h1>
                <p class="mt-1.5 text-sm text-emerald-50">Terima kasih. Data Anda sudah kami terima dan akan diverifikasi petugas rumah sakit.</p>
            </div>

            <div class="px-8 py-7">
                <div class="mb-6 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4 text-sm leading-relaxed text-emerald-800">
                    <p class="font-bold text-emerald-900">Apa yang terjadi selanjutnya?</p>
                    <ul class="mt-2 space-y-1.5 text-[13px]">
                        <li class="flex items-start gap-2"><span class="mt-0.5 text-emerald-500">✓</span>Petugas akan memverifikasi data dan tujuan kunjungan Anda.</li>
                        <li class="flex items-start gap-2"><span class="mt-0.5 text-emerald-500">✓</span>Status persetujuan akan diinformasikan melalui WhatsApp/telepon ke <span class="font-bold">{{ $register->no_hp }}</span>.</li>
                        <li class="flex items-start gap-2"><span class="mt-0.5 text-emerald-500">✓</span>Silakan hadir sesuai jadwal yang Anda pilih untuk mendapatkan pelayanan.</li>
                    </ul>
                </div>

                {{-- ===== Ringkasan Pendaftaran ===== --}}
                <h2 class="mb-3 text-sm font-bold uppercase tracking-wide text-slate-500">Ringkasan Pendaftaran</h2>
                <dl class="overflow-hidden rounded-2xl border border-slate-200">
                    <div class="grid grid-cols-1 gap-4 border-b border-slate-100 bg-slate-50/60 p-4 sm:grid-cols-2">
                        <div>
                            <dt class="{{ $labelClass }}">Nama</dt>
                            <dd class="{{ $valueClass }}">{{ $register->nama }}</dd>
                        </div>
                        <div>
                            <dt class="{{ $labelClass }}">Jabatan</dt>
                            <dd class="{{ $valueClass }}">{{ $register->jabatan }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="{{ $labelClass }}">Satker</dt>
                            <dd class="{{ $valueClass }}">{{ $register->satkerNamaTampil() }}</dd>
                        </div>
                        <div>
                            <dt class="{{ $labelClass }}">Rencana Tanggal Kunjungan</dt>
                            <dd class="{{ $valueClass }}">{{ $register->rencana_tanggal_kunjungan?->translatedFormat('d F Y') }}</dd>
                        </div>
                        <div>
                            <dt class="{{ $labelClass }}">Rencana Jam Kunjungan</dt>
                            <dd class="{{ $valueClass }}">{{ $register->rencana_jam_kunjungan }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="{{ $labelClass }}">Poli Tujuan</dt>
                            <dd class="{{ $valueClass }}">
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($register->polis as $poli)
                                        <span class="{{ $chipClass }}">{{ $poli->nama }}</span>
                                    @endforeach
                                </div>
                            </dd>
                        </div>
                        @if ($register->tujuanKunjungans->isNotEmpty() || filled($register->tujuan_lainnya))
                            <div class="sm:col-span-2">
                                <dt class="{{ $labelClass }}">Tujuan Kunjungan</dt>
                                <dd class="{{ $valueClass }}">
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($register->tujuanKunjungans as $tujuan)
                                            <span class="inline-flex items-center rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-700 ring-1 ring-teal-200">{{ $tujuan->nama }}</span>
                                        @endforeach
                                        @if (filled($register->tujuan_lainnya))
                                            <span class="inline-flex items-center rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-700 ring-1 ring-teal-200">{{ $register->tujuan_lainnya }}</span>
                                        @endif
                                    </div>
                                </dd>
                            </div>
                        @endif
                    </div>
                </dl>

                <div class="mt-7 flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
                    <p class="text-xs text-slate-400">Simpan atau catat nomor HP Anda — dipakai untuk pengumuman status persetujuan.</p>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('register-pnpp.create') }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            Daftar Lagi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </body>
</html>
