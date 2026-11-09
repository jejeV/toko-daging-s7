@extends('layouts.app')

@section('title', 'Laporan Penjualan')

@use('App\Support\Fmt')
@use('App\Enums\AdjustmentType')

@section('content')
@php
    $periodLabel = $month
        ? $from->locale('id')->translatedFormat('F Y')
        : $from->locale('id')->translatedFormat('j M Y').' – '.$to->locale('id')->translatedFormat('j M Y');
    $query = $month ? ['bulan' => $month] : ['dari' => $from->toDateString(), 'sampai' => $to->toDateString()];

    $change = fn ($now, $before) => $previous && $before > 0 ? ($now - $before) / $before * 100 : null;
    $kpis = array_values(array_filter([
        ['Omzet', Fmt::rupiah($summary->revenue), $change($summary->revenue, $previous?->revenue)],
        ['Transaksi', number_format($summary->orders, 0, ',', '.').' struk', $change($summary->orders, $previous?->orders)],
        ['Rata-rata per struk', Fmt::rupiah($summary->avg_order), $change($summary->avg_order, $previous?->avg_order)],
        $isOwner ? ['Laba kotor', Fmt::rupiah($summary->profit), $change($summary->profit, $previous?->profit), 'Margin '.Fmt::percent($summary->margin)] : null,
        ['Pembelian stok', Fmt::rupiah($purchasesTotal), null, 'Faktur supplier di periode ini'],
    ]));

    $activeDays = collect($daily)->where('orders', '>', 0);
    $best = $activeDays->sortByDesc('revenue')->first();
    $worst = $activeDays->sortBy('revenue')->first();
    $labelEvery = count($daily) > 45 ? 7 : (count($daily) > 20 ? 5 : 1);

    $dailyItems = array_map(fn ($d) => [
        'value' => $d['revenue'],
        'axis' => $d['day'] === 1 || $d['day'] % $labelEvery === 0 || $labelEvery === 1 ? $d['day'] : null,
        'title' => $d['label'],
        'lines' => [Fmt::rupiah($d['revenue']), $d['orders'].' struk'],
        'shade' => $d['weekend'],
    ], $daily);

    $weekdayItems = array_map(fn ($w) => [
        'value' => $w['revenue'],
        'axis' => mb_substr($w['name'], 0, 3),
        'title' => $w['name'].' (rata-rata '.$w['days'].' hari)',
        'lines' => [Fmt::rupiah($w['revenue']), Fmt::qty($w['orders'], 0).' struk'],
        'shade' => $w['name'] === 'Sabtu' || $w['name'] === 'Minggu',
    ], $weekdays);

    $hourItems = array_map(fn ($h) => [
        'value' => $h['orders'],
        'axis' => sprintf('%02d', $h['hour']),
        'title' => sprintf('%02d.00–%02d.59', $h['hour'], $h['hour']),
        'lines' => [Fmt::qty($h['orders'], 1).' struk / hari', Fmt::rupiah($h['revenue'])],
    ], $hours);
    $busiest = collect($hours)->sortByDesc('orders')->first();

    $categoryTotal = $categories->sum('revenue') ?: 1;
    $productTotal = $products->sum('revenue') ?: 1;
    $adjustmentLoss = $adjustments->where('value', '<', 0)->sum('value');
@endphp

<div class="space-y-6">

    {{-- Judul & pilihan periode --}}
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
        <div>
            <p class="eyebrow">Laporan penjualan</p>
            <h2 class="display text-[30px] leading-tight mt-1">{{ $periodLabel }}</h2>
            <p class="text-xs text-faint mt-1">
                Dibuat {{ now()->locale('id')->translatedFormat('j F Y, H.i') }} oleh {{ Auth::user()->name }}
                @if ($previous) · dibandingkan dengan {{ $previousLabel }} @endif
            </p>
        </div>

        <div class="no-print flex flex-wrap items-end gap-2">
            <form method="GET" action="{{ route('reports.index') }}" class="flex items-end gap-2">
                <div>
                    <label for="bulan" class="label">Bulan</label>
                    <select id="bulan" name="bulan" class="field w-auto" onchange="this.form.submit()">
                        @unless ($month)
                            <option value="">Rentang khusus</option>
                        @endunless
                        @foreach ($months as $ym => $label)
                            <option value="{{ $ym }}" @selected($month === $ym)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            <form method="GET" action="{{ route('reports.index') }}" class="flex items-end gap-1.5">
                <div>
                    <label for="dari" class="label">Atau rentang</label>
                    <input type="date" id="dari" name="dari" value="{{ $month ? '' : $from->toDateString() }}" required class="field w-auto">
                </div>
                <span class="text-faint text-xs pb-2.5">–</span>
                <input type="date" name="sampai" value="{{ $month ? '' : $to->toDateString() }}" required aria-label="Sampai tanggal" class="field w-auto">
                <button type="submit" class="btn btn-secondary">Tampilkan</button>
            </form>

            <div class="flex gap-2">
                <a href="{{ route('reports.products-csv', $query) }}" data-no-prerender class="btn btn-secondary" title="Rekap per produk untuk Excel">
                    <i class="fa-solid fa-file-csv text-[12px]"></i> CSV
                </a>
                <button type="button" onclick="window.print()" class="btn btn-primary">
                    <i class="fa-solid fa-print text-[11px]"></i> Cetak / PDF
                </button>
            </div>
        </div>
    </div>

    @if ($summary->orders === 0)
        <div class="panel empty">Tidak ada penjualan pada periode ini.</div>
    @else

    {{-- KPI --}}
    <div class="panel overflow-hidden grid grid-cols-2 {{ count($kpis) === 5 ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }} gap-px bg-line">
        @foreach ($kpis as $kpi)
            <div class="bg-surface p-5 {{ $loop->last && count($kpis) % 2 ? 'col-span-2 lg:col-span-1' : '' }}">
                <p class="text-xs text-muted">{{ $kpi[0] }}</p>
                <p class="display text-[22px] leading-tight mt-1.5">{{ $kpi[1] }}</p>
                <p class="text-[11px] text-faint mt-1">
                    @if ($kpi[2] !== null)
                        <span class="font-semibold {{ $kpi[2] >= 0 ? 'text-ok' : 'text-accent' }}">
                            <i class="fa-solid {{ $kpi[2] >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }} text-[9px]"></i>
                            {{ Fmt::percent(abs($kpi[2])) }}
                        </span>
                        vs {{ $previousLabel }}
                    @elseif (isset($kpi[3]))
                        {{ $kpi[3] }}
                    @else
                        {{ $previous ? 'Tidak ada data pembanding' : 'Data periode sebelumnya belum lengkap' }}
                    @endif
                </p>
            </div>
        @endforeach
    </div>

    {{-- Tren harian --}}
    <section class="panel p-5">
        <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-2 mb-4">
            <div>
                <h3 class="panel-title">Omzet harian</h3>
                @if ($best)
                    <p class="text-xs text-muted mt-1">
                        Tertinggi <strong class="text-ink font-semibold">{{ $best['label'] }}</strong> ({{ Fmt::rupiah($best['revenue']) }}),
                        terendah {{ $worst['label'] }} ({{ Fmt::rupiah($worst['revenue']) }}).
                        Rata-rata {{ Fmt::rupiah($summary->revenue / max(1, $activeDays->count())) }} per hari buka.
                    </p>
                @endif
            </div>
            <span class="flex items-center gap-1.5 text-[11px] text-faint shrink-0">
                <span class="w-3 h-3 rounded-sm bg-sunken border border-line"></span> akhir pekan
            </span>
        </div>
        <x-chart.columns :items="$dailyItems" height="h-64" label="Omzet harian {{ $periodLabel }}" />
    </section>

    {{-- Pola mingguan & jam --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <section class="panel p-5">
            <h3 class="panel-title">Rata-rata omzet per hari</h3>
            <p class="text-xs text-muted mt-1 mb-4">Dari hari yang ada penjualannya. Kolom berarsir = akhir pekan.</p>
            <x-chart.columns :items="$weekdayItems" label="Rata-rata omzet per hari dalam seminggu" />
        </section>

        <section class="panel p-5">
            <h3 class="panel-title">Jam ramai</h3>
            <p class="text-xs text-muted mt-1 mb-4">
                Rata-rata struk per jam.
                @if ($busiest)
                    Paling ramai pukul <strong class="text-ink font-semibold">{{ sprintf('%02d.00', $busiest['hour']) }}</strong>
                    (±{{ Fmt::qty($busiest['orders'], 0) }} struk).
                @endif
            </p>
            <x-chart.columns :items="$hourItems" color="bg-series-2" :peak="false" label="Rata-rata struk per jam" />
        </section>
    </div>

    {{-- Arus kas & kategori --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <section class="panel p-5 lg:col-span-3">
            <h3 class="panel-title">Penjualan vs pembelian per minggu</h3>
            <p class="text-xs text-muted mt-1 mb-4">Pembelian = nilai faktur stok dari supplier.</p>
            <x-chart.grouped
                :labels="array_column($weeks, 'label')"
                :series="[
                    ['name' => 'Penjualan', 'color' => 'bg-accent', 'values' => array_column($weeks, 'sales')],
                    ['name' => 'Pembelian', 'color' => 'bg-series-2', 'values' => array_column($weeks, 'purchases')],
                ]"
                label="Penjualan dan pembelian per minggu" />
        </section>

        <section class="panel p-5 lg:col-span-2">
            <h3 class="panel-title mb-4">Omzet per kategori</h3>
            <x-chart.bars :items="$categories->map(fn ($c) => [
                'label' => $c->name,
                'value' => (float) $c->revenue,
                'display' => Fmt::short($c->revenue),
                'note' => Fmt::percent($c->revenue / $categoryTotal * 100, 0).($isOwner ? ' · margin '.Fmt::percent($c->revenue > 0 ? $c->profit / $c->revenue * 100 : 0, 0) : ''),
            ])->all()" />
        </section>
    </div>

    {{-- Rincian produk --}}
    <section class="panel overflow-hidden">
        <div class="panel-head">
            <h3 class="panel-title">Rincian per produk</h3>
            <span class="text-xs text-faint num">{{ $products->count() }} produk terjual</span>
        </div>
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th class="w-10">#</th>
                        <th>Produk</th>
                        <th>Kategori</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Struk</th>
                        <th class="text-right">Omzet</th>
                        <th class="min-w-[140px]">Kontribusi</th>
                        @if ($isOwner)
                            <th class="text-right">Laba kotor</th>
                            <th class="text-right">Margin</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $i => $p)
                        @php
                            $share = $p->revenue / $productTotal * 100;
                            $profit = $p->revenue - $p->cost;
                        @endphp
                        <tr>
                            <td class="text-faint num">{{ $i + 1 }}</td>
                            <td>
                                <p class="font-semibold">{{ $p->name }}</p>
                                <p class="font-mono text-[11px] text-faint mt-0.5">{{ $p->code }}</p>
                            </td>
                            <td class="text-muted whitespace-nowrap">{{ $p->category }}</td>
                            <td class="text-right num whitespace-nowrap">{{ Fmt::qty($p->qty) }} <span class="text-faint">{{ $p->unit }}</span></td>
                            <td class="text-right num text-muted">{{ number_format($p->orders, 0, ',', '.') }}</td>
                            <td class="text-right num font-semibold whitespace-nowrap">{{ Fmt::rupiah($p->revenue) }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-1.5 rounded-full bg-sunken overflow-hidden">
                                        <div class="h-full rounded-full bg-accent" style="width: {{ min(100, $share / max(1, $products->first()->revenue / $productTotal * 100) * 100) }}%"></div>
                                    </div>
                                    <span class="text-[11px] text-muted num w-11 text-right">{{ Fmt::percent($share) }}</span>
                                </div>
                            </td>
                            @if ($isOwner)
                                <td class="text-right num whitespace-nowrap {{ $profit < 0 ? 'text-accent' : '' }}">{{ Fmt::rupiah($profit) }}</td>
                                <td class="text-right num text-muted">{{ Fmt::percent($p->revenue > 0 ? $profit / $p->revenue * 100 : 0) }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-right eyebrow">Total</td>
                        <td class="text-right num font-bold whitespace-nowrap">{{ Fmt::rupiah($products->sum('revenue')) }}</td>
                        <td></td>
                        @if ($isOwner)
                            <td class="text-right num font-bold whitespace-nowrap">{{ Fmt::rupiah($products->sum('revenue') - $products->sum('cost')) }}</td>
                            <td class="text-right num text-muted">{{ Fmt::percent($summary->margin) }}</td>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    {{-- Kasir & penyesuaian --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <section class="panel overflow-hidden lg:col-span-2">
            <div class="panel-head"><h3 class="panel-title">Kinerja kasir</h3></div>
            <table class="tbl">
                <thead>
                    <tr><th>Kasir</th><th class="text-right">Struk</th><th class="text-right">Omzet</th><th class="text-right">Rata-rata</th></tr>
                </thead>
                <tbody>
                    @foreach ($cashiers as $c)
                        <tr>
                            <td class="font-medium">{{ $c->name }}</td>
                            <td class="text-right num">{{ number_format($c->orders, 0, ',', '.') }}</td>
                            <td class="text-right num whitespace-nowrap">{{ Fmt::short($c->revenue) }}</td>
                            <td class="text-right num text-muted whitespace-nowrap">{{ Fmt::rupiah($c->revenue / max(1, $c->orders)) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="panel overflow-hidden lg:col-span-3">
            <div class="panel-head">
                <h3 class="panel-title">Penyesuaian stok</h3>
                @if ($adjustmentLoss < 0)
                    <span class="text-xs text-muted">Estimasi kerugian <strong class="text-accent font-semibold num">{{ Fmt::rupiah(abs($adjustmentLoss)) }}</strong></span>
                @endif
            </div>
            @if ($adjustments->isEmpty())
                <div class="empty">Tidak ada penyesuaian stok di periode ini.</div>
            @else
                <div class="overflow-x-auto max-h-80 overflow-y-auto">
                    <table class="tbl">
                        <thead>
                            <tr><th>Produk</th><th>Jenis</th><th class="text-right">Kali</th><th class="text-right">Selisih</th><th class="text-right">Est. nilai</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($adjustments as $a)
                                @php $type = $a->type instanceof AdjustmentType ? $a->type : AdjustmentType::from($a->type); @endphp
                                <tr>
                                    <td class="font-medium">{{ $a->name }}</td>
                                    <td>
                                        <span class="tag {{ match ($type) { AdjustmentType::Susut => 'tag-warn', AdjustmentType::Rusak => 'tag-accent', default => 'tag-neutral' } }}">{{ $type->label() }}</span>
                                    </td>
                                    <td class="text-right num text-muted">{{ $a->times }}</td>
                                    <td class="text-right num whitespace-nowrap">{{ $a->qty > 0 ? '+' : '' }}{{ Fmt::qty($a->qty) }} <span class="text-faint">{{ $a->unit }}</span></td>
                                    <td class="text-right num whitespace-nowrap {{ $a->value < 0 ? 'text-accent' : 'text-ok' }}">{{ Fmt::rupiah($a->value) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="px-5 py-2.5 border-t border-line text-[11px] text-faint">Nilai = selisih qty × HPP rata-rata produk saat ini (perkiraan).</p>
            @endif
        </section>
    </div>

    <p class="text-[11px] text-faint">
        Laba kotor memakai HPP yang terkunci saat setiap transaksi (moving average), belum termasuk biaya operasional.
        @unless ($isOwner) Angka laba hanya tampil untuk akun pemilik. @endunless
    </p>

    @endif
</div>
@endsection
