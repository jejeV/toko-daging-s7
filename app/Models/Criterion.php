<?php

namespace App\Models;

use App\Enums\CriteriaType;
use App\Services\Saw\RatingScale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Criterion extends Model
{
    protected $table = 'criteria';

    protected $fillable = ['code', 'name', 'type', 'weight', 'unit'];

    protected function casts(): array
    {
        return [
            'type' => CriteriaType::class,
            'weight' => 'decimal:4',
        ];
    }

    public function scores(): HasMany
    {
        return $this->hasMany(SupplierScore::class, 'criteria_id');
    }

    public function productScores(): HasMany
    {
        return $this->hasMany(ProductScore::class, 'criteria_id');
    }

    public function subCriteria(): HasMany
    {
        return $this->hasMany(SubCriterion::class, 'criteria_id')->orderByDesc('rating');
    }

    /** Skala rating dari sub-kriteria; null kalau sub-kriteria belum diatur. */
    public function ratingScale(): ?RatingScale
    {
        $bounds = $this->subCriteria->pluck('min_value', 'rating')->all();

        return count($bounds) >= 2 && in_array(null, $bounds, true) ? new RatingScale($bounds) : null;
    }
}
