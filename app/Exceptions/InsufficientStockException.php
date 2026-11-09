<?php

namespace App\Exceptions;

use App\Models\Product;

class InsufficientStockException extends BusinessRuleException
{
    public static function for(Product $product, float $requested): self
    {
        return new self(sprintf(
            'Stok %s tidak cukup. Tersedia %s %s, diminta %s %s.',
            $product->name,
            rtrim(rtrim(number_format((float) $product->stock, 3, ',', '.'), '0'), ','),
            $product->unit->value,
            rtrim(rtrim(number_format($requested, 3, ',', '.'), '0'), ','),
            $product->unit->value,
        ));
    }
}
