@extends('errors._layout')

@section('title', 'Ada Kendala Teknis')

@section('content')
    @include('errors._card', [
        'kode' => '500',
        'ikon' => '500',
        'warna' => 'rose',
        'judul' => 'Lagi Ada Kendala Teknis',
        'deskripsi' => 'Terjadi masalah di bagian dalam sistem. Ini bukan karena kesalahanmu — jangan khawatir, tim teknis akan segera menanganinya.',
        'tips' => [
            'Coba muat ulang halaman ini beberapa saat lagi.',
            'Kalau halaman masih belum pulih, lanjutkan pekerjaan setelah beberapa menit.',
            'Kalau muncul terus, hubungi admin sistem dan sampaikan kode acuan di bawah.',
        ],
        'aksi' => [
            ['label' => 'Muat Ulang', 'cara' => 'reload', 'tipe' => 'utama', 'ikon' => 'reload'],
            ['label' => 'Ke Beranda', 'cara' => 'link', 'href' => '/', 'tipe' => 'sekunder', 'ikon' => 'home'],
        ],
        'acuan' => isset($exception)
            ? strtoupper(substr(hash('crc32', (string) $exception->getMessage().'|'.request()->getPathInfo()), 0, 6))
            : null,
    ])
@endsection