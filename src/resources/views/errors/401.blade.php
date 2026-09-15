@extends('errors._layout')

@section('title', 'Perlu Masuk')

@section('content')
    @include('errors._card', [
        'kode' => '401',
        'ikon' => '401',
        'warna' => 'amber',
        'judul' => 'Sesi Belum Aktif',
        'deskripsi' => 'Kamu perlu masuk (login) dulu sebelum bisa membuka halaman ini, atau sesi masukmu sudah berakhir demi keamanan.',
        'tips' => [
            'Masuk kembali dengan akun yang kamu gunakan.',
            'Kalau lupa kata sandi, hubungi admin sistem untuk bantuan.',
        ],
        'aksi' => [
            ['label' => 'Kembali', 'cara' => 'back', 'tipe' => 'sekunder', 'ikon' => 'back'],
            ['label' => 'Masuk / Login', 'cara' => 'link', 'href' => url('/login'), 'tipe' => 'utama', 'ikon' => 'login'],
        ],
    ])
@endsection