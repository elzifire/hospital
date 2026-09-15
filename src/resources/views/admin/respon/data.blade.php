@extends('layouts.app')

@section('title', 'Data Respon')
@section('page-title', 'Respon Pasien')

@section('content')
<div class="space-y-6">
    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Data Respon</h2>
            <p class="mt-0.5 text-sm text-slate-500">Balasan yang dicatat manual atau diimpor dari Excel.</p>
        </div>
        <a href="{{ route('admin.respon.manual') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Input Manual
        </a>
    </div>

    @include('admin.respon._flash')

    @include('admin.respon._subnav', ['active' => 'data'])

    <div class="space-y-4">
        <div class="flex items-start gap-3 rounded-2xl bg-sky-50 px-5 py-4 text-sm text-sky-800 ring-1 ring-sky-200">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-.118 5.118h.007v.007h-.007v-.007ZM3 13.125A9.375 9.375 0 0 1 12.375 3.75h1.5A6.375 6.375 0 0 1 20.25 10.125v1.5a9.375 9.375 0 0 1-9.375 9.375H9.375a6.375 6.375 0 0 1-6.375-6.375Z" /></svg>
            <p>Data balasan yang dicatat manual atau diimpor dari Excel — <span class="font-semibold">terpisah</span> dari balasan otomatis WhatsApp (webhook).</p>
        </div>

        <div class="grid grid-cols-3 gap-3">
            @foreach ([
                ['label' => 'Total Data', 'value' => number_format($totalRespon), 'cls' => 'bg-sky-50 text-sky-600'],
                ['label' => 'Input Manual', 'value' => number_format($totalManual), 'cls' => 'bg-emerald-50 text-emerald-600'],
                ['label' => 'Dari Import', 'value' => number_format($totalImport), 'cls' => 'bg-violet-50 text-violet-600'],
            ] as $s)
                <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-xs ring-1 ring-slate-200">
                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg {{ $s['cls'] }}">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-lg font-extrabold leading-tight tabular-nums text-slate-900">{{ $s['value'] }}</p>
                        <p class="truncate text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ $s['label'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-bold text-slate-900">Data Respon (Manual & Import)</h3>
            </div>

            <form method="GET" action="{{ route('admin.respon.data') }}"
                  class="flex flex-wrap items-end gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4">
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Cari</label>
                    <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Nama / NRP-NIP / nomor / satker / isi…"
                           class="w-64 rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">Cari</button>
                    @if (filled($filters['q']))
                        <a href="{{ route('admin.respon.data') }}" class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-300">Reset</a>
                    @endif
                </div>
            </form>

            @if ($dataRespon->isEmpty())
                <div class="px-5 py-12 text-center">
                    <p class="text-sm font-medium text-slate-500">Belum ada data. Tambah via halaman "Input Manual" atau unduh template di halaman "Import Excel".</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="px-5 py-3">Waktu</th>
                                <th class="px-5 py-3">Sumber</th>
                                <th class="px-5 py-3">Data</th>
                                <th class="px-5 py-3">Isi / Satker</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($dataRespon as $d)
                                <tr class="hover:bg-slate-50/60 align-top">
                                    <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-500">
                                        {{ $d->waktu?->format('d M Y, H:i') }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3">
                                        @if ($d->sumber === \App\Models\ResponManual::SUMBER_MANUAL)
                                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-600">MANUAL</span>
                                        @else
                                            <span class="rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-semibold text-violet-600">IMPORT</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        <p class="font-semibold text-slate-800">{{ $d->nama ?? '—' }}</p>
                                        @if ($d->nrp_nip)
                                            <p class="font-mono text-xs text-slate-400">NRP/NIP: {{ $d->nrp_nip }}</p>
                                        @endif
                                        <p class="font-mono text-xs text-slate-400">{{ $d->no_hp }}</p>
                                    </td>
                                    <td class="max-w-[300px] px-5 py-3">
                                        @if ($d->satker)
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-sky-600">{{ $d->satker }}</p>
                                        @endif
                                        <p class="text-xs text-slate-600" title="{{ $d->isi }}">{{ \Illuminate\Support\Str::limit($d->isi, 110) }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right">
                                        <form method="POST" action="{{ route('admin.respon.manual-destroy', $d) }}"
                                              onsubmit="return confirm('Hapus data respon ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-100">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-5 py-3">
                    {{ $dataRespon->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection