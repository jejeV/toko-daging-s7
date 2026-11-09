<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <title>Toko Daging — Kasir & Manajemen Stok</title>
</head>
<body class="min-h-screen flex flex-col">

    <header class="border-b border-line">
        <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded bg-accent text-white flex items-center justify-center display text-sm">TD</span>
                <span class="display text-lg">Toko Daging</span>
            </a>
            <div class="flex items-center gap-2">
                <button type="button" data-theme-toggle class="btn-icon" title="Ganti tema" aria-label="Ganti tema">
                    <span class="dark:hidden"><i class="fa-solid fa-moon"></i></span>
                    <span class="hidden dark:inline"><i class="fa-solid fa-sun"></i></span>
                </button>
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-secondary btn-sm">Masuk</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="flex-1">
        <section class="max-w-6xl mx-auto px-6 pt-16 sm:pt-24 pb-16">
            <p class="eyebrow">Kasir · Stok · HPP · SPK</p>
            <h1 class="display text-[56px] sm:text-[88px] lg:text-[112px] leading-[0.88] tracking-[-0.025em] mt-4">
                Segar ditimbang,<br><span class="text-accent">tercatat rapi.</span>
            </h1>
            <p class="mt-8 max-w-xl text-[16px] text-muted leading-relaxed">
                Sistem kasir untuk toko daging: jual per kilo, pantau stok di chiller, hitung HPP moving average, dan temukan produk paling menguntungkan dengan metode SAW.
            </p>
            <div class="mt-10 flex flex-wrap items-center gap-3">
                @auth
                    <a href="{{ route('pos.index') }}" class="btn btn-primary h-11 px-5 text-[14px]">
                        Buka kasir <i class="fa-solid fa-arrow-right text-xs"></i>
                    </a>
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary h-11 px-5 text-[14px]">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary h-11 px-5 text-[14px]">
                        Masuk ke sistem <i class="fa-solid fa-arrow-right text-xs"></i>
                    </a>
                @endauth
            </div>
        </section>

        <section class="border-t border-line">
            <div class="max-w-6xl mx-auto grid sm:grid-cols-2 lg:grid-cols-4 gap-px bg-line border-x border-line">
                @foreach ([
                    ['01', 'Kasir per kilo', 'Qty desimal untuk timbangan, uang pas dan kembalian otomatis.'],
                    ['02', 'Stok & susut', 'Pembelian menambah stok, penyesuaian mencatat susut dan opname.'],
                    ['03', 'HPP moving average', 'Harga pokok diperbarui setiap faktur masuk, laba terkunci per transaksi.'],
                    ['04', 'SPK metode SAW', 'Peringkat produk dari omzet, margin, frekuensi, dan tingkat susut.'],
                ] as [$no, $title, $desc])
                    <div class="bg-canvas p-6">
                        <p class="font-mono text-xs text-accent">{{ $no }}</p>
                        <h2 class="text-[15px] font-semibold mt-3">{{ $title }}</h2>
                        <p class="text-[13px] text-muted mt-1.5 leading-relaxed">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </main>

    <footer class="border-t border-line">
        <div class="max-w-6xl mx-auto px-6 py-6 text-xs text-faint">&copy; {{ date('Y') }} Toko Daging</div>
    </footer>

</body>
</html>
