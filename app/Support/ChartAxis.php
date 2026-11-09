<?php

namespace App\Support;

/**
 * Sumbu nilai grafik: batas atas bulat dan garis bantu yang jatuh di angka rapi.
 */
class ChartAxis
{
    /** Batas atas: 1; 2; 2,5; atau 5 × 10^n yang ≥ $max. */
    public static function ceiling(float $max): float
    {
        if ($max <= 0) {
            return 1;
        }

        $magnitude = 10 ** floor(log10($max));
        foreach ([1, 2, 2.5, 5, 10] as $step) {
            if ($step * $magnitude >= $max - 1e-9) {
                return $step * $magnitude;
            }
        }

        return 10 * $magnitude;
    }

    /**
     * Nilai garis bantu dari 0 sampai $ceiling. Jumlah pembagian dipilih supaya tiap langkah bulat:
     * 1 → per 0,25 · 2 → per 0,5 · 2,5 → per 0,5 · 5 → per 1.
     *
     * @return list<float>
     */
    public static function ticks(float $ceiling): array
    {
        $lead = round($ceiling / 10 ** floor(log10($ceiling)), 2);
        $divisions = in_array($lead, [2.5, 5.0], true) ? 5 : 4;

        return array_map(fn ($i) => $ceiling * $i / $divisions, range(0, $divisions));
    }
}
