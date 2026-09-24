<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Login') — {{ config('app.name', 'RS BHAYANGKARA BOGOR') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak]{display:none !important;}
    </style>
</head>
<body class="h-full bg-slate-50 font-sans antialiased">

    {{-- No forced centering/padding here anymore — the login view
         defines its own full-height, two-column layout below. --}}
    <div class="flex min-h-full">
        @yield('content')
    </div>

</body>
</html>