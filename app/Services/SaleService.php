<?php

namespace App\Services;

use App\Enums\ProductUnit;
use App\Enums\StockMovementType;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\InsufficientStockException;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(private readonly StockService $stock)
    {
    }

    /**
     * Simpan satu transaksi kasir. Semua langkah (sale, item, stok) sukses bersama atau gagal bersama.
     *
     * @param  array<int, array{product_id:int, qty:float|string}>  $items
     */
    public function create(User $cashier, array $items, float $paid, ?Carbon $at = null): Sale
    {
        if ($items === []) {
            throw new BusinessRuleException('Keranjang masih kosong.');
        }

        $at ??= now();

        return DB::transaction(function () use ($cashier, $items, $paid, $at) {
            $products = $this->stock->lockProducts(array_column($items, 'product_id'));

            // 1. Validasi dan hitung dulu, sebelum menulis apa pun.
            $lines = [];
            $total = 0.0;

            foreach ($items as $item) {
                $product = $products->get($item['product_id'])
                    ?? throw new BusinessRuleException("Produk #{$item['product_id']} tidak ditemukan.");

                if (! $product->is_active) {
                    throw new BusinessRuleException("Produk {$product->name} sudah tidak aktif.");
                }

                $qty = round((float) $item['qty'], 3);

                if ($qty <= 0) {
                    throw new BusinessRuleException("Qty {$product->name} harus lebih dari 0.");
                }

                if ($product->unit === ProductUnit::Pcs && floor($qty) != $qty) {
                    throw new BusinessRuleException("{$product->name} dijual per pcs, qty harus bilangan bulat.");
                }

                $price = (float) $product->sell_price;
                $subtotal = round($qty * $price, 2);

                $lines[] = compact('product', 'qty', 'price', 'subtotal');
                $total += $subtotal;
            }

            // Cek stok per produk (produk yang sama bisa muncul di beberapa baris).
            $requested = [];
            foreach ($lines as $line) {
                $id = $line['product']->id;
                $requested[$id] = round(($requested[$id] ?? 0) + $line['qty'], 3);
            }
            foreach ($requested as $id => $qty) {
                $product = $products->get($id);
                if ($qty > (float) $product->stock) {
                    throw InsufficientStockException::for($product, $qty);
                }
            }

            $total = round($total, 2);

            if (round($paid, 2) < $total) {
                throw new BusinessRuleException(sprintf(
                    'Uang bayar kurang. Total Rp %s, dibayar Rp %s.',
                    number_format($total, 0, ',', '.'),
                    number_format($paid, 0, ',', '.'),
                ));
            }

            // 2. Tulis transaksi.
            $sale = Sale::create([
                'user_id' => $cashier->id,
                'invoice_no' => $this->nextInvoiceNumber($at),
                'sale_date' => $at,
                'total' => $total,
                'paid' => round($paid, 2),
                'change' => round($paid - $total, 2),
            ]);

            foreach ($lines as $line) {
                $sale->items()->create([
                    'product_id' => $line['product']->id,
                    'qty' => $line['qty'],
                    'price' => $line['price'],
                    'buy_price' => $line['product']->avg_buy_price,
                    'subtotal' => $line['subtotal'],
                ]);

                $this->stock->apply($line['product'], StockMovementType::Out, -$line['qty'], $sale, 'Penjualan');
            }

            return $sale->load('items.product');
        });
    }

    /**
     * Format: INV-YYYYMMDD-0001, reset setiap hari.
     */
    private function nextInvoiceNumber(Carbon $at): string
    {
        $prefix = 'INV-'.$at->format('Ymd').'-';

        $last = Sale::query()
            ->where('invoice_no', 'like', $prefix.'%')
            ->orderByDesc('invoice_no')
            ->lockForUpdate()
            ->value('invoice_no');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
