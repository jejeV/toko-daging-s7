<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $fillable = ['purchase_id', 'product_id', 'qty', 'buy_price', 'subtotal', 'expired_at'];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:3',
            'buy_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'expired_at' => 'date',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
