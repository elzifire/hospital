@extends('layouts.app')

@section('title', 'Log Aktivitas')
@section('page-title', 'Log Aktivitas')

@section('content')
<div class="space-y-6">

    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Log Aktivitas</h2>
            <p class="mt-0.5 text-sm text-slate-500">
                Jejak perubahan data (before & after) yang tercatat otomatis dari seluruh modul.
            </p>
        </div>
    </div>

    {{-- ===== Filter ===== --}}
    <form method="GET" action="{{ route('admin.activity-log.index') }}"
          class="flex flex-wrap items-center gap-3 rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-200">
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <span class="font-medium">Event:</span>
            <select name="event" class="rounded-lg border-0 bg-slate-50 px-3 py-2 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500">
                <option value="">Semua</option>
                @foreach (['created' => 'Dibuat', 'updated' => 'Diubah', 'deleted' => 'Dihapus', 'restored' => 'Dipulihkan'] as $nilai => $label)
                    <option value="{{ $nilai }}" @selected(request('event') === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <span class="font-medium">Log:</span>
            <select name="log" class="rounded-lg border-0 bg-slate-50 px-3 py-2 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-sky-500">
                <option value="">Semua</option>
                @foreach ($logNames as $nama)
                    <option value="{{ $nama }}" @selected(request('log') === $nama)>{{ $nama }}</option>
                @endforeach
            </select>
        </label>

        <label class="flex min-w-[220px] flex-1 items-center gap-2 text-sm text-slate-600">
            <span class="font-medium">Cari:</span>
            <input type="search" name="cari" value="{{ request('cari') }}"
                   class="w-full rounded-lg border-0 bg-slate-50 px-3 py-2 text-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500"
                   placeholder="Deskripsi, jenis record, nama log...">
        </label>

        <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">
            Filter
        </button>
        @if (request()->hasAny(['event', 'log', 'cari']))
            <a href="{{ route('admin.activity-log.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700">Reset</a>
        @endif
    </form>

    {{-- ===== Tabel ===== --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 bg-slate-50/60 px-4 py-3 text-sm text-slate-500">
            Menampilkan <span class="font-bold text-slate-700">{{ $activities->count() }}</span> aktivitas terbaru
            (maks. 500) — urut terbaru.
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] font-bold uppercase tracking-wide text-slate-400">
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Deskripsi</th>
                        <th class="px-4 py-3">Record</th>
                        <th class="px-4 py-3">Pelaku</th>
                        <th class="px-4 py-3 text-right">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($activities as $activity)
                        @php
                            $eventClass = match ($activity->event) {
                                'created'  => ['bg-emerald-50 text-emerald-700 ring-emerald-200', 'baru'],
                                'deleted'  => ['bg-rose-50 text-rose-700 ring-rose-200', 'dihapus'],
                                'restored' => ['bg-sky-50 text-sky-700 ring-sky-200', 'dipulihkan'],
                                default    => ['bg-amber-50 text-amber-700 ring-amber-200', 'diubah'],
                            };
                            $subjectName = $activity->subject
                                ? class_basename($activity->subject).' #'.$activity->subject->getKey()
                                : ($activity->subject_type
                                    ? class_basename($activity->subject_type).' #'.(string) $activity->subject_id
                                    : '—');
                        @endphp
                        <tr class="transition hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">
                                {{ $activity->created_at?->timezone(optional(auth()->user())->timezone ?? config('app.timezone'))->translatedFormat('d M Y, H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-bold ring-1 {{ $eventClass[0] }}">
                                    {{ ucfirst((string) $eventClass[1]) }}
                                </span>
                                <span class="ml-2 text-slate-700">{{ $activity->description }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-semibold text-slate-600">{{ $subjectName }}</span>
                                @if ($activity->log_name && $activity->log_name !== 'default')
                                    <div class="text-[11px] text-slate-400">{{ $activity->log_name }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($activity->causer)
                                    <span class="text-xs text-slate-600">{{ $activity->causer->name }}</span>
                                @else
                                    <span class="text-xs italic text-slate-400">Sistem / otomatis</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <a href="{{ route('admin.activity-log.show', $activity) }}"
                                   class="inline-flex items-center gap-1 text-xs font-bold text-sky-600 hover:text-sky-800">
                                    Lihat
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-400">
                                Belum ada aktivitas yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection