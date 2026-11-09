<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <title>@yield('title', 'Dashboard') — Toko Daging</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    @stack('styles')
</head>
<body class="min-h-screen" x-data="{ sidebarOpen: false }">

    @php
        $navGroups = [
            'Operasional' => [
                ['route' => 'dashboard', 'match' => 'dashboard', 'icon' => 'fa-chart-simple', 'label' => 'Dashboard'],
                ['route' => 'pos.index', 'match' => 'pos.*', 'icon' => 'fa-cash-register', 'label' => 'Kasir'],
            ],
            'Inventori' => [
                ['route' => 'products.index', 'match' => 'products.*', 'icon' => 'fa-box', 'label' => 'Produk'],
                ['route' => 'purchases.index', 'match' => 'purchases.*', 'icon' => 'fa-truck', 'label' => 'Pembelian'],
                ['route' => 'adjustments.index', 'match' => 'adjustments.*', 'icon' => 'fa-scale-balanced', 'label' => 'Penyesuaian Stok'],
            ],
            'Laporan' => [
                ['route' => 'reports.index', 'match' => 'reports.*', 'icon' => 'fa-chart-column', 'label' => 'Laporan Penjualan'],
                ['route' => 'sales.index', 'match' => 'sales.*', 'icon' => 'fa-receipt', 'label' => 'Riwayat Penjualan'],
                ['route' => 'saw.index', 'match' => 'saw.*', 'icon' => 'fa-ranking-star', 'label' => 'SPK Produk (SAW)'],
            ],
        ];
    @endphp

    {{-- Sidebar --}}
    <aside class="no-print fixed inset-y-0 left-0 z-40 w-60 bg-surface border-r border-line flex flex-col transition-transform duration-200 lg:translate-x-0"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

        <div class="h-14 flex items-center justify-between px-5 border-b border-line shrink-0">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                <span class="w-7 h-7 rounded bg-accent text-white flex items-center justify-center display text-[13px]">TD</span>
                <span class="display text-[17px] leading-none">Toko Daging</span>
            </a>
            <button @click="sidebarOpen = false" class="btn-icon lg:hidden" aria-label="Tutup menu">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-5">
            @foreach ($navGroups as $group => $links)
                <div>
                    <p class="eyebrow px-3 mb-1.5">{{ $group }}</p>
                    @foreach ($links as $link)
                        @php $active = request()->routeIs($link['match']); @endphp
                        <a href="{{ route($link['route']) }}"
                           class="relative flex items-center gap-3 h-9 px-3 rounded-md text-[13px] transition-colors
                                  {{ $active ? 'bg-sunken text-ink font-semibold' : 'text-muted hover:text-ink hover:bg-sunken/60' }}">
                            @if ($active)
                                <span class="absolute left-0 top-2 bottom-2 w-[3px] rounded-r bg-accent"></span>
                            @endif
                            <i class="fa-solid {{ $link['icon'] }} w-4 text-center text-[12px] {{ $active ? 'text-accent' : 'text-faint' }}"></i>
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        <div class="border-t border-line p-3 space-y-3 shrink-0">
            <div class="segmented" role="group" aria-label="Tema tampilan">
                <button type="button" data-theme-set="light">Terang</button>
                <button type="button" data-theme-set="dark">Gelap</button>
                <button type="button" data-theme-set="system">Sistem</button>
            </div>

            @auth
                <div class="flex items-center gap-2.5 px-1">
                    <span class="w-8 h-8 rounded-md bg-sunken border border-line flex items-center justify-center text-xs font-bold text-muted shrink-0">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[13px] font-semibold truncate">{{ Auth::user()->name }}</p>
                        <p class="text-[11px] text-faint">{{ Auth::user()->role->label() }}</p>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-icon hover:text-accent!" title="Keluar" aria-label="Keluar">
                            <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </aside>

    {{-- Overlay mobile --}}
    <div x-show="sidebarOpen" @click="sidebarOpen = false" x-transition.opacity x-cloak
         class="no-print fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

    <div class="lg:ml-60 print:ml-0 flex flex-col min-h-screen min-w-0">

        <header class="no-print sticky top-0 z-20 h-14 bg-canvas/85 backdrop-blur border-b border-line flex items-center justify-between gap-3 px-4 sm:px-6">
            <div class="flex items-center gap-3 min-w-0">
                <button @click="sidebarOpen = true" class="btn-icon lg:hidden" aria-label="Buka menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h1 class="text-[15px] font-semibold truncate">@yield('title', 'Dashboard')</h1>
            </div>

            <div class="flex items-center gap-1.5 shrink-0">
                <span class="hidden md:inline text-xs text-faint mr-2 num">{{ now()->locale('id')->translatedFormat('l, j F Y') }}</span>
                <button type="button" data-theme-toggle class="btn-icon" title="Ganti tema" aria-label="Ganti tema">
                    <span class="dark:hidden"><i class="fa-solid fa-moon"></i></span>
                    <span class="hidden dark:inline"><i class="fa-solid fa-sun"></i></span>
                </button>
                @unless (request()->routeIs('pos.*'))
                    <a href="{{ route('pos.index') }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-cash-register text-[11px]"></i>
                        <span class="hidden sm:inline">Buka Kasir</span>
                    </a>
                @endunless
            </div>
        </header>

        <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 py-6 print:p-0 print:max-w-none">
            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" class="callout border-l-ok mb-5 items-start">
                    <i class="fa-solid fa-check text-ok mt-1"></i>
                    <p class="flex-1 text-ink">{{ session('success') }}</p>
                    <button @click="show = false" class="text-faint hover:text-ink" aria-label="Tutup">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div x-data="{ show: true }" x-show="show" class="callout border-l-accent mb-5 items-start">
                    <i class="fa-solid fa-circle-exclamation text-accent mt-1"></i>
                    <p class="flex-1 text-ink">{{ session('error') }}</p>
                    <button @click="show = false" class="text-faint hover:text-ink" aria-label="Tutup">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            @endif

            @if (isset($errors) && $errors->any())
                <div class="callout border-l-warn mb-5 items-start">
                    <i class="fa-solid fa-triangle-exclamation text-warn mt-1"></i>
                    <div>
                        <p class="font-semibold text-ink">Periksa kembali isian berikut:</p>
                        <ul class="mt-1 list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
