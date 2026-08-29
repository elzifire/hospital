<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Login') — {{ config('app.name', 'RS BHAYANGKARA BOGOR') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak]{display:none !important;}
       
    </style>
</head>
<body class="h-full bg-gradient-to-br from-slate-900 via-slate-800 to-sky-900 font-sans antialiased">

    <div class="flex min-h-full items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        @yield('content')
    </div>

</body>
</html>

