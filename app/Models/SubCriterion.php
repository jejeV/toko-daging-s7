<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubCriterion extends Model
{
    protected $table = 'sub_criteria';

    protected $fillable = ['criteria_id', 'rating', 'min_value'];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'min_value' => 'float',
        ];
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class, 'criteria_id');
    }
}
