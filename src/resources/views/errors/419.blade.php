@extends('errors._layout')

@section('title', 'Sesi Berakhir')

@section('content')
    @include('errors._card', [
        'kode' => '419',
        'ikon' => 'clock',
        'warna' => 'amber',
        'judul' => 'Sesi Berakhir',
        'deskripsi' => 'Halaman ini terlalu lama dibiarkan terbuka, jadi sistem mengunci sesi demi keamanan. Data yang belum disimpan bisa hilang.',
        'tips' => [
            'Muat ulang halaman, lalu malsuk kembali kalau diminta.',
            'Isi ulang form dan simpan datanya sebelum menutup halaman.',
            'Kalau kamu sedang di tengah pekerjaan, pastikan data penting sudah tersimpan dulu.',
        ],
        'aksi' => [
            ['label' => 'Muat Ulang', 'cara' => 'reload', 'tipe' => 'utama', 'ikon' => 'reload'],
            ['label' => 'Ke Beranda', 'cara' => 'link', 'href' => '/', 'tipe' => 'sekunder', 'ikon' => 'home'],
        ],
    ])
@endsection