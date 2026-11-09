@extends('layouts.app')

@section('title', 'Catat Pembelian')

@section('content')
<div x-data="purchaseForm()" class="max-w-5xl mx-auto space-y-5">

    <a href="{{ route('purchases.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left text-xs"></i> Riwayat pembelian
    </a>

    <div>
        <h2 class="display text-[24px]">Faktur pembelian baru</h2>
        <p class="text-[13px] text-muted mt-1">
            Setiap item yang disimpan langsung menambah stok dan memperbarui <strong class="text-ink font-semibold">HPP moving average</strong>.
        </p>
    </div>

    <form action="{{ route('purchases.store') }}" method="POST" class="panel">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 p-6 border-b border-line">
            <div>
                <label for="supplier_id" class="label">Supplier <span class="text-accent">*</span></label>
                <select id="supplier_id" name="supplier_id" required class="field">
                    <option value="">Pilih supplier…</option>
                    @foreach ($suppliers as $sup)
                        <option value="{{ $sup->id }}" @selected(old('supplier_id') == $sup->id)>{{ $sup->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="purchase_date" class="label">Tanggal faktur <span class="text-accent">*</span></label>
                <input type="date" id="purchase_date" name="purchase_date" value="{{ old('purchase_date', date('Y-m-d')) }}" required class="field">
            </div>
        </div>

        <div class="flex items-center justify-between px-6 h-12 border-b border-line">
            <h3 class="panel-title">Item diterima</h3>
            <button type="button" @click="addItem()" class="btn btn-ghost btn-sm text-accent-ink">
                <i class="fa-solid fa-plus text-[10px]"></i> Tambah baris
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th class="min-w-[240px]">Produk</th>
                        <th class="w-32">Qty</th>
                        <th class="w-40">Harga beli / satuan</th>
                        <th class="w-40">Kedaluwarsa</th>
                        <th class="w-36 text-right">Subtotal</th>
                        <th class="w-12"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, index) in items" :key="index">
                        <tr>
                            <td class="py-2.5!">
                                <select :name="'items['+index+'][product_id]'" x-model="item.product_id" @change="onProductChange(index)" required class="field">
                                    <option value="">Pilih produk…</option>
                                    @foreach ($products as $p)
                                        <option value="{{ $p->id }}" data-unit="{{ $p->unit->value }}" data-price="{{ (float) $p->avg_buy_price }}">
                                            {{ $p->name }} ({{ $p->unit->value }}) — stok {{ (float) $p->stock }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="py-2.5!">
                                <div class="relative">
                                    <input type="number" :name="'items['+index+'][qty]'" x-model.number="item.qty" step="0.001" min="0.001" required placeholder="0"
                                        class="field pr-9 text-right num font-semibold">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] text-faint" x-text="item.unit"></span>
                                </div>
                            </td>
                            <td class="py-2.5!">
                                <input type="number" :name="'items['+index+'][buy_price]'" x-model.number="item.buy_price" min="0" step="100" required placeholder="0"
                                    class="field text-right num font-semibold">
                            </td>
                            <td class="py-2.5!">
                                <input type="date" :name="'items['+index+'][expired_at]'" x-model="item.expired_at" class="field">
                            </td>
                            <td class="text-right num font-semibold whitespace-nowrap">
                                Rp <span x-text="formatNumber(item.qty * item.buy_price)"></span>
                            </td>
                            <td class="text-center">
                                <button type="button" @click="removeItem(index)" :disabled="items.length === 1" class="btn-icon hover:text-accent!" title="Hapus baris">
                                    <i class="fa-solid fa-xmark text-xs"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="flex items-baseline justify-between px-6 py-4 border-t border-line bg-sunken/40">
            <span class="eyebrow">Total faktur</span>
            <span class="display num text-[26px] leading-none">Rp <span x-text="formatNumber(grandTotal)"></span></span>
        </div>

        <div class="p-6 border-t border-line">
            <label for="note" class="label">Catatan / no. surat jalan <span class="text-faint font-normal">(opsional)</span></label>
            <textarea id="note" name="note" rows="2" placeholder="SJ-2026-9908 — daging segar dan beku dalam kondisi baik." class="field">{{ old('note') }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-line">
            <a href="{{ route('purchases.index') }}" class="btn btn-ghost">Batal</a>
            <button type="submit" :disabled="items.length === 0" class="btn btn-primary">Simpan & perbarui stok</button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
function purchaseForm() {
    return {
        items: [
            { product_id: '', unit: '', qty: 1, buy_price: 0, expired_at: '' }
        ],

        addItem() {
            this.items.push({ product_id: '', unit: '', qty: 1, buy_price: 0, expired_at: '' });
        },

        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },

        onProductChange(index) {
            const select = event.target;
            const selectedOpt = select.options[select.selectedIndex];
            if (selectedOpt && selectedOpt.dataset) {
                this.items[index].unit = selectedOpt.dataset.unit || '';
                if (!this.items[index].buy_price) {
                    this.items[index].buy_price = parseFloat(selectedOpt.dataset.price) || 0;
                }
            }
        },

        get grandTotal() {
            return this.items.reduce((sum, item) => sum + ((item.qty || 0) * (item.buy_price || 0)), 0);
        },

        formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(Math.round(num || 0));
        }
    }
}
</script>
@endpush
