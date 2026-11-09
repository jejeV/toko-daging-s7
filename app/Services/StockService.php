<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu-satunya tempat yang boleh mengubah products.stock.
 * Setiap perubahan stok selalu menghasilkan satu baris stock_movements.
 *
 * Wajib dipanggil di dalam DB::transaction() dengan produk yang sudah di-lock.
 */
class StockService
{
    /**
     * Ambil produk dengan row lock, diurutkan per id supaya urutan lock selalu sama (menghindari deadlock).
     *
     * @param  array<int>  $productIds
     * @return Collection<int, Product>  keyed by id
     */
    public function lockProducts(array $productIds): Collection
    {
        $ids = array_values(array_unique($productIds));
        sort($ids);

        return Product::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  float  $delta  bertanda: positif menambah stok, negatif mengurangi.
     */
    public function apply(
        Product $product,
        StockMovementType $type,
        float $delta,
        Model $reference,
        ?string $note = null,
    ): StockMovement {
        $current = (float) $product->stock;
        $newBalance = round($current + $delta, 3);

        if ($newBalance < 0) {
            throw InsufficientStockException::for($product, abs($delta));
        }

        $product->stock = $newBalance;
        $product->save();

        return StockMovement::create([
            'product_id' => $product->id,
            'type' => $type,
            'qty' => round($delta, 3),
            'balance_after' => $newBalance,
            'reference_type' => $reference->getMorphClass(),
            'reference_id' => $reference->getKey(),
            'note' => $note,
        ]);
    }
}
