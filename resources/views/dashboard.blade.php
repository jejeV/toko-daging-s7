@extends('layouts.app')

@section('title', 'Dashboard')

@use('App\Support\Fmt')

@section('content')
@php
    $hour = now()->hour;
    $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));

    $rupiah = Fmt::rupiah(...);
    $short = Fmt::short(...);

    $perf = $performance;
    $isOwner = Auth::user()->isOwner();
    $kpis = array_filter([
        ['label' => 'Omzet', 'data' => $perf['revenue'], 'format' => $rupiah],
        ['label' => 'Transaksi', 'data' => $perf['orders'], 'format' => fn ($v) => number_format($v, 0, ',', '.')],
        ['label' => 'Rata-rata per transaksi', 'data' => $perf['avgOrder'], 'format' => $rupiah],
        $isOwner ? ['label' => 'Laba kotor', 'data' => $perf['profit'], 'format' => $rupiah] : null,
    ]);

    $daily = $perf['daily'];
    $labelEvery = count($daily) > 20 ? 5 : 2;
    $catTotal = $perf['categories']->sum('revenue') ?: 1;
@endphp

<div class="space-y-6">

    {{-- Sapaan --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <p class="eyebrow">{{ now()->locale('id')->translatedFormat('l, j F Y') }}</p>
            <h2 class="display text-[28px] sm:text-[32px] leading-tight mt-1">{{ $greeting }}, {{ Str::before(Auth::user()->name, ' ') }}.</h2>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('purchases.create') }}" class="btn btn-secondary">
                <i class="fa-solid fa-plus text-[11px]"></i> Catat Pembelian
            </a>
            <a href="{{ route('pos.index') }}" class="btn btn-primary">
                <i class="fa-solid fa-cash-register text-[11px]"></i> Buka Kasir
            </a>
        </div>
    </div>

    {{-- Angka hari ini --}}
    <div class="panel overflow-hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-px bg-line">
        <div class="p-5 bg-surface">
            <p class="eyebrow">Penjualan hari ini</p>
            <p class="display text-[30px] leading-none mt-3">
                <span class="text-base text-faint font-semibold mr-0.5">Rp</span>{{ number_format($todaySalesTotal, 0, ',', '.') }}
            </p>
            <p class="text-xs text-muted mt-2.5">{{ $todaySalesCount }} transaksi</p>
        </div>

        <div class="p-5 bg-surface">
            <p class="eyebrow">Pembelian bulan ini</p>
            <p class="display text-[30px] leading-none mt-3">
                <span class="text-base text-faint font-semibold mr-0.5">Rp</span>{{ number_format($thisMonthPurchasesTotal, 0, ',', '.') }}
            </p>
            <p class="text-xs text-muted mt-2.5">Stok masuk dari supplier</p>
        </div>

        <div class="p-5 bg-surface">
            <p class="eyebrow">Katalog produk</p>
            <p class="display text-[30px] leading-none mt-3">{{ $totalProducts }}</p>
            <p class="text-xs text-muted mt-2.5">{{ $totalCategories }} kategori · {{ $totalSuppliers }} supplier</p>
        </div>

        <div class="p-5 bg-surface min-w-0">
            <p class="eyebrow">Produk terbaik (SAW)</p>
            @if ($topProduct)
                <p class="display text-[20px] leading-tight mt-3 truncate" title="{{ $topProduct['product']->name }}">
                    {{ $topProduct['product']->name }}
                </p>
                <p class="text-xs text-muted mt-2.5">
                    Nilai <span class="font-semibold text-accent-ink">{{ Fmt::qty($topProduct['scaled'] ?? $topProduct['score'] * 5) }} / 5</span>
                    · {{ $topProduct['product']->category->name }}
                </p>
            @else
                <p class="text-[13px] text-faint mt-3">Belum ada skor.</p>
                <a href="{{ route('saw.index') }}" class="link text-xs mt-2 inline-block">Hitung SAW →</a>
            @endif
        </div>
    </div>

    {{-- Performa penjualan --}}
    <section class="panel overflow-hidden" id="performa">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-3 border-b border-line">
            <div>
                <h3 class="panel-title">Performa penjualan</h3>
                <p class="text-xs text-faint mt-0.5">{{ $perf['range'] }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <nav class="inline-grid grid-flow-col p-0.5 rounded-md bg-sunken border border-line" aria-label="Periode">
                    @foreach ($perf['periods'] as $key => $label)
                        <a href="{{ route('dashboard', ['periode' => $key]) }}#performa"
                           @if ($perf['period'] === $key) aria-current="page" @endif
                           class="h-7 px-3 inline-flex items-center rounded text-xs font-semibold transition-colors {{ $perf['period'] === $key ? 'bg-surface text-ink shadow-sm' : 'text-faint hover:text-ink' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </nav>
                @if ($perf['months']->isNotEmpty())
                    <select aria-label="Pilih bulan"
                            onchange="if (this.value) location.href = '{{ route('dashboard') }}?periode=' + this.value + '#performa'"
                            class="field h-8 w-auto text-xs font-semibold {{ $perf['months']->has($perf['period']) ? 'border-accent text-ink' : 'text-muted' }}">
                        <option value="">Pilih bulan…</option>
                        @foreach ($perf['months'] as $ym => $label)
                            <option value="{{ $ym }}" @selected($perf['period'] === $ym)>{{ $label }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
        </div>

        {{-- KPI periode --}}
        <div class="grid grid-cols-2 {{ count($kpis) === 4 ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }} gap-px bg-line border-b border-line">
            @foreach ($kpis as $kpi)
                @php $change = $kpi['data']['change']; @endphp
                <div class="bg-surface px-5 py-4">
                    <p class="text-xs text-muted">{{ $kpi['label'] }}</p>
                    <p class="display text-[22px] leading-tight mt-1">{{ $kpi['format']($kpi['data']['value']) }}</p>
                    <p class="text-[11px] mt-1 text-faint" title="Dibanding {{ $perf['compareLabel'] }}: {{ $kpi['format']($kpi['data']['previous']) }}">
                        @if ($change === null)
                            {{ $perf['comparable'] ? 'Belum ada penjualan di ' : 'Data belum lengkap untuk ' }}{{ $perf['compareLabel'] }}
                        @else
                            <span class="font-semibold {{ $change >= 0 ? 'text-ok' : 'text-accent' }}">
                                <i class="fa-solid {{ $change >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }} text-[9px]"></i>
                                {{ number_format(abs($change), 1, ',', '.') }}%
                            </span>
                            vs {{ $perf['compareLabel'] }}
                        @endif
                    </p>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px]">

            {{-- Omzet harian --}}
            <div class="p-5 min-w-0 lg:border-r border-line">
                <div class="flex items-baseline justify-between gap-3 mb-4">
                    <h4 class="text-[13px] font-semibold">Omzet harian</h4>
                    <span class="flex items-center gap-1.5 text-[11px] text-faint">
                        <span class="w-3 h-3 rounded-sm bg-sunken border border-line"></span> akhir pekan
                    </span>
                </div>

                <x-chart.columns height="h-60" label="Omzet harian" :items="array_map(fn ($d) => [
                    'value' => $d['revenue'],
                    'axis' => $d['day'] === 1 || $d['day'] % $labelEvery === 0 ? $d['day'] : null,
                    'title' => $d['label'],
                    'lines' => [Fmt::rupiah($d['revenue']), $d['orders'].' transaksi'],
                    'shade' => $d['weekend'],
                ], $daily)" />

                <details class="mt-4 group">
                    <summary class="text-xs font-semibold text-muted hover:text-ink cursor-pointer select-none list-none inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-table text-[10px]"></i> Lihat sebagai tabel
                    </summary>
                    <div class="mt-3 max-h-64 overflow-y-auto border border-line rounded-md">
                        <table class="tbl text-xs">
                            <thead><tr><th>Tanggal</th><th class="text-right">Transaksi</th><th class="text-right">Omzet</th></tr></thead>
                            <tbody>
                                @foreach ($daily as $d)
                                    <tr>
                                        <td class="py-1.5!">{{ $d['label'] }}</td>
                                        <td class="py-1.5! text-right num">{{ $d['orders'] }}</td>
                                        <td class="py-1.5! text-right num">{{ $rupiah($d['revenue']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>

            {{-- Kategori & produk teratas --}}
            <div class="border-t lg:border-t-0 border-line divide-y divide-line">
                <div class="p-5">
                    <h4 class="text-[13px] font-semibold mb-4">Omzet per kategori</h4>
                    @if ($perf['categories']->isEmpty())
                        <p class="text-[13px] text-faint">Belum ada penjualan di periode ini.</p>
                    @else
                        <x-chart.bars :items="$perf['categories']->map(fn ($cat) => [
                            'label' => $cat->name,
                            'value' => (float) $cat->revenue,
                            'display' => Fmt::short($cat->revenue),
                            'note' => Fmt::percent($cat->revenue / $catTotal * 100, 0),
                        ])->all()" />
                    @endif
                </div>

                <div class="p-5">
                    <h4 class="text-[13px] font-semibold mb-3">Produk teratas</h4>
                    @forelse ($perf['topProducts'] as $i => $prod)
                        <div class="flex items-center gap-3 py-2 {{ $loop->last ? '' : 'border-b border-line' }}">
                            <span class="display text-[15px] w-4 text-center {{ $i === 0 ? 'text-accent' : 'text-faint' }}">{{ $i + 1 }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-[13px] font-medium truncate">{{ $prod->name }}</p>
                                <p class="text-[11px] text-faint num">{{ rtrim(rtrim(number_format($prod->qty, 2, ',', '.'), '0'), ',') }} {{ $prod->unit }} terjual</p>
                            </div>
                            <span class="text-[13px] num font-semibold">{{ $short($prod->revenue) }}</span>
                        </div>
                    @empty
                        <p class="text-[13px] text-faint">Belum ada penjualan di periode ini.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        {{-- Stok menipis --}}
        <section class="panel lg:col-span-2">
            <div class="panel-head">
                <h3 class="panel-title">Stok menipis</h3>
                <a href="{{ route('products.index', ['stock_status' => 'low']) }}" class="link text-xs">Lihat semua</a>
            </div>

            @forelse ($lowStockProducts as $prod)
                @php
                    $min = (float) $prod->min_stock;
                    $pct = $min > 0 ? max(0, min(100, ((float) $prod->stock / $min) * 100)) : 0;
                    $out = $prod->stock <= 0;
                @endphp
                <div class="px-5 py-3.5 {{ $loop->last ? '' : 'border-b border-line' }}">
                    <div class="flex items-baseline justify-between gap-3">
                        <p class="text-[13px] font-semibold truncate">{{ $prod->name }}</p>
                        <p class="num text-[13px] font-semibold shrink-0 {{ $out ? 'text-accent' : 'text-warn' }}">
                            {{ (float) $prod->stock }}<span class="text-faint font-normal"> / {{ $min }} {{ $prod->unit->value }}</span>
                        </p>
                    </div>
                    <div class="mt-2 h-1 rounded-full bg-sunken overflow-hidden">
                        <div class="h-full rounded-full {{ $out ? 'bg-accent' : 'bg-warn' }}" style="width: {{ $pct }}%"></div>
                    </div>
                    <p class="text-[11px] text-faint mt-1.5">{{ $prod->category->name }}{{ $out ? ' · habis' : '' }}</p>
                </div>
            @empty
                <div class="empty">
                    <span class="block mb-2"><i class="fa-solid fa-check text-ok"></i></span>
                    Semua stok di atas batas minimum.
                </div>
            @endforelse
        </section>

        {{-- Transaksi terakhir --}}
        <section class="panel lg:col-span-3 overflow-hidden">
            <div class="panel-head">
                <h3 class="panel-title">Transaksi terakhir</h3>
                <a href="{{ route('sales.index') }}" class="link text-xs">Riwayat lengkap</a>
            </div>

            @if ($recentSales->isEmpty())
                <div class="empty">Belum ada transaksi.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Kasir</th>
                                <th class="text-right">Total</th>
                                <th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentSales as $sale)
                                <tr>
                                    <td>
                                        <a href="{{ route('sales.show', $sale) }}" class="font-mono text-xs font-semibold hover:text-accent-ink">{{ $sale->invoice_no }}</a>
                                        <p class="text-[11px] text-faint mt-0.5">{{ $sale->sale_date->locale('id')->diffForHumans() }}</p>
                                    </td>
                                    <td class="text-muted">
                                        {{ $sale->user->name }}
                                        <span class="text-faint">· {{ $sale->items_count }} item</span>
                                    </td>
                                    <td class="text-right num font-semibold whitespace-nowrap">Rp {{ number_format($sale->total, 0, ',', '.') }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('pos.receipt', $sale) }}" target="_blank" class="btn-icon" title="Struk">
                                            <i class="fa-solid fa-receipt text-xs"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

    </div>
</div>
@endsection
