@extends('layouts.app')

@section('title', 'Detail Log Aktivitas')
@section('page-title', 'Detail Log Aktivitas')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">

    {{-- ===== Header ===== --}}
    <div>
        <a href="{{ route('admin.activity-log.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-sky-600 hover:text-sky-800">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
            Kembali ke Log Aktivitas
        </a>
        <h2 class="mt-3 text-xl font-bold tracking-tight text-slate-900">{{ ucfirst((string) $activity->description) }}</h2>
        <p class="mt-1 text-sm text-slate-500">
            {{ $activity->created_at?->translatedFormat('l, d F Y — H:i') }}
        </p>
    </div>

    {{-- ===== Info Umum ===== --}}
    @php
        $eventClass = match ($activity->event) {
            'created'  => ['bg-emerald-50 text-emerald-700 ring-emerald-200', 'Dibuat'],
            'deleted'  => ['bg-rose-50 text-rose-700 ring-rose-200', 'Dihapus'],
            'restored' => ['bg-sky-50 text-sky-700 ring-sky-200', 'Dipulihkan'],
            default    => ['bg-amber-50 text-amber-700 ring-amber-200', 'Diubah'],
        };
        $subjectName = $activity->subject
            ? class_basename($activity->subject).' #'.$activity->subject->getKey()
            : ($activity->subject_type
                ? class_basename($activity->subject_type).' #'.(string) $activity->subject_id
                : '—');
    @endphp
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 px-5 py-4">
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $eventClass[0] }}">{{ $eventClass[1] }}</span>
        </div>
        <dl class="grid grid-cols-1 divide-y divide-slate-100 sm:grid-cols-2 sm:divide-x sm:divide-y-0">
            <div class="flex items-center justify-between gap-4 px-5 py-4 text-sm">
                <dt class="font-semibold text-slate-500">Record</dt>
                <dd class="text-right font-bold text-slate-900">{{ $subjectName }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 px-5 py-4 text-sm">
                <dt class="font-semibold text-slate-500">Pelaku</dt>
                <dd class="text-right font-bold text-slate-900">
                    @if ($activity->causer)
                        {{ $activity->causer->name }}
                        <span class="block text-[11px] font-normal text-slate-400">{{ $activity->causer_type }}</span>
                    @else
                        <span class="italic text-slate-400">Sistem / otomatis</span>
                    @endif
                </dd>
            </div>
            @if ($activity->log_name && $activity->log_name !== 'default')
                <div class="flex items-center justify-between gap-4 px-5 py-4 text-sm">
                    <dt class="font-semibold text-slate-500">Log</dt>
                    <dd class="text-right font-bold text-slate-900">{{ $activity->log_name }}</dd>
                </div>
            @endif
        </dl>
    </div>

    {{-- ===== Perubahan Kolom (before & after) ===== --}}
    @php
        $changes = $activity->changes(); // ['attributes' => [...nilai baru], 'old' => [...nilai lama]]
        $nilaiBaru = (array) ($changes['attributes'] ?? []);
        $nilaiLama = (array) ($changes['old'] ?? []);
    @endphp
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Perubahan Data</h3>
            <p class="mt-0.5 text-xs text-slate-400">Hanya kolom yang berubah dicatat. Anotasi — nilai kosong/baru dibuat di event "Dibuat".</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] font-bold uppercase tracking-wide text-slate-400">
                        <th class="px-5 py-3 w-1/4">Kolom</th>
                        <th class="px-5 py-3 w-2/5">Sebelum</th>
                        <th class="px-5 py-3 w-2/5">Sesudah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($nilaiBaru as $kolom => $nilai)
                        @php
                            $tampilBaru = is_array($nilai) ? json_encode($nilai, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $nilai;
                            $nilaiLamaKolom = $nilaiLama[$kolom] ?? null;
                            $tampilLama = is_array($nilaiLamaKolom) ? json_encode($nilaiLamaKolom, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) ($nilaiLamaKolom ?? '');
                            $berubah = array_key_exists($kolom, $nilaiLama);
                            $kosongBaru = $tampilBaru === '' || $tampilBaru === '[]' || $tampilBaru === 'null';
                        @endphp
                        <tr>
                            <td class="px-5 py-3 font-semibold text-slate-700">{{ $kolom }}</td>
                            <td class="px-5 py-3 text-slate-500">
                                @if ($activity->event === 'created')
                                    <span class="text-[11px] italic text-slate-400">(tidak ada — record baru)</span>
                                @else
                                    <span class="{{ $berubah ? 'text-slate-500' : 'text-[11px] italic text-slate-400' }}">
                                        {{ $berubah && $tampilLama !== '' ? $tampilLama : (($activity->event === 'deleted' && $kosongBaru) ? $tampilLama : '—') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $tampilBaru !== '' ? $tampilBaru : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-10 text-center text-sm text-slate-400">
                                Tidak ada perubahan kolom tercatat (mungkin hanya tanggal update otomatis).
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection