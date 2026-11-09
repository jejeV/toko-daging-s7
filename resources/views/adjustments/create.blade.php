@extends('layouts.app')

@section('title', 'Catat Penyesuaian')

@section('content')
<div x-data="adjustmentForm()" class="max-w-2xl mx-auto space-y-5">

    <a href="{{ route('adjustments.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left text-xs"></i> Riwayat penyesuaian
    </a>

    <div>
        <h2 class="display text-[24px]">Penyesuaian stok</h2>
        <p class="text-[13px] text-muted mt-1">Catat susut pemotongan, daging rusak, atau hasil hitung fisik stok opname.</p>
    </div>

    <form action="{{ route('adjustments.store') }}" method="POST" class="panel">
        @csrf

        <div class="p-6 space-y-5">
            <div>
                <label for="product_id" class="label">Produk <span class="text-accent">*</span></label>
                <select id="product_id" name="product_id" x-model="selectedProductId" @change="onProductChange()" required class="field">
                    <option value="">Pilih produk…</option>
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}" data-stock="{{ (float) $p->stock }}" data-unit="{{ $p->unit->value }}">
                            {{ $p->name }} ({{ $p->code }}) — stok {{ (float) $p->stock }} {{ $p->unit->value }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <span class="label">Jenis penyesuaian <span class="text-accent">*</span></span>
                <div class="grid grid-cols-3 gap-2">
                    @foreach ($types as $t)
                        <label class="flex items-center justify-center h-10 rounded-md border text-[13px] cursor-pointer select-none transition-colors"
                            :class="selectedType === '{{ $t->value }}'
                                ? 'border-accent bg-accent-soft text-accent-ink font-semibold'
                                : 'border-line-strong text-muted hover:bg-sunken hover:text-ink'">
                            <input type="radio" name="type" value="{{ $t->value }}" x-model="selectedType" class="sr-only">
                            {{ $t->label() }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label for="qty" class="label">
                    <span x-text="selectedType === 'opname' ? 'Jumlah stok fisik sebenarnya' : 'Jumlah yang susut / rusak'"></span>
                    <span class="text-accent">*</span>
                </label>
                <div class="relative">
                    <input type="number" id="qty" name="qty" value="{{ old('qty') }}" step="0.001" min="0" required placeholder="0"
                        class="field pr-12 num font-semibold">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-faint" x-text="currentUnit"></span>
                </div>
                <p class="hint">
                    <template x-if="selectedType === 'opname'">
                        <span>Isi saldo fisik di chiller/toko. Sistem menghitung selisih dari stok tercatat
                            (<span class="font-semibold text-ink num" x-text="currentStock + ' ' + currentUnit"></span>).</span>
                    </template>
                    <template x-if="selectedType !== 'opname'">
                        <span>Stok tercatat (<span class="font-semibold text-ink num" x-text="currentStock + ' ' + currentUnit"></span>)
                            akan dikurangi sebesar angka ini.</span>
                    </template>
                </p>
            </div>

            <div>
                <label for="reason" class="label">Alasan <span class="text-accent">*</span></label>
                <textarea id="reason" name="reason" rows="3" required placeholder="Susut trimming lemak 0,5 kg saat persiapan pagi." class="field">{{ old('reason') }}</textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-line bg-sunken/40">
            <a href="{{ route('adjustments.index') }}" class="btn btn-ghost">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan penyesuaian</button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
function adjustmentForm() {
    return {
        selectedProductId: '{{ old('product_id') }}',
        selectedType: '{{ old('type', 'susut') }}',
        currentStock: 0,
        currentUnit: 'kg',

        onProductChange() {
            const select = document.getElementById('product_id');
            const selectedOpt = select.options[select.selectedIndex];
            if (selectedOpt && selectedOpt.dataset) {
                this.currentStock = parseFloat(selectedOpt.dataset.stock) || 0;
                this.currentUnit = selectedOpt.dataset.unit || 'kg';
            }
        }
    }
}
</script>
@endpush
