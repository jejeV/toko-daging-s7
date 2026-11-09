<?php

namespace App\Support;

/**
 * Format angka gaya Indonesia untuk tampilan (titik ribuan, koma desimal).
 */
class Fmt
{
    public static function rupiah(float|int|string|null $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }

    /** Angka ringkas untuk sumbu grafik & label sempit: 850 rb, 1,5 jt, 2,3 M. */
    public static function short(float|int|string|null $value): string
    {
        $value = (float) $value;

        return match (true) {
            abs($value) >= 1e9 => self::trim($value / 1e9).' M',
            abs($value) >= 1e6 => self::trim($value / 1e6).' jt',
            abs($value) >= 1e3 => number_format($value / 1e3, 0, ',', '.').' rb',
            default => number_format($value, 0, ',', '.'),
        };
    }

    /** Qty tanpa nol di belakang koma: 12,5 / 3 / 0,25. */
    public static function qty(float|int|string|null $value, int $decimals = 2): string
    {
        return self::trim((float) $value, $decimals);
    }

    public static function percent(float|int|null $value, int $decimals = 1): string
    {
        return number_format((float) $value, $decimals, ',', '.').'%';
    }

    private static function trim(float $value, int $decimals = 1): string
    {
        $formatted = number_format($value, $decimals, ',', '.');

        return str_contains($formatted, ',') ? rtrim(rtrim($formatted, '0'), ',') : $formatted;
    }
}
