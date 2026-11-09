<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <title>Masuk — Toko Daging</title>
</head>
<body class="min-h-screen grid lg:grid-cols-[1fr_minmax(420px,520px)]">

    {{-- Papan nama --}}
    <aside class="hidden lg:flex flex-col justify-between bg-accent text-white p-10">
        <a href="{{ url('/') }}" class="flex items-center gap-2.5">
            <span class="w-8 h-8 rounded bg-white text-accent flex items-center justify-center display text-sm">TD</span>
            <span class="display text-lg">Toko Daging</span>
        </a>

        <div>
            <p class="display text-[72px] xl:text-[88px] leading-[0.9] tracking-[-0.02em]">Segar<br>ditimbang,<br>tercatat.</p>
            <p class="mt-6 max-w-md text-white/80 text-[15px] leading-relaxed">
                Kasir, stok per kilo, HPP moving average, dan peringkat produk terbaik dalam satu sistem.
            </p>
        </div>

        <p class="text-xs text-white/60">&copy; {{ date('Y') }} Toko Daging</p>
    </aside>

    {{-- Form --}}
    <main class="flex flex-col justify-center px-6 py-10 sm:px-12 bg-canvas">
        <div class="w-full max-w-sm mx-auto">
            <a href="{{ url('/') }}" class="lg:hidden flex items-center gap-2.5 mb-10">
                <span class="w-8 h-8 rounded bg-accent text-white flex items-center justify-center display text-sm">TD</span>
                <span class="display text-lg">Toko Daging</span>
            </a>

            <h1 class="display text-[30px] leading-tight">Masuk</h1>
            <p class="text-[13px] text-muted mt-1">Gunakan akun toko untuk membuka kasir.</p>

            @if (session('success'))
                <div class="callout border-l-ok mt-6">
                    <i class="fa-solid fa-check text-ok mt-1"></i>
                    <p class="text-ink">{{ session('success') }}</p>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="mt-8 space-y-5">
                @csrf
                <div>
                    <label for="email" class="label">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                        placeholder="nama@tokodaging.test" class="field h-10">
                    @error('email')
                        <p class="mt-1.5 text-xs text-accent">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="label">Kata sandi</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••" class="field h-10">
                </div>

                <label class="flex items-center gap-2 text-[13px] text-muted cursor-pointer select-none">
                    <input type="checkbox" name="remember" class="check">
                    Ingat saya
                </label>

                <button type="submit" class="btn btn-primary w-full h-10">Masuk</button>
            </form>

            @if ($demoUsers->isNotEmpty())
                <div class="mt-10">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="eyebrow">Akun demo</span>
                        <span class="flex-1 h-px bg-line"></span>
                    </div>
                    <div class="panel divide-y divide-line">
                        @foreach ($demoUsers as $user)
                            <a href="{{ route('quick-login', $user) }}" class="flex items-center justify-between gap-3 px-4 h-11 text-[13px] hover:bg-sunken/60 transition-colors group">
                                <span class="font-medium">{{ $user->name }}</span>
                                <span class="flex items-center gap-2 text-xs text-faint">
                                    {{ $user->role->label() }}
                                    <i class="fa-solid fa-arrow-right text-[10px] group-hover:text-accent transition-colors"></i>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="segmented mt-10 max-w-[240px]" role="group" aria-label="Tema tampilan">
                <button type="button" data-theme-set="light">Terang</button>
                <button type="button" data-theme-set="dark">Gelap</button>
                <button type="button" data-theme-set="system">Sistem</button>
            </div>
        </div>
    </main>

</body>
</html>
