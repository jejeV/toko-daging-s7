<?php

namespace App\Models;

use App\Enums\ProductUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    // stock dan avg_buy_price sengaja tidak fillable: hanya boleh diubah lewat StockService / PurchaseService.
    protected $fillable = [
        'category_id',
        'code',
        'name',
        'unit',
        'sell_price',
        'min_stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'unit' => ProductUnit::class,
            'sell_price' => 'decimal:2',
            'avg_buy_price' => 'decimal:2',
            'stock' => 'decimal:3',
            'min_stock' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(ProductScore::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }
}
