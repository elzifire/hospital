@extends('errors._layout')

@section('title', 'Akses Ditolak')

@section('content')
    @include('errors._card', [
        'kode' => '403',
        'ikon' => '403',
        'warna' => 'rose',
        'judul' => 'Akses Ditolak',
        'deskripsi' => 'Kamu tidak punya izin untuk membuka halaman ini. Halaman tertentu hanya boleh diakses oleh petugas dengan peran tertentu.',
        'tips' => [
            'Pastikan kamu login dengan akun yang tepat (punya hak akses halaman ini).',
            'Kalau merasa seharusnya bisa, hubungi admin sistem untuk pengaturan akses.',
            'Buka menu dari samping kiri untuk melihat fitur yang boleh kamu gunakan.',
        ],
        'aksi' => [
            ['label' => 'Kembali', 'cara' => 'back', 'tipe' => 'sekunder', 'ikon' => 'back'],
            ['label' => 'Ke Beranda', 'cara' => 'link', 'href' => '/', 'tipe' => 'utama', 'ikon' => 'home'],
        ],
    ])
@endsection