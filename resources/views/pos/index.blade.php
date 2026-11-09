@extends('layouts.app')

@section('title', 'Kasir')

@section('content')
<div x-data="posApp()" class="flex flex-col lg:flex-row gap-6 items-start">

    {{-- Katalog --}}
    <div class="flex-1 w-full min-w-0 space-y-4">

        <div class="flex flex-col sm:flex-row gap-2">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass text-xs text-faint absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input type="text" x-model="searchQuery" placeholder="Cari nama daging atau SKU…" class="field pl-9">
            </div>
            <select x-model="activeCategory" class="field sm:w-56">
                <option :value="null">Semua kategori</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 max-h-[calc(100vh-12rem)] overflow-y-auto pr-1 pb-1">
            @foreach ($products as $prod)
                @php
                    $out = $prod->stock <= 0;
                    $low = ! $out && $prod->stock <= $prod->min_stock;
                @endphp
                <button type="button"
                     x-show="matchesFilter({{ $prod->category_id }}, '{{ strtolower(addslashes($prod->name)) }}', '{{ strtolower($prod->code) }}')"
                     class="group text-left panel p-3.5 flex flex-col justify-between min-h-[132px] transition hover:border-accent focus:outline-none focus-visible:ring-[3px] focus-visible:ring-accent/25 {{ $out ? 'opacity-50' : '' }}"
                     @click="addToCart({
                         id: {{ $prod->id }},
                         code: '{{ $prod->code }}',
                         name: '{{ addslashes($prod->name) }}',
                         unit: '{{ $prod->unit->value }}',
                         price: {{ (float) $prod->sell_price }},
                         stock: {{ (float) $prod->stock }},
                         allowsDecimal: {{ $prod->unit->allowsDecimal() ? 'true' : 'false' }}
                     })">

                    <div>
                        <p class="font-mono text-[10px] text-faint">{{ $prod->code }}</p>
                        <p class="text-[13px] font-semibold leading-snug mt-1 line-clamp-2">{{ $prod->name }}</p>
                    </div>

                    <div class="mt-3 flex items-end justify-between gap-2">
                        <p class="display num text-[17px] leading-none">
                            <span class="text-[11px] text-faint font-semibold">Rp</span>{{ number_format($prod->sell_price, 0, ',', '.') }}
                            <span class="text-[11px] text-faint font-medium">/{{ $prod->unit->value }}</span>
                        </p>
                        <span class="text-[11px] num font-semibold {{ $out ? 'text-accent' : ($low ? 'text-warn' : 'text-faint') }}">
                            {{ $out ? 'Habis' : (float) $prod->stock . ' ' . $prod->unit->value }}
                        </span>
                    </div>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Keranjang --}}
    <div class="panel w-full lg:w-[380px] flex flex-col shrink-0 lg:sticky lg:top-20 overflow-hidden" style="min-height: 520px; max-height: calc(100vh - 6.5rem);">

        <div class="panel-head shrink-0">
            <h3 class="panel-title">
                Pesanan <span class="text-faint font-normal num" x-text="'· ' + cart.length + ' item'"></span>
            </h3>
            <button type="button" @click="clearCart()" x-show="cart.length > 0" class="text-xs font-semibold text-faint hover:text-accent">
                Kosongkan
            </button>
        </div>

        <div class="flex-1 overflow-y-auto">
            <template x-if="cart.length === 0">
                <div class="h-full min-h-[200px] flex flex-col items-center justify-center text-center px-8">
                    <i class="fa-solid fa-basket-shopping text-xl text-line-strong mb-3"></i>
                    <p class="text-[13px] font-semibold text-muted">Belum ada item</p>
                    <p class="text-xs text-faint mt-1">Pilih produk dari katalog untuk mulai.</p>
                </div>
            </template>

            <template x-for="(item, index) in cart" :key="item.id">
                <div class="px-5 py-3.5 border-b border-line">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[13px] font-semibold truncate" x-text="item.name"></p>
                            <p class="text-[11px] text-faint num mt-0.5" x-text="'Rp ' + formatNumber(item.price) + ' / ' + item.unit"></p>
                        </div>
                        <button @click="removeItem(index)" class="text-faint hover:text-accent -mr-1 p-1" title="Hapus">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>

                    <div class="flex items-center justify-between mt-2.5">
                        <div class="flex items-center h-8 rounded-md border border-line-strong overflow-hidden">
                            <button type="button" @click="decreaseQty(index)" class="w-8 h-full text-muted hover:bg-sunken hover:text-ink">−</button>
                            <input type="number" :step="item.allowsDecimal ? '0.1' : '1'" min="0.001" :max="item.stock"
                                x-model.number="item.qty" @change="validateQty(index)"
                                class="w-14 h-full text-center text-[13px] font-semibold num bg-transparent border-x border-line-strong focus:outline-none focus:bg-sunken">
                            <button type="button" @click="increaseQty(index)" class="w-8 h-full text-muted hover:bg-sunken hover:text-ink">+</button>
                        </div>
                        <span class="text-[11px] text-faint ml-2 mr-auto" x-text="item.unit"></span>
                        <span class="text-[13px] font-semibold num" x-text="'Rp ' + formatNumber(item.qty * item.price)"></span>
                    </div>
                </div>
            </template>
        </div>

        <form action="{{ route('pos.store') }}" method="POST" class="border-t border-line bg-sunken/50 p-5 space-y-4 shrink-0">
            @csrf
            <template x-for="(item, i) in cart" :key="item.id">
                <div>
                    <input type="hidden" :name="'items['+i+'][product_id]'" :value="item.id">
                    <input type="hidden" :name="'items['+i+'][qty]'" :value="item.qty">
                </div>
            </template>

            <div class="flex items-baseline justify-between">
                <span class="eyebrow">Total</span>
                <span class="display num text-[28px] leading-none" x-text="'Rp ' + formatNumber(grandTotal)"></span>
            </div>

            <div>
                <label class="label" for="paid">Uang diterima</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[13px] text-faint font-semibold">Rp</span>
                    <input type="number" id="paid" name="paid" x-model.number="paidAmount" required min="0" placeholder="0"
                        class="field h-10 pl-9 text-[15px] font-semibold num">
                </div>
                <div class="grid grid-cols-4 gap-1.5 mt-2">
                    <button type="button" @click="paidAmount = grandTotal" class="btn btn-secondary btn-sm">Pas</button>
                    <button type="button" @click="paidAmount = 50000" class="btn btn-secondary btn-sm num">50rb</button>
                    <button type="button" @click="paidAmount = 100000" class="btn btn-secondary btn-sm num">100rb</button>
                    <button type="button" @click="paidAmount = 200000" class="btn btn-secondary btn-sm num">200rb</button>
                </div>
            </div>

            <div class="flex items-baseline justify-between text-[13px]">
                <span class="text-muted">Kembalian</span>
                <span class="num font-semibold" :class="changeAmount >= 0 ? 'text-ok' : 'text-accent'" x-text="'Rp ' + formatNumber(changeAmount)"></span>
            </div>

            <button type="submit" :disabled="cart.length === 0 || paidAmount < grandTotal" class="btn btn-primary w-full h-11 text-[14px]">
                Selesaikan transaksi
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function posApp() {
    return {
        searchQuery: '',
        activeCategory: null,
        cart: [],
        paidAmount: 0,

        matchesFilter(catId, name, code) {
            const catOk = this.activeCategory == null || this.activeCategory == catId;
            const q = this.searchQuery.toLowerCase().trim();
            return catOk && (q === '' || name.includes(q) || code.includes(q));
        },

        addToCart(product) {
            if (product.stock <= 0) {
                alert('Stok produk ini sedang habis.');
                return;
            }
            const existing = this.cart.find(i => i.id === product.id);
            const step = product.allowsDecimal ? 1.0 : 1;
            if (existing) {
                if (existing.qty + step > product.stock) {
                    alert('Jumlah melebihi stok yang tersedia (' + product.stock + ' ' + product.unit + ').');
                    return;
                }
                existing.qty = Math.round((existing.qty + step) * 1000) / 1000;
            } else {
                this.cart.push({ ...product, qty: step });
            }
        },

        increaseQty(i) {
            const item = this.cart[i];
            const s = item.allowsDecimal ? 0.5 : 1;
            if (item.qty + s > item.stock) {
                alert('Stok maksimal tercapai: ' + item.stock + ' ' + item.unit);
                return;
            }
            item.qty = Math.round((item.qty + s) * 1000) / 1000;
        },

        decreaseQty(i) {
            const item = this.cart[i];
            const s = item.allowsDecimal ? 0.5 : 1;
            if (item.qty - s <= 0) {
                this.removeItem(i);
            } else {
                item.qty = Math.round((item.qty - s) * 1000) / 1000;
            }
        },

        validateQty(i) {
            const item = this.cart[i];
            if (item.qty <= 0) {
                this.removeItem(i);
                return;
            }
            if (!item.allowsDecimal) item.qty = Math.floor(item.qty);
            if (item.qty > item.stock) item.qty = item.stock;
        },

        removeItem(i) {
            this.cart.splice(i, 1);
        },

        clearCart() {
            if (confirm('Kosongkan semua item di keranjang?')) {
                this.cart = [];
                this.paidAmount = 0;
            }
        },

        get grandTotal() {
            return Math.round(this.cart.reduce((s, i) => s + (i.qty * i.price), 0));
        },

        get changeAmount() {
            return this.paidAmount ? Math.round(this.paidAmount - this.grandTotal) : 0;
        },

        formatNumber(n) {
            return new Intl.NumberFormat('id-ID').format(n || 0);
        }
    }
}
</script>
@endpush
