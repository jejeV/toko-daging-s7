@php $product = $product ?? null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div>
        <label for="category_id" class="label">Kategori <span class="text-accent">*</span></label>
        <select id="category_id" name="category_id" required class="field">
            @unless ($product)
                <option value="">Pilih kategori…</option>
            @endunless
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" @selected(old('category_id', $product?->category_id) == $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="code" class="label">Kode SKU <span class="text-accent">*</span></label>
        <input type="text" id="code" name="code" value="{{ old('code', $product?->code) }}" required placeholder="D-SIRL-01" class="field font-mono">
    </div>

    <div class="sm:col-span-2">
        <label for="name" class="label">Nama produk <span class="text-accent">*</span></label>
        <input type="text" id="name" name="name" value="{{ old('name', $product?->name) }}" required placeholder="Daging Sapi Sirloin Lokal" class="field">
    </div>

    <div>
        <label for="unit" class="label">Satuan <span class="text-accent">*</span></label>
        <select id="unit" name="unit" required class="field">
            @foreach ($units as $u)
                <option value="{{ $u->value }}" @selected(old('unit', $product?->unit->value) == $u->value)>
                    {{ strtoupper($u->value) }} — {{ $u->allowsDecimal() ? 'desimal / timbangan' : 'bilangan bulat' }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="sell_price" class="label">Harga jual per satuan <span class="text-accent">*</span></label>
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[13px] text-faint font-semibold">Rp</span>
            <input type="number" id="sell_price" name="sell_price" value="{{ old('sell_price', $product ? (int) $product->sell_price : null) }}" required min="0" step="100" placeholder="120000" class="field pl-9 num font-semibold">
        </div>
    </div>

    <div>
        <label for="min_stock" class="label">Batas minimum stok <span class="text-accent">*</span></label>
        <input type="number" id="min_stock" name="min_stock" value="{{ old('min_stock', $product ? (float) $product->min_stock : 5) }}" required min="0" step="0.1" class="field num">
        <p class="hint">Peringatan muncul saat stok ≤ angka ini.</p>
    </div>

    <div class="sm:pt-7">
        <label class="flex items-center gap-2.5 cursor-pointer select-none">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product?->is_active ?? true)) class="check">
            <span class="text-[13px] font-medium">Aktif dan tampil di kasir</span>
        </label>
    </div>
</div>
