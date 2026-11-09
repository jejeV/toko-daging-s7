@extends('layouts.app')

@section('title', 'Edit Produk')

@section('content')
<div class="max-w-2xl mx-auto space-y-5">

    <a href="{{ route('products.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left text-xs"></i> Katalog produk
    </a>

    <div class="flex items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="font-mono text-xs text-faint">{{ $product->code }}</p>
            <h2 class="display text-[24px] truncate">{{ $product->name }}</h2>
        </div>
        <div class="text-right shrink-0">
            <p class="eyebrow">Stok saat ini</p>
            <p class="display num text-[22px] leading-tight">{{ (float) $product->stock }} <span class="text-[13px] text-faint">{{ $product->unit->value }}</span></p>
        </div>
    </div>

    <form action="{{ route('products.update', $product) }}" method="POST" class="panel">
        @csrf
        @method('PUT')
        <div class="p-6">
            @include('products._fields', ['product' => $product])
        </div>
        <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-line bg-sunken/40">
            <a href="{{ route('products.index') }}" class="btn btn-ghost">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan perubahan</button>
        </div>
    </form>

</div>
@endsection
