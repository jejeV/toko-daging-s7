<?php

namespace App\Services\Saw;

use App\Support\Fmt;

/**
 * Konversi nilai asli kriteria menjadi rating sub-kriteria (mis. 1–5).
 *
 * Tiap rating punya batas bawah inklusif: nilai >= batas rating itu, tapi < batas rating di atasnya.
 * Rating terendah tidak punya batas bawah (null), jadi setiap nilai pasti mendapat rating.
 *
 * Murni PHP, tidak bergantung database.
 */
class RatingScale
{
    /** @param  array<int, float|null>  $bounds  [rating => batas bawah], mis. [5 => 300, 4 => 170, 3 => 130, 2 => 100, 1 => null] */
    public function __construct(private readonly array $bounds)
    {
    }

    public function rate(float $value): int
    {
        $bounds = $this->bounds;
        krsort($bounds);

        foreach ($bounds as $rating => $min) {
            if ($min === null || $value >= $min) {
                return (int) $rating;
            }
        }

        return (int) min(array_keys($bounds));
    }

    /**
     * Label rentang tiap rating untuk tabel sub-kriteria, mis. [5 => '≥ 300', 4 => '170 – < 300', 1 => '< 100'].
     *
     * @return array<int, string>
     */
    public function labels(?string $unit = null): array
    {
        $bounds = $this->bounds;
        krsort($bounds);
        $ratings = array_keys($bounds);
        $labels = [];

        foreach ($ratings as $i => $rating) {
            $min = $bounds[$rating];
            $upper = $i > 0 ? $bounds[$ratings[$i - 1]] : null; // batas rating di atasnya

            $labels[$rating] = match (true) {
                $min === null && $upper !== null && $upper <= 0.001 => 'Tidak ada (0)',
                $min === null => '< '.self::format($upper, $unit),
                $upper === null => '≥ '.self::format($min, $unit),
                default => ($min <= 0.001 ? '> 0' : self::format($min, $unit)).' – < '.self::format($upper, $unit),
            };
        }

        return $labels;
    }

    public static function format(?float $value, ?string $unit): string
    {
        return match ($unit) {
            'rp' => Fmt::rupiah($value),
            'persen' => Fmt::qty($value).'%',
            null, '' => Fmt::qty($value),
            default => Fmt::qty($value).' '.$unit,
        };
    }
}
