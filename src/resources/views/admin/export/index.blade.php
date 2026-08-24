@extends('layouts.app')

@section('title', 'Export ' . $config['label'])
@section('page-title', 'Export ' . $config['label'])

@section('content')
@php $indexRoute = 'admin.' . $entity . '.index'; @endphp

<div class="mx-auto max-w-3xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <span class="text-slate-500">Data Master</span>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <a href="{{ route($indexRoute) }}" class="rounded transition hover:text-sky-600">{{ $config['label'] }}</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">Export</span>
        </nav>
        <h2 class="text-xl font-bold tracking-tight text-slate-900">Export {{ $config['label'] }}</h2>
        <p class="mt-0.5 text-sm text-slate-500">Unduh seluruh data {{ strtolower($config['label']) }} dalam format Excel (.xlsx) atau CSV.</p>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 p-6">
            <h3 class="text-base font-bold text-slate-900">Ringkasan</h3>
            <p class="mt-0.5 text-sm text-slate-500">Data yang akan diexport.</p>
        </div>

        <div class="p-6">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-50 text-sky-600">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                </div>
                <div>
                    <p class="text-2xl font-extrabold tabular-nums text-slate-900">{{ $count }}</p>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Total data {{ strtolower($config['label']) }}</p>
                </div>
            </div>

            <div class="mt-6">
                <p class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-400">Kolom yang akan diexport</p>
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($config['headers'] as $header)
                        <code class="rounded-md bg-slate-100 px-2 py-1 font-mono text-[11px] text-slate-600">{{ $header }}</code>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-4 rounded-b-2xl border-t border-slate-100 bg-slate-50/70 p-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Template</span>
                <a href="{{ route('admin.master.template', ['entity' => $entity, 'format' => 'xlsx']) }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
                    <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    Excel
                </a>
                <a href="{{ route('admin.master.template', ['entity' => $entity, 'format' => 'csv']) }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    CSV
                </a>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.master.export.download', ['entity' => $entity, 'format' => 'csv']) }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    Download CSV
                </a>
                <a href="{{ route('admin.master.export.download', ['entity' => $entity, 'format' => 'xlsx']) }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    Download Excel
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
