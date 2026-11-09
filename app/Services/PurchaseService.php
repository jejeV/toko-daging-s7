<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Exceptions\BusinessRuleException;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(private readonly StockService $stock)
    {
    }

    /**
     * @param  array<int, array{product_id:int, qty:float|string, buy_price:float|string, expired_at?:string|null}>  $items
     */
    public function create(User $user, int $supplierId, string $purchaseDate, array $items, ?string $note = null): Purchase
    {
        if ($items === []) {
            throw new BusinessRuleException('Pembelian harus berisi minimal satu barang.');
        }

        foreach ($items as $item) {
            if ((float) $item['qty'] <= 0) {
                throw new BusinessRuleException('Qty pembelian harus lebih dari 0.');
            }
            if ((float) $item['buy_price'] < 0) {
                throw new BusinessRuleException('Harga beli tidak boleh negatif.');
            }
        }

        return DB::transaction(function () use ($user, $supplierId, $purchaseDate, $items, $note) {
            $products = $this->stock->lockProducts(array_column($items, 'product_id'));

            $purchase = Purchase::create([
                'supplier_id' => $supplierId,
                'user_id' => $user->id,
                'purchase_date' => $purchaseDate,
                'total' => 0,
                'note' => $note,
            ]);

            $total = 0.0;

            foreach ($items as $item) {
                $product = $products->get($item['product_id'])
                    ?? throw new BusinessRuleException("Produk #{$item['product_id']} tidak ditemukan.");

                $qty = round((float) $item['qty'], 3);
                $price = round((float) $item['buy_price'], 2);
                $subtotal = round($qty * $price, 2);

                $purchase->items()->create([
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'buy_price' => $price,
                    'subtotal' => $subtotal,
                    'expired_at' => $item['expired_at'] ?? null,
                ]);

                // Harga beli rata-rata tertimbang (moving average).
                $oldStock = max((float) $product->stock, 0);
                $oldAvg = (float) $product->avg_buy_price;
                $product->avg_buy_price = round(($oldStock * $oldAvg + $qty * $price) / ($oldStock + $qty), 2);

                $this->stock->apply($product, StockMovementType::In, $qty, $purchase, 'Pembelian');

                $total += $subtotal;
            }

            $purchase->update(['total' => round($total, 2)]);

            return $purchase->load('items');
        });
    }
}
