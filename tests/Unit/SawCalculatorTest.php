<?php

namespace Tests\Unit;

use App\Services\Saw\SawCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SawCalculatorTest extends TestCase
{
    private function criteria(): array
    {
        return [
            'C1' => ['type' => 'cost', 'weight' => 0.30],
            'C2' => ['type' => 'benefit', 'weight' => 0.30],
            'C3' => ['type' => 'benefit', 'weight' => 0.20],
            'C4' => ['type' => 'cost', 'weight' => 0.10],
            'C5' => ['type' => 'cost', 'weight' => 0.10],
        ];
    }

    private function alternatives(): array
    {
        return [
            'A' => ['C1' => 120000, 'C2' => 4, 'C3' => 5, 'C4' => 10, 'C5' => 8],
            'B' => ['C1' => 115000, 'C2' => 3, 'C3' => 4, 'C4' => 20, 'C5' => 15],
            'C' => ['C1' => 125000, 'C2' => 5, 'C3' => 4, 'C4' => 5, 'C5' => 12],
        ];
    }

    public function test_hasil_sama_dengan_contoh_di_prd(): void
    {
        $result = (new SawCalculator)->calculate($this->criteria(), $this->alternatives());

        $this->assertSame(0.8775, $result['scores']['A']);
        $this->assertSame(0.7183, $result['scores']['B']);
        $this->assertSame(0.9027, $result['scores']['C']);

        $this->assertSame(['C', 'A', 'B'], array_column($result['ranking'], 'alternative'));
        $this->assertSame([1, 2, 3], array_column($result['ranking'], 'rank'));
    }

    public function test_normalisasi_benefit_dan_cost(): void
    {
        $n = (new SawCalculator)->calculate($this->criteria(), $this->alternatives())['normalized'];

        // Cost: min / x
        $this->assertSame(1.0, $n['B']['C1']);      // B termurah
        $this->assertSame(0.92, $n['C']['C1']);     // 115000 / 125000
        // Benefit: x / max
        $this->assertSame(1.0, $n['C']['C2']);      // C kualitas tertinggi
        $this->assertSame(0.6, $n['B']['C2']);      // 3 / 5
    }

    public function test_nilai_sama_mendapat_peringkat_sama(): void
    {
        $criteria = ['C1' => ['type' => 'benefit', 'weight' => 1.0]];
        $alternatives = ['X' => ['C1' => 5], 'Y' => ['C1' => 5], 'Z' => ['C1' => 1]];

        $ranking = (new SawCalculator)->calculate($criteria, $alternatives)['ranking'];

        $this->assertSame([1, 1, 3], array_column($ranking, 'rank'));
    }

    public function test_menolak_total_bobot_bukan_satu(): void
    {
        $criteria = $this->criteria();
        $criteria['C1']['weight'] = 0.5;

        $this->expectException(InvalidArgumentException::class);
        (new SawCalculator)->calculate($criteria, $this->alternatives());
    }

    public function test_menolak_nilai_nol(): void
    {
        $alternatives = $this->alternatives();
        $alternatives['A']['C1'] = 0;

        $this->expectException(InvalidArgumentException::class);
        (new SawCalculator)->calculate($this->criteria(), $alternatives);
    }

    public function test_menolak_nilai_yang_belum_lengkap(): void
    {
        $alternatives = $this->alternatives();
        unset($alternatives['A']['C5']);

        $this->expectException(InvalidArgumentException::class);
        (new SawCalculator)->calculate($this->criteria(), $alternatives);
    }
}
