@extends('layouts.app')

@section('title', 'Produk')

@section('content')
<div class="space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
        <div>
            <h2 class="display text-[24px]">Katalog produk</h2>
            <p class="text-[13px] text-muted mt-1">Harga jual, HPP rata-rata, dan stok fisik setiap produk.</p>
        </div>
        <a href="{{ route('products.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus text-[11px]"></i> Tambah produk
        </a>
    </div>

    <section class="panel overflow-hidden">
        <form action="{{ route('products.index') }}" method="GET" class="flex flex-wrap items-center gap-2 p-3 border-b border-line">
            <div class="relative flex-1 min-w-[200px]">
                <i class="fa-solid fa-magnifying-glass text-xs text-faint absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau SKU…" class="field pl-9">
            </div>
            <select name="category_id" class="field w-auto">
                <option value="">Semua kategori</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
            <select name="stock_status" class="field w-auto">
                <option value="">Semua stok</option>
                <option value="low" @selected(request('stock_status') == 'low')>Menipis (≤ min)</option>
                <option value="out" @selected(request('stock_status') == 'out')>Habis</option>
            </select>
            <button type="submit" class="btn btn-secondary">Terapkan</button>
            @if (request()->hasAny(['search', 'category_id', 'stock_status']))
                <a href="{{ route('products.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Kategori</th>
                        <th class="text-right">Harga jual</th>
                        <th class="text-right">HPP rata-rata</th>
                        <th class="text-right">Stok</th>
                        <th>Status</th>
                        <th class="text-right"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        @php
                            $out = $product->stock <= 0;
                            $low = ! $out && $product->stock <= $product->min_stock;
                        @endphp
                        <tr>
                            <td>
                                <p class="font-semibold">{{ $product->name }}</p>
                                <p class="font-mono text-[11px] text-faint mt-0.5">{{ $product->code }}</p>
                            </td>
                            <td class="text-muted">{{ $product->category->name }}</td>
                            <td class="text-right num font-semibold whitespace-nowrap">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</td>
                            <td class="text-right num text-muted whitespace-nowrap">Rp {{ number_format($product->avg_buy_price, 0, ',', '.') }}</td>
                            <td class="text-right whitespace-nowrap">
                                <span class="num font-semibold {{ $out ? 'text-accent' : ($low ? 'text-warn' : '') }}">{{ (float) $product->stock }}</span>
                                <span class="text-faint">{{ $product->unit->value }}</span>
                                <p class="text-[11px] text-faint num">min {{ (float) $product->min_stock }}</p>
                            </td>
                            <td>
                                @if ($product->is_active)
                                    <span class="tag tag-ok"><span class="dot"></span>Aktif</span>
                                @else
                                    <span class="tag tag-neutral"><span class="dot"></span>Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-ghost btn-sm">Edit</a>
                                <form action="{{ route('products.toggle', $product) }}" method="POST" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-ghost btn-sm" onclick="return confirm('Ubah status aktif produk ini?')">
                                        {{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty">Tidak ada produk yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $products->links('partials.pagination') }}
    </section>

</div>
@endsection
