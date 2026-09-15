@extends('errors._layout')

@section('title', 'Terlalu Banyak Permintaan')

@section('content')
    @include('errors._card', [
        'kode' => '429',
        'ikon' => 'clock',
        'warna' => 'amber',
        'judul' => 'Sedikit Terlalu Cepat',
        'deskripsi' => 'Sistem menerima terlalu banyak permintaan dalam waktu singkat dan butuh istirahat sebentar sebelum bisa dilanjutkan.',
        'tips' => [
            'Tunggu beberapa saat, sistem akan normal kembali dengan sendirinya.',
            'Hindari menekan tombol atau me-muat ulang halaman berulang-ulang.',
            'Kalau muncul terus-menerus, hubungi admin sistem.',
        ],
        'aksi' => [
            ['label' => 'Coba Lagi', 'cara' => 'reload', 'tipe' => 'utama', 'ikon' => 'reload'],
            ['label' => 'Ke Beranda', 'cara' => 'link', 'href' => '/', 'tipe' => 'sekunder', 'ikon' => 'home'],
        ],
    ])
@endsection