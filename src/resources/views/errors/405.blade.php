@extends('errors._layout')

@section('title', 'Metode Tidak Diizinkan')

@section('content')
    @include('errors._card', [
        'kode' => '405',
        'ikon' => 'warning',
        'warna' => 'slate',
        'judul' => 'Aksi Tidak Didukung',
        'deskripsi' => 'Cara permintaan ini tidak didukung oleh halaman tersebut. Ini biasanya terjadi saat tombol diklik dua kali atau form dikirim lewat cara yang salah.',
        'tips' => [
            'Tekan tombol "Muat Ulang" lalu coba lagi sekali.',
            'Hindari menekan tombol kirim berulang-ulang.',
            'Kalau terus muncul, hubungi admin sistem.',
        ],
        'aksi' => [
            ['label' => 'Coba Lagi', 'cara' => 'reload', 'tipe' => 'utama', 'ikon' => 'reload'],
            ['label' => 'Ke Beranda', 'cara' => 'link', 'href' => '/', 'tipe' => 'sekunder', 'ikon' => 'home'],
        ],
    ])
@endsection