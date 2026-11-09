@extends('layouts.app')

@section('title', 'Detail Pembelian')

@section('content')
<div class="max-w-4xl mx-auto space-y-5">

    <a href="{{ route('purchases.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left text-xs"></i> Riwayat pembelian
    </a>

    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <p class="font-mono text-xs text-faint">PUR-{{ str_pad($purchase->id, 5, '0', STR_PAD_LEFT) }}</p>
            <h2 class="display text-[24px]">Faktur pembelian</h2>
            <p class="text-[13px] text-muted mt-0.5">{{ \Carbon\Carbon::parse($purchase->purchase_date)->locale('id')->translatedFormat('l, d F Y') }}</p>
        </div>
        <div class="sm:text-right">
            <p class="eyebrow">Total pembelian</p>
            <p class="display num text-[30px] leading-tight text-accent">Rp {{ number_format($purchase->total, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="panel overflow-hidden grid grid-cols-1 sm:grid-cols-2 gap-px bg-line">
        <div class="p-5 bg-surface">
            <p class="eyebrow">Supplier</p>
            <p class="text-[15px] font-semibold mt-2">{{ $purchase->supplier->name }}</p>
            <p class="text-[13px] text-muted mt-1">{{ $purchase->supplier->phone ?? 'Tanpa nomor telepon' }}</p>
            <p class="text-xs text-faint mt-0.5">{{ $purchase->supplier->address ?? '—' }}</p>
        </div>
        <div class="p-5 bg-surface">
            <p class="eyebrow">Diterima oleh</p>
            <p class="text-[15px] font-semibold mt-2">{{ $purchase->user->name }}</p>
            <p class="text-[13px] text-muted mt-1">{{ $purchase->note ?? 'Tidak ada catatan.' }}</p>
        </div>
    </div>

    <section class="panel overflow-hidden">
        <div class="panel-head">
            <h3 class="panel-title">Rincian item</h3>
            <span class="text-xs text-faint num">{{ $purchase->items->count() }} produk</span>
        </div>
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th class="w-10">#</th>
                        <th>Produk</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Harga beli</th>
                        <th>Kedaluwarsa</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchase->items as $index => $item)
                        <tr>
                            <td class="text-faint num">{{ $index + 1 }}</td>
                            <td>
                                <p class="font-semibold">{{ $item->product->name }}</p>
                                <p class="font-mono text-[11px] text-faint mt-0.5">{{ $item->product->code }}</p>
                            </td>
                            <td class="text-right num whitespace-nowrap">{{ (float) $item->qty }} <span class="text-faint">{{ $item->product->unit->value }}</span></td>
                            <td class="text-right num text-muted whitespace-nowrap">Rp {{ number_format($item->buy_price, 0, ',', '.') }}</td>
                            <td class="text-muted num">{{ $item->expired_at ? \Carbon\Carbon::parse($item->expired_at)->format('d/m/Y') : '—' }}</td>
                            <td class="text-right num font-semibold whitespace-nowrap">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-right eyebrow">Total</td>
                        <td class="text-right num font-bold whitespace-nowrap">Rp {{ number_format($purchase->total, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

</div>
@endsection
