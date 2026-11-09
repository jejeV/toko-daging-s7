<?php

namespace App\Enums;

enum ProductUnit: string
{
    case Kg = 'kg';
    case Pcs = 'pcs';

    public function allowsDecimal(): bool
    {
        return $this === self::Kg;
    }
}
