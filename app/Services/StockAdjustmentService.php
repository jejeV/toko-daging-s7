<?php

namespace App\Services;

use App\Enums\AdjustmentType;
use App\Enums\StockMovementType;
use App\Exceptions\BusinessRuleException;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StockAdjustmentService
{
    public function __construct(private readonly StockService $stock)
    {
    }

    /**
     * @param  float  $qty  Susut/Rusak: jumlah yang hilang (positif).
     *                      Opname: jumlah stok fisik hasil hitung.
     */
    public function record(User $user, int $productId, AdjustmentType $type, float $qty, string $reason): StockAdjustment
    {
        if (trim($reason) === '') {
            throw new BusinessRuleException('Alasan penyesuaian wajib diisi.');
        }

        if ($qty < 0) {
            throw new BusinessRuleException('Qty tidak boleh negatif.');
        }

        if ($type !== AdjustmentType::Opname && $qty == 0) {
            throw new BusinessRuleException('Qty susut/rusak harus lebih dari 0.');
        }

        return DB::transaction(function () use ($user, $productId, $type, $qty, $reason) {
            $product = $this->stock->lockProducts([$productId])->get($productId)
                ?? throw new BusinessRuleException("Produk #{$productId} tidak ditemukan.");

            $delta = $type === AdjustmentType::Opname
                ? round($qty - (float) $product->stock, 3)
                : -round($qty, 3);

            $adjustment = StockAdjustment::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'type' => $type,
                'qty' => $delta,
                'reason' => $reason,
            ]);

            if ($delta != 0) {
                $this->stock->apply($product, StockMovementType::Adjust, $delta, $adjustment, $type->label().': '.$reason);
            }

            return $adjustment;
        });
    }
}
