<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') — {{ config('app.name', 'RS BHAYANGKARA BOGOR') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>

    [x-cloak]{display:none !important;}
        *, *::before, *::after ,body{
            font-family: 'Montserrat', sans-serif;
            
        }
        /* Animasi hamburger -> X */
        #sidebar-toggle.open .bar-1{ transform: translateY(7px) rotate(45deg); }
        #sidebar-toggle.open .bar-2{ transform: scaleX(0); opacity: 0; }
        #sidebar-toggle.open .bar-3{ transform: translateY(-7px) rotate(-45deg); }
    </style>
</head>
<body class="h-full bg-gray-100 font-sans antialiased">

    <div class="flex h-full">

        @php
            /*
            |----------------------------------------------------------
            | Konfigurasi menu sidebar (grouped)
            | - label  : judul grup
            | - items  : daftar menu (dirender sebagai <li>)
            |     .route  : nama route
            |     .active : kondisi menu aktif
            |     .icon   : array path SVG (heroicons outline)
            |     .roles  : array role yang boleh lihat ([] = semua)
            |
            | Tambah menu baru cukup tambah item di sini.
            |----------------------------------------------------------
            */
            $currentUser = auth()->user();
            $menuGroups = [
                [
                    'label' => 'Utama',
                    'items' => [
                        [
                            'label'  => 'Dashboard',
                            'route'  => 'admin.dashboard',
                            'active' => request()->routeIs('admin.dashboard'),
                            'icon'   => ['m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25'],
                            'roles'  => [],
                        ],
                    ],
                ],
                [
                    'label' => 'Manajemen',
                    'items' => [
                        [
                            'label'  => 'Pengguna',
                            'route'  => 'admin.users.index',
                            'active' => request()->routeIs('admin.users.*'),
                            'icon'   => ['M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
                            'roles'  => ['superadmin', 'admin'],
                        ],
                        [
                            'label'  => 'Role',
                            'route'  => 'admin.roles.index',
                            'active' => request()->routeIs('admin.roles.*'),
                            'icon'   => ['M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z'],
                            'roles'  => ['superadmin'],
                        ],
                        [
                            'label'  => 'Permission',
                            'route'  => 'admin.permissions.index',
                            'active' => request()->routeIs('admin.permissions.*'),
                            'icon'   => [
                                'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z',
                                'M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
                            ],
                            'roles'  => ['superadmin'],
                        ],
                    ],
                ],
                
                [
                    'label' => 'Data Master',
                    'items' => [
                        [
                            'label'      => 'PNPP',
                            'route'      => 'admin.pnpp.index',
                            'active'     => request()->routeIs('admin.pnpp.*'),
                            'icon'       => ['M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z'],
                            'permission' => 'manage master',
                        ],
                        [
                            'label'      => 'Satker',
                            'route'      => 'admin.satker.index',
                            'active'     => request()->routeIs('admin.satker.*'),
                            'icon'       => ['M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21'],
                            'permission' => 'manage master',
                        ],
                        [
                            'label'      => 'Penyakit Kronis',
                            'route'      => 'admin.penyakit.index',
                            'active'     => request()->routeIs('admin.penyakit.*'),
                            'icon'       => ['M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z'],
                            'permission' => 'manage master',
                        ],
                        [
                            'label'      => 'Poli',
                            'route'      => 'admin.poli.index',
                            'active'     => request()->routeIs('admin.poli.*'),
                            'icon'       => ['M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z'],
                            'permission' => 'manage master',
                        ],
                        [
                            'label'      => 'Dokter',
                            'route'      => 'admin.dokter.index',
                            'active'     => request()->routeIs('admin.dokter.*'),
                            'icon'       => ['M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z'],
                            'permission' => 'manage master',
                        ],
                        [
                            'label'      => 'Jadwal',
                            'route'      => 'admin.jadwal.index',
                            'active'     => request()->routeIs('admin.jadwal.*'),
                            'icon'       => ['M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5'],
                            'permission' => 'manage master',
                        ],
                    ],
                ],
                [
                    'label' => 'Akun',
                    'items' => [
                        [
                            'label'  => 'Profil Saya',
                            'route'  => 'admin.profile.edit',
                            'active' => request()->routeIs('admin.profile.*'),
                            'icon'   => ['M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'],
                            'roles'  => [],
                        ],
                    ],
                ],
            ];
        @endphp

        {{-- Sidebar overlay (mobile) --}}
        <div id="sidebar-overlay" onclick="closeSidebar()"
             class="fixed inset-0 z-40 hidden bg-black/50 lg:hidden">
        </div>

        {{-- Sidebar --}}
        <aside id="sidebar"
               class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col overflow-hidden bg-slate-900 text-white transition-all duration-300 ease-in-out lg:static lg:z-auto">

            {{-- Logo / Brand + tombol tutup (mobile) --}}
            <div class="flex h-16 flex-shrink-0 items-center justify-between border-b border-slate-700/70 px-5">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('image/RSB.png') }}" alt="Logo" class="h-10 w-10 rounded-2xl object-cover" />
                    <span class="text-base font-bold">RS BHAYANGKARA BOGOR</span>
                </div>
                <button type="button" onclick="closeSidebar()" title="Tutup menu" aria-label="Tutup menu"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-800 hover:text-white lg:hidden">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Navigation (grouped, dari variable $menuGroups) --}}
            <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5">
                @foreach ($menuGroups as $group)
                    @php
                        $visibleItems = collect($group['items'])->filter(function ($item) use ($currentUser) {
                            $allowedByRole       = empty($item['roles']) || $currentUser->hasAnyRole($item['roles']);
                            $allowedByPermission = empty($item['permission']) || $currentUser->can($item['permission']);

                            return $allowedByRole && $allowedByPermission;
                        });
                    @endphp

                    @if ($visibleItems->isNotEmpty())
                        <div>
                            <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-widest text-slate-500">{{ $group['label'] }}</p>
                            <ul class="space-y-1">
                                @foreach ($visibleItems as $item)
                                    <li>
                                        <a href="{{ route($item['route']) }}" onclick="closeSidebar()" title="{{ $item['label'] }}"
                                           class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $item['active'] ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                            <svg class="h-5 w-5 flex-shrink-0 transition-colors {{ $item['active'] ? '' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                @foreach ($item['icon'] as $d)
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $d }}" />
                                                @endforeach
                                            </svg>
                                            <span>{{ $item['label'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            </nav>

            {{-- User info at bottom --}}
            <div class="border-t border-slate-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-sky-600 text-sm font-bold uppercase">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">{{ Auth::user()->name }}</p>
                        <p class="truncate text-xs text-slate-400">{{ Auth::user()->getRoleNames()->first() }}</p>
                    </div>
                </div>
            </div>
        </aside>

        {{-- Main content --}}
        <div class="flex flex-1 flex-col overflow-hidden">
            {{-- Top bar --}}
            <header class="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 shadow-sm lg:px-6">
                {{-- Toggle sidebar: drawer di mobile, collapse di desktop --}}
                <button type="button" id="sidebar-toggle" onclick="toggleSidebar()" title="Buka/tutup sidebar" aria-label="Toggle sidebar"
                        class="rounded-lg p-2 text-sky-600 transition hover:bg-sky-50 hover:text-sky-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-400">
                    <span class="flex h-6 w-6 flex-col items-center justify-center gap-[5px]">
                        <span class="bar-1 block h-0.5 w-5 rounded-full bg-current transition-all duration-300"></span>
                        <span class="bar-2 block h-0.5 w-5 rounded-full bg-current transition-all duration-300"></span>
                        <span class="bar-3 block h-0.5 w-5 rounded-full bg-current transition-all duration-300"></span>
                    </span>
                </button>

                <h1 class="text-lg font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h1>

                {{-- User dropdown --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 transition">
                        <span class="hidden sm:inline">{{ Auth::user()->name }}</span>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    <div x-show="open" @click.away="open = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-48 rounded-lg bg-white py-1 shadow-lg ring-1 ring-gray-200 z-50">
                        <div class="border-b border-gray-100 px-4 py-2">
                            <p class="text-sm font-medium text-gray-900">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                        </div>
                        <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
                            @csrf
                        </form>
                        <button type="button" onclick="confirmLogout()" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition w-full text-left cursor-pointer">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                            </svg>
                            Logout
                        </button>
                    </div>
                </div>
            </header>

            {{-- Page content --}}
            <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Alpine.js CDN --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Form global untuk aksi DELETE (dipakai semua halaman admin) --}}
    <form id="delete-form" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    <script>
        // ===== Sidebar (vanilla JS — works di semua device) =====
        let sidebarOpen = false;

        function isMobile() { return window.innerWidth < 1024; }

        function applySidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const toggle  = document.getElementById('sidebar-toggle');
            if (!sidebar) return;

            const openCls   = ['translate-x-0', 'lg:ml-0'];
            const closedCls = ['-translate-x-full', 'lg:translate-x-0', 'lg:-ml-64'];

            sidebar.classList.remove(...openCls, ...closedCls);
            sidebar.classList.add(...(sidebarOpen ? openCls : closedCls));

            if (overlay) overlay.classList.toggle('hidden', !(sidebarOpen && isMobile()));
            if (toggle)  toggle.classList.toggle('open', sidebarOpen);
            document.body.classList.toggle('overflow-hidden', sidebarOpen && isMobile());
        }

        function toggleSidebar() { sidebarOpen = !sidebarOpen; applySidebar(); }
        function closeSidebar() { sidebarOpen = false; applySidebar(); }

        // Inisialisasi: tertutup di mobile, terbuka di desktop
        sidebarOpen = !isMobile();
        applySidebar();

        window.addEventListener('resize', applySidebar);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeSidebar(); });

        // ===== SweetAlert: Toast notifikasi (sukses / error dari session flash) =====
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });

        function notify(icon, title) {
            Toast.fire({ icon: icon, title: title });
        }

        @if (session('success'))
            document.addEventListener('DOMContentLoaded', () => notify('success', @js(session('success'))));
        @endif
        @if (session('error'))
            document.addEventListener('DOMContentLoaded', () => notify('error', @js(session('error'))));
        @endif

        // ===== Helper konfirmasi + submit form DELETE terpusat =====
        function confirmSubmit(url, options = {}) {
            Swal.fire({
                title: options.title ?? 'Apakah Anda yakin?',
                html: options.html ?? '',
                icon: options.icon ?? 'warning',
                showCancelButton: true,
                confirmButtonColor: options.danger === false ? '#0284c7' : '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: options.confirmText ?? 'Ya, lanjutkan',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                focusCancel: true,
                customClass: {
                    popup: 'rounded-2xl font-sans shadow-xl',
                    confirmButton: 'rounded-lg font-semibold px-5 py-2.5',
                    cancelButton: 'rounded-lg font-medium px-5 py-2.5'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('delete-form');
                    form.action = url;
                    form.submit();
                }
            });
        }

        // ===== Info / peringatan tanpa aksi =====
        function infoDialog(title, html, icon = 'error') {
            Swal.fire({
                title: title,
                html: html,
                icon: icon,
                confirmButtonColor: '#0284c7',
                confirmButtonText: 'Mengerti',
                customClass: { popup: 'rounded-2xl font-sans shadow-xl' }
            });
        }

        function confirmLogout() {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Sesi Anda akan diakhiri.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0284c7',
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Logout!',
                cancelButtonText: 'Batal',
                background: '#ffffff',
                customClass: {
                    popup: 'rounded-xl font-sans'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('logout-form').submit();
                }
            });
        }
    </script>
</body>
</html>

