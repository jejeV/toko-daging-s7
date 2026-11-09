@extends('layouts.app')

@section('title', 'Tambah Produk')

@section('content')
<div class="max-w-2xl mx-auto space-y-5">

    <a href="{{ route('products.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left text-xs"></i> Katalog produk
    </a>

    <div>
        <h2 class="display text-[24px]">Produk baru</h2>
        <p class="text-[13px] text-muted mt-1">Stok awal dimulai dari 0 dan bertambah lewat menu Pembelian.</p>
    </div>

    <form action="{{ route('products.store') }}" method="POST" class="panel">
        @csrf
        <div class="p-6">
            @include('products._fields')
        </div>
        <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-line bg-sunken/40">
            <a href="{{ route('products.index') }}" class="btn btn-ghost">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan produk</button>
        </div>
    </form>

</div>
@endsection
