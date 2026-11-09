<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk {{ $sale->invoice_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 12px; max-width: 300px; margin: 0 auto; padding: 16px; color: #111; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .line { border-top: 1px dashed #999; margin: 8px 0; }
        .row { display: flex; justify-content: space-between; }
        .mt { margin-top: 4px; }
        .actions { display: flex; justify-content: center; gap: 8px; margin: 8px auto 28px; font-family: system-ui, sans-serif; }
        .actions a, .actions button { display: inline-flex; align-items: center; height: 36px; padding: 0 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; cursor: pointer; border: 1px solid #d2cbc2; background: #fff; color: #1c1917; }
        .actions button { background: #c4161c; border-color: #c4161c; color: #fff; }
        .actions button:hover { background: #a31016; }
        @media print { .actions { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="actions">
        <a href="{{ route('pos.index') }}">← Kasir</a>
        <button onclick="window.print()">Cetak struk</button>
    </div>

    <div class="center bold" style="font-size: 14px;">TOKO DAGING SEGAR</div>
    <div class="center" style="font-size: 10px; color: #666; margin-top: 2px;">Jl. Pasar Daging No. 12</div>

    <div class="line"></div>

    <div class="row"><span>No:</span><span class="bold">{{ $sale->invoice_no }}</span></div>
    <div class="row"><span>Waktu:</span><span>{{ $sale->sale_date->format('d/m/Y H:i') }}</span></div>
    <div class="row"><span>Kasir:</span><span>{{ $sale->user->name }}</span></div>

    <div class="line"></div>

    @foreach ($sale->items as $item)
        <div class="bold">{{ $item->product->name }}</div>
        <div class="row mt">
            <span>{{ (float)$item->qty }} {{ $item->product->unit->value }} × {{ number_format($item->price, 0, ',', '.') }}</span>
            <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
        </div>
    @endforeach

    <div class="line"></div>

    <div class="row bold"><span>TOTAL</span><span>Rp {{ number_format($sale->total, 0, ',', '.') }}</span></div>
    <div class="row mt"><span>Tunai</span><span>Rp {{ number_format($sale->paid, 0, ',', '.') }}</span></div>
    <div class="row"><span>Kembali</span><span>Rp {{ number_format($sale->change, 0, ',', '.') }}</span></div>

    <div class="line"></div>

    <div class="center" style="font-size: 10px; color: #666;">Terima kasih!</div>
</body>
</html>
