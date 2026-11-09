@extends('layouts.app')

@section('title', 'Pembelian')

@section('content')
<div class="space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
        <div>
            <h2 class="display text-[24px]">Pembelian stok</h2>
            <p class="text-[13px] text-muted mt-1">Faktur dari supplier. Setiap pembelian memperbarui stok dan HPP rata-rata.</p>
        </div>
        <a href="{{ route('purchases.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus text-[11px]"></i> Catat pembelian
        </a>
    </div>

    <section class="panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>No. faktur</th>
                        <th>Tanggal</th>
                        <th>Supplier</th>
                        <th class="text-right">Item</th>
                        <th class="text-right">Total</th>
                        <th>Petugas</th>
                        <th><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchases as $purchase)
                        <tr>
                            <td class="font-mono text-xs font-semibold whitespace-nowrap">
                                <a href="{{ route('purchases.show', $purchase) }}" class="hover:text-accent-ink">PUR-{{ str_pad($purchase->id, 5, '0', STR_PAD_LEFT) }}</a>
                            </td>
                            <td class="text-muted whitespace-nowrap num">
                                {{ \Carbon\Carbon::parse($purchase->purchase_date)->locale('id')->translatedFormat('d M Y') }}
                            </td>
                            <td>
                                <p class="font-semibold">{{ $purchase->supplier->name }}</p>
                                @if ($purchase->note)
                                    <p class="text-[11px] text-faint mt-0.5">{{ Str::limit($purchase->note, 40) }}</p>
                                @endif
                            </td>
                            <td class="text-right num text-muted">{{ $purchase->items_count }}</td>
                            <td class="text-right num font-semibold whitespace-nowrap">Rp {{ number_format($purchase->total, 0, ',', '.') }}</td>
                            <td class="text-muted">{{ $purchase->user->name }}</td>
                            <td class="text-right">
                                <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-ghost btn-sm">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty">Belum ada pembelian tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $purchases->links('partials.pagination') }}
    </section>

</div>
@endsection
