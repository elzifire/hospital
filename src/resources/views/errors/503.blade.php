@extends('errors._layout')

@section('title', 'Sedang Dalam Perbaikan')

@section('content')
    @include('errors._card', [
        'kode' => '503',
        'ikon' => '503',
        'warna' => 'amber',
        'judul' => 'Sedang Dalam Perbaikan',
        'deskripsi' => 'Sistem sedang menjalani pemeliharaan singkat. Mohon tunggu sebentar — halaman ini akan coba dibuka lagi otomatis saat sistem pulih.',
        'retryAfter' => $retryAfter ?? null,
        'refresh' => $refresh ?? null,
        'tips' => [
            'Halaman akan dimuat ulang otomatis setelah perbaikan selesai.',
            'Lewat beberapa menit, tekan tombol "Coba Lagi" untuk memeriksa.',
            'Kalau pekerjaan kantor mendesak, hubungi admin sistem.',
        ],
        'aksi' => [
            ['label' => 'Coba Lagi', 'cara' => 'reload', 'tipe' => 'utama', 'ikon' => 'reload'],
        ],
    ])
@endsection