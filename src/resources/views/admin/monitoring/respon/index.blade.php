@extends('layouts.app')

@section('title', 'Laporan Respon')
@section('page-title', 'Monitoring')

@section('content')
@php
    // Peta warna ubin ikon & badge (mengikuti tone di masing-masing fitur).
    $tileTone = [
        'sky'     => 'bg-sky-50 text-sky-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'violet'  => 'bg-violet-50 text-violet-600',
        'amber'   => 'bg-amber-50 text-amber-600',
        'rose'    => 'bg-rose-50 text-rose-600',
        'slate'   => 'bg-slate-100 text-slate-500',
        'indigo'  => 'bg-indigo-50 text-indigo-600',
        'teal'    => 'bg-teal-50 text-teal-600',
    ];

    $badgeTone = [
        'sky'     => 'bg-sky-50 text-sky-700 ring-sky-200/70',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200/70',
        'violet'  => 'bg-violet-50 text-violet-700 ring-violet-200/70',
        'amber'   => 'bg-amber-50 text-amber-700 ring-amber-200/70',
        'rose'    => 'bg-rose-50 text-rose-700 ring-rose-200/70',
        'slate'   => 'bg-slate-100 text-slate-500 ring-slate-200/70',
        'indigo'  => 'bg-indigo-50 text-indigo-700 ring-indigo-200/70',
        'teal'    => 'bg-teal-50 text-teal-700 ring-teal-200/70',
    ];
@endphp

<div class="space-y-6">
    @include('admin.monitoring.partials._header')
    @include('admin.monitoring.partials._stats')
    @include('admin.monitoring.respon._rekap')
    @include('admin.monitoring.respon._table')
    @include('admin.monitoring.respon._info')
</div>
@endsection