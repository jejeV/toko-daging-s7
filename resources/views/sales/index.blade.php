@extends('layouts.app')

@section('title', 'Riwayat Penjualan')

@section('content')
<div class="space-y-5">

    <div>
        <h2 class="display text-[24px]">Riwayat penjualan</h2>
        <p class="text-[13px] text-muted mt-1">Semua transaksi kasir beserta pembayaran dan kembalian.</p>
    </div>

    <section class="panel overflow-hidden">
        <form action="{{ route('sales.index') }}" method="GET" class="flex flex-wrap items-center gap-2 p-3 border-b border-line">
            <div class="relative flex-1 min-w-[200px]">
                <i class="fa-solid fa-magnifying-glass text-xs text-faint absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="No. invoice (INV-…)" class="field pl-9 font-mono">
            </div>
            <div class="flex items-center gap-1.5">
                <input type="date" name="date_from" value="{{ request('date_from') }}" title="Dari tanggal" aria-label="Dari tanggal" class="field w-auto">
                <span class="text-faint text-xs">–</span>
                <input type="date" name="date_to" value="{{ request('date_to') }}" title="Sampai tanggal" aria-label="Sampai tanggal" class="field w-auto">
            </div>
            <button type="submit" class="btn btn-secondary">Terapkan</button>
            @if (request()->hasAny(['search', 'date_from', 'date_to']))
                <a href="{{ route('sales.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Waktu</th>
                        <th>Kasir</th>
                        <th class="text-right">Item</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Tunai</th>
                        <th class="text-right">Kembali</th>
                        <th><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td class="font-mono text-xs font-semibold whitespace-nowrap">
                                <a href="{{ route('sales.show', $sale) }}" class="hover:text-accent-ink">{{ $sale->invoice_no }}</a>
                            </td>
                            <td class="whitespace-nowrap num">
                                {{ $sale->sale_date->locale('id')->translatedFormat('d M Y') }}
                                <span class="text-faint">{{ $sale->sale_date->format('H:i') }}</span>
                            </td>
                            <td class="text-muted">{{ $sale->user->name }}</td>
                            <td class="text-right num text-muted">{{ $sale->items_count }}</td>
                            <td class="text-right num font-semibold whitespace-nowrap">Rp {{ number_format($sale->total, 0, ',', '.') }}</td>
                            <td class="text-right num text-muted whitespace-nowrap">Rp {{ number_format($sale->paid, 0, ',', '.') }}</td>
                            <td class="text-right num text-muted whitespace-nowrap">Rp {{ number_format($sale->change, 0, ',', '.') }}</td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('sales.show', $sale) }}" class="btn-icon" title="Detail"><i class="fa-solid fa-arrow-right text-xs"></i></a>
                                <a href="{{ route('pos.receipt', $sale) }}" target="_blank" class="btn-icon" title="Cetak struk"><i class="fa-solid fa-receipt text-xs"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty">Tidak ada penjualan yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $sales->links('partials.pagination') }}
    </section>

</div>
@endsection
