@extends('layouts.app')

@section('title', 'Ubah Pesan Follow Up')
@section('page-title', 'Ubah Pesan Follow Up')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Ubah Pesan Follow Up</h1>
            <p class="mt-1 text-sm text-slate-500">
                Perbaiki target, template, atau variabel pesan pada grup ini sebelum dikirim.
            </p>
        </div>
        <a href="{{ route('admin.follow-up.index') }}"
           class="inline-flex items-center gap-2 rounded-lg bg-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-300">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Kembali ke Follow Up
        </a>
    </div>

    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-3 text-sm text-amber-700">
        Grup pesan ini masih <strong>menunggu dikirim</strong>. Simpan perubahan untuk memperbarui
        pesan yang belum terkirim; target yang dilepas akan dibatalkan.
    </div>

    @include('admin.outreach._filter', [
        'actionFilter' => route('admin.follow-up.edit', $kirimGroup),
        'filters' => $filters,
        'satkers' => $satkers,
    ])

    @include('admin.outreach._form', [
        'action' => route('admin.follow-up.update', $kirimGroup),
        'method' => 'PUT',
        'kirimPesan' => false,
        'templates' => $templates,
        'canKirimIds' => $canKirimIds,
        'selectedIds' => $selectedIds,
        'templateId' => $templateId,
        'varsAwal' => $varsAwal,
        'submitLabel' => 'Simpan Perubahan',
    ])
</div>
@endsection