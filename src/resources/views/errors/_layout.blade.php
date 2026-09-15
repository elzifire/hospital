<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Terjadi Kesalahan') — {{ config('app.name', 'RS BHAYANGKARA BOGOR') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        *, *::before, *::after, body { font-family: 'Montserrat', sans-serif; }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-slate-900 via-slate-800 to-sky-900 font-sans antialiased">

    <div class="flex min-h-full items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
        @yield('content')
    </div>

    <script>
        // Tombol "Kembali": balik ke halaman sebelumnya kalau ada riwayat,
        // kalau tidak ada langsung alihkan ke beranda.
        function kembaliAtauBeranda(el) {
            const home = el && el.getAttribute('data-home') ? el.getAttribute('data-home') : '/';
            if (window.history && window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = home;
            }
        }
    </script>
</body>
</html>