<?php

namespace App\Enums;

enum AdjustmentType: string
{
    case Susut = 'susut';
    case Rusak = 'rusak';
    case Opname = 'opname';

    public function label(): string
    {
        return match ($this) {
            self::Susut => 'Susut',
            self::Rusak => 'Rusak / busuk',
            self::Opname => 'Stok opname',
        };
    }
}
