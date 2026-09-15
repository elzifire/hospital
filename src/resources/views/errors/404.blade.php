@extends('errors._layout')

@section('title', 'Laman Tidak Ditemukan')

@section('content')
    @include('errors._card', [
        'kode' => '404',
        'ikon' => '404',
        'warna' => 'sky',
        'judul' => 'Laman Tidak Ditemukan',
        'deskripsi' => 'Sepertinya alamat halaman yang kamu buka tidak ada, sudah dipindah, atau link yang dipakai tidak berlaku lagi.',
        'tips' => [
            'Periksa kembali alamat yang diketik di browser, pastikan tidak ada yang salah ketik.',
            'Kalau kamu masuk lewat tautan/jadwal lama, coba buka fiturnya dari menu di samping kiri.',
            'Halaman pencarian di atas biasanya bisa membantu menemukan data yang kamu butuhkan.',
        ],
        'aksi' => [
            ['label' => 'Kembali', 'cara' => 'back', 'tipe' => 'sekunder', 'ikon' => 'back'],
            ['label' => 'Ke Beranda', 'cara' => 'link', 'href' => '/', 'tipe' => 'utama', 'ikon' => 'home'],
        ],
    ])
@endsection