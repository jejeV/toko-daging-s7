<?php

namespace App\Services\Saw;

use InvalidArgumentException;

/**
 * Simple Additive Weighting (SAW).
 *
 * Kelas ini murni PHP (tidak bergantung Laravel/database) supaya mudah dites dan dijelaskan saat sidang.
 *
 *   Benefit : r_ij = x_ij / max(x_j)
 *   Cost    : r_ij = min(x_j) / x_ij
 *   Nilai   : V_i  = Σ w_j * r_ij
 */
class SawCalculator
{
    private const WEIGHT_TOLERANCE = 0.0001;

    /**
     * @param  array<string, array{type:string, weight:float}>  $criteria       ['C1' => ['type' => 'cost', 'weight' => 0.3], ...]
     * @param  array<int|string, array<string, float>>          $alternatives   ['A' => ['C1' => 120000, ...], ...]
     * @return array{
     *     matrix: array<int|string, array<string, float>>,
     *     normalized: array<int|string, array<string, float>>,
     *     scores: array<int|string, float>,
     *     ranking: list<array{alternative:int|string, score:float, rank:int}>
     * }
     */
    public function calculate(array $criteria, array $alternatives, int $precision = 4): array
    {
        $this->validate($criteria, $alternatives);

        // Cari nilai max/min tiap kriteria sekali saja.
        $extremes = [];
        foreach ($criteria as $code => $c) {
            $column = array_map(fn ($alt) => (float) $alt[$code], $alternatives);
            $extremes[$code] = ['max' => max($column), 'min' => min($column)];
        }

        $normalized = [];
        $scores = [];

        foreach ($alternatives as $key => $values) {
            $score = 0.0;

            foreach ($criteria as $code => $c) {
                $x = (float) $values[$code];

                $r = $c['type'] === 'benefit'
                    ? $x / $extremes[$code]['max']
                    : $extremes[$code]['min'] / $x;

                $normalized[$key][$code] = round($r, $precision);
                $score += (float) $c['weight'] * $r; // pakai r tanpa pembulatan supaya hasil akhir akurat
            }

            $scores[$key] = round($score, $precision);
        }

        $ranking = [];
        foreach ($scores as $key => $score) {
            $ranking[] = ['alternative' => $key, 'score' => $score];
        }
        usort($ranking, fn ($a, $b) => $b['score'] <=> $a['score']);

        // Nilai sama -> peringkat sama (1, 2, 2, 4).
        $previousScore = null;
        $previousRank = 0;
        foreach ($ranking as $i => &$row) {
            $row['rank'] = $row['score'] === $previousScore ? $previousRank : $i + 1;
            $previousScore = $row['score'];
            $previousRank = $row['rank'];
        }
        unset($row);

        return [
            'matrix' => $alternatives,
            'normalized' => $normalized,
            'scores' => $scores,
            'ranking' => $ranking,
        ];
    }

    private function validate(array $criteria, array $alternatives): void
    {
        if ($criteria === []) {
            throw new InvalidArgumentException('Kriteria SAW belum diisi.');
        }

        if ($alternatives === []) {
            throw new InvalidArgumentException('Belum ada alternatif dengan nilai lengkap.');
        }

        $totalWeight = 0.0;
        foreach ($criteria as $code => $c) {
            if (! in_array($c['type'], ['benefit', 'cost'], true)) {
                throw new InvalidArgumentException("Jenis kriteria {$code} harus benefit atau cost.");
            }
            $totalWeight += (float) $c['weight'];
        }

        if (abs($totalWeight - 1.0) > self::WEIGHT_TOLERANCE) {
            throw new InvalidArgumentException(sprintf('Total bobot harus 1, sekarang %.4f.', $totalWeight));
        }

        foreach ($alternatives as $key => $values) {
            foreach ($criteria as $code => $c) {
                if (! array_key_exists($code, $values)) {
                    throw new InvalidArgumentException("Alternatif {$key} belum punya nilai {$code}.");
                }
                // Nilai 0 membuat pembagian tidak valid (cost) atau max = 0 (benefit).
                if ((float) $values[$code] <= 0) {
                    throw new InvalidArgumentException("Nilai {$code} untuk alternatif {$key} harus lebih dari 0.");
                }
            }
        }
    }
}
