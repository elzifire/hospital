@extends('layouts.app')

@section('title', 'Registrasi PNPP')
@section('page-title', 'Registrasi PNPP')

@section('content')
@php
    $statuses = \App\Models\RegisterPnpp::STATUS_LABEL;
@endphp
<div class="space-y-6">

    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Registrasi PNPP</h2>
            <p class="mt-0.5 text-sm text-slate-500">Pendaftaran online calon peserta — verifikasi & setujui pendaftar yang masuk.</p>
        </div>
        <a href="{{ route('register-pnpp.create') }}" target="_blank"
           class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
            Lihat Form Publik
        </a>
    </div>

    {{-- ===== Statistik status ===== --}}
    @php
        $statCards = [
            ['label' => 'Total Pendaftar', 'value' => $counts['semua'], 'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z', 'color' => 'sky'],
            ['label' => 'Belum Disetujui', 'value' => $counts['belum disetujui'], 'icon' => 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z', 'color' => 'amber'],
            ['label' => 'Disetujui', 'value' => $counts['disetujui'], 'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'color' => 'emerald'],
        ];
        $statColor = [
            'sky'     => ['bg-sky-50',    'text-sky-600'],
            'amber'   => ['bg-amber-50',  'text-amber-600'],
            'emerald' => ['bg-emerald-50','text-emerald-600'],
        ];
    @endphp
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach ($statCards as $stat)
            @php $c = $statColor[$stat['color']]; @endphp
            <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-xs ring-1 ring-slate-200">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg {{ $c[0] }} {{ $c[1] }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $stat['icon'] }}" /></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-lg font-extrabold leading-tight tabular-nums text-slate-900">{{ $stat['value'] }}</p>
                    <p class="truncate text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ $stat['label'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== Tabel ===== --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/60 p-4 lg:flex-row lg:items-center lg:justify-between">
            <form method="GET" action="{{ route('admin.register-pnpp.index') }}" class="flex w-full flex-col gap-3 sm:flex-row lg:max-w-3xl">
                <div class="relative flex-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    </div>
                    <input name="search" value="{{ request('search') }}" type="text"
                           class="block w-full rounded-xl border-0 bg-white py-2.5 pl-10 pr-9 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500"
                           placeholder="Cari nama, NIK, NIP, atau no. HP...">
                </div>
                <select name="poli" onchange="this.form.submit()" {{ $batasiPoli ? 'disabled' : '' }}
                        class="rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                    <option value="">
                        {{ $batasiPoli ? $polis->firstWhere('id', $poliAktif)?->nama : 'Semua Poli' }}
                    </option>
                    @foreach ($polis as $poliOpt)
                        <option value="{{ $poliOpt->id }}" @selected($poliId === (string) $poliOpt->id)>{{ $poliOpt->nama }}</option>
                    @endforeach
                </select>
                <select name="status" onchange="this.form.submit()"
                        class="rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500 cursor-pointer">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $k => $label)
                        <option value="{{ $k }}" @selected($status === $k)>{{ $label }}</option>
                    @endforeach
                </select>
                @if (request('search') || $status || (! $batasiPoli && $poliId))
                    <a href="{{ route('admin.register-pnpp.index') }}"
                       class="inline-flex items-center justify-center rounded-lg bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-200">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] text-left">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                        <th class="px-6 py-3.5 font-semibold">Pendaftar</th>
                        <th class="px-6 py-3.5 font-semibold">Satker</th>
                        <th class="px-6 py-3.5 font-semibold">Poli Tujuan</th>
                        <th class="px-6 py-3.5 font-semibold">Di PNPP</th>
                        <th class="px-6 py-3.5 font-semibold">Rencana Kunjungan</th>
                        <th class="px-6 py-3.5 font-semibold">Status</th>
                        <th class="px-6 py-3.5 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($registers as $r)
                        @php
                            $jmlPoli = $r->polis->count();
                            $jmlSetuju = $r->polis->filter(fn ($p) => $p->pivot->approved_at !== null)->count();
                            $semuaDisetujui = $jmlPoli > 0 && $jmlSetuju === $jmlPoli;
                            $approved = $r->status === \App\Models\RegisterPnpp::STATUS_DISETUJUI;
                            $t = $tanda[$r->id] ?? ['ada' => false, 'pnpp_id' => null];
                            $poliSayaDisetujui = $poliAktif !== null
                                && $r->polis->contains(fn ($p) => (int) $p->id === (int) $poliAktif && $p->pivot->approved_at !== null);
                        @endphp
                        <tr class="group hover:bg-sky-50/40">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sm font-bold text-sky-600">
                                        {{ strtoupper(substr($r->nama, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-900">{{ $r->nama }}</p>
                                        <p class="text-xs text-slate-400">
                                            {{ $r->jabatan }}
                                            @if ($r->nik) · NIK {{ $r->nik }}@endif
                                            @if ($r->nip) · NIP {{ $r->nip }}@endif
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if ($r->satker)
                                    <span class="text-sm font-medium text-slate-700">{{ $r->satker->nama }}</span>
                                @else
                                    <span class="text-xs italic text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col items-start gap-1">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($r->polis as $poli)
                                            @php $ok = $poli->pivot->approved_at !== null; @endphp
                                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold ring-1 {{ $ok ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-50 text-slate-500 ring-slate-200' }}">
                                                @if ($ok)
                                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                                @else
                                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5" /></svg>
                                                @endif
                                                {{ $poli->nama }}
                                            </span>
                                        @endforeach
                                    </div>
                                    @if ($jmlPoli > 0)
                                        <span class="text-[11px] font-semibold {{ $semuaDisetujui ? 'text-emerald-600' : 'text-slate-400' }}">{{ $jmlSetuju }}/{{ $jmlPoli }} poli disetujui</span>
                                    @endif
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if ($t['ada'])
                                    <a href="{{ route('admin.pnpp.edit', $t['pnpp_id']) }}"
                                       class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200 transition hover:bg-emerald-100">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                        Sudah Ada
                                    </a>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-3 py-1 text-xs font-bold text-slate-500 ring-1 ring-slate-200">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                        Baru
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <p class="text-sm font-semibold tabular-nums text-slate-800">{{ $r->rencana_tanggal_kunjungan?->translatedFormat('d M Y') }}</p>
                                <p class="text-xs text-slate-400">Jam {{ $r->rencana_jam_kunjungan }}</p>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold {{ $approved ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $approved ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                    {{ $statuses[$r->status] ?? $r->status }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if ($poliAktif !== null)
                                        {{-- Akun poli: setujui/batalkan bagian polinya saja. --}}
                                        @if ($poliSayaDisetujui)
                                            <form method="POST" action="{{ route('admin.register-pnpp.unapprove', $r) }}"
                                                  onsubmit="return confirm('Batalkan persetujuan poli Anda untuk {{ $r->nama }}?')">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" title="Batalkan Persetujuan Poli Saya"
                                                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-bold text-amber-600 transition-all hover:bg-amber-50">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                                    Batalkan
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.register-pnpp.approve', $r) }}"
                                                  onsubmit="return confirm('Setujui {{ $r->nama }} untuk poli Anda?')">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" title="Setujui untuk Poli Saya"
                                                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-bold text-emerald-600 transition-all hover:bg-emerald-50">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                    Setujui
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        {{-- Admin/superadmin: setujui semua atau batalkan semua. --}}
                                        @if (! $semuaDisetujui)
                                            <form method="POST" action="{{ route('admin.register-pnpp.approve', $r) }}"
                                                  onsubmit="return confirm('Setujui {{ $r->nama }} untuk semua poli tujuan?')">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" title="Setujui Semua Poli"
                                                        class="rounded-lg p-2 text-slate-400 transition-all hover:bg-emerald-50 hover:text-emerald-600">
                                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.register-pnpp.unapprove', $r) }}"
                                                  onsubmit="return confirm('Batalkan persetujuan {{ $r->nama }} untuk semua poli?')">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" title="Batalkan Semua Poli"
                                                        class="rounded-lg p-2 text-slate-400 transition-all hover:bg-amber-50 hover:text-amber-600">
                                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                    <a href="{{ route('admin.register-pnpp.show', $r) }}" title="Lihat Detail"
                                       class="rounded-lg p-2 text-slate-400 transition-all hover:bg-sky-50 hover:text-sky-600">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 ring-8 ring-slate-50">
                                        <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M4.5 5.25h15a2.25 2.25 0 0 1 2.25 2.25v9.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25v-9.5A2.25 2.25 0 0 1 4.5 5.25Z" /></svg>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-900">Tidak ada pendaftaran</h3>
                                    <p class="mt-1 text-sm text-slate-500">Belum ada pendaftaran yang cocok dengan pencarian / filter ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($registers->hasPages())
            <div class="border-t border-slate-100 bg-slate-50/60 px-6 py-4">
                {{ $registers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection