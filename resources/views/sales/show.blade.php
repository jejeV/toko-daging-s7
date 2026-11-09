@extends('layouts.app')

@section('title', 'Detail Penjualan')

@section('content')
@php $isOwner = Auth::user()->isOwner(); @endphp
<div class="max-w-4xl mx-auto space-y-5">

    <div class="flex items-center justify-between">
        <a href="{{ route('sales.index') }}" class="back-link">
            <i class="fa-solid fa-arrow-left text-xs"></i> Riwayat penjualan
        </a>
        <a href="{{ route('pos.receipt', $sale) }}" target="_blank" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-receipt text-[11px]"></i> Cetak struk
        </a>
    </div>

    <div>
        <p class="font-mono text-xs text-faint">{{ $sale->invoice_no }}</p>
        <h2 class="display text-[24px]">Transaksi penjualan</h2>
        <p class="text-[13px] text-muted mt-0.5">
            {{ $sale->sale_date->locale('id')->translatedFormat('l, d F Y · H:i') }} · Kasir {{ $sale->user->name }}
        </p>
    </div>

    <div class="panel overflow-hidden grid grid-cols-1 sm:grid-cols-3 gap-px bg-line">
        <div class="p-5 bg-surface">
            <p class="eyebrow">Total</p>
            <p class="display num text-[26px] leading-tight mt-2 text-accent">Rp {{ number_format($sale->total, 0, ',', '.') }}</p>
        </div>
        <div class="p-5 bg-surface">
            <p class="eyebrow">Tunai diterima</p>
            <p class="display num text-[26px] leading-tight mt-2">Rp {{ number_format($sale->paid, 0, ',', '.') }}</p>
        </div>
        <div class="p-5 bg-surface">
            <p class="eyebrow">Kembalian</p>
            <p class="display num text-[26px] leading-tight mt-2">Rp {{ number_format($sale->change, 0, ',', '.') }}</p>
        </div>
    </div>

    <section class="panel overflow-hidden">
        <div class="panel-head">
            <h3 class="panel-title">Item terjual</h3>
            @if ($isOwner)
                <span class="text-[11px] text-faint">HPP & laba hanya terlihat oleh pemilik</span>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th class="w-10">#</th>
                        <th>Produk</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Harga</th>
                        @if ($isOwner)
                            <th class="text-right">HPP</th>
                            <th class="text-right">Laba kotor</th>
                        @endif
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalProfit = 0; @endphp
                    @foreach ($sale->items as $index => $item)
                        @php
                            $profit = ($item->price - $item->buy_price) * $item->qty;
                            $totalProfit += $profit;
                        @endphp
                        <tr>
                            <td class="text-faint num">{{ $index + 1 }}</td>
                            <td>
                                <p class="font-semibold">{{ $item->product->name }}</p>
                                <p class="font-mono text-[11px] text-faint mt-0.5">{{ $item->product->code }}</p>
                            </td>
                            <td class="text-right num whitespace-nowrap">{{ (float) $item->qty }} <span class="text-faint">{{ $item->product->unit->value }}</span></td>
                            <td class="text-right num text-muted whitespace-nowrap">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                            @if ($isOwner)
                                <td class="text-right num text-muted whitespace-nowrap">Rp {{ number_format($item->buy_price, 0, ',', '.') }}</td>
                                <td class="text-right num whitespace-nowrap {{ $profit >= 0 ? 'text-ok' : 'text-accent' }}">
                                    {{ $profit >= 0 ? '+' : '−' }}Rp {{ number_format(abs($profit), 0, ',', '.') }}
                                </td>
                            @endif
                            <td class="text-right num font-semibold whitespace-nowrap">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="{{ $isOwner ? 6 : 4 }}" class="text-right eyebrow">Total</td>
                        <td class="text-right num font-bold whitespace-nowrap">Rp {{ number_format($sale->total, 0, ',', '.') }}</td>
                    </tr>
                    @if ($isOwner)
                        <tr>
                            <td colspan="6" class="text-right eyebrow border-t-0! pt-0!">Estimasi laba kotor</td>
                            <td class="text-right num font-bold whitespace-nowrap border-t-0! pt-0! {{ $totalProfit >= 0 ? 'text-ok' : 'text-accent' }}">
                                {{ $totalProfit >= 0 ? '+' : '−' }}Rp {{ number_format(abs($totalProfit), 0, ',', '.') }}
                            </td>
                        </tr>
                    @endif
                </tfoot>
            </table>
        </div>
    </section>

</div>
@endsection
