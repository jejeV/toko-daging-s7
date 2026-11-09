<?php

namespace Tests\Unit;

use App\Services\Saw\RatingScale;
use PHPUnit\Framework\TestCase;

class RatingScaleTest extends TestCase
{
    private function c1(): RatingScale
    {
        return new RatingScale([5 => 300, 4 => 170, 3 => 130, 2 => 100, 1 => null]);
    }

    public function test_nilai_masuk_rating_sesuai_batas_bawah_inklusif(): void
    {
        $scale = $this->c1();

        $this->assertSame(5, $scale->rate(2457.36));
        $this->assertSame(5, $scale->rate(300));   // tepat di batas -> rating itu
        $this->assertSame(4, $scale->rate(299.99));
        $this->assertSame(3, $scale->rate(130));
        $this->assertSame(2, $scale->rate(100));
        $this->assertSame(1, $scale->rate(99.9));
        $this->assertSame(1, $scale->rate(0));
    }

    public function test_urutan_array_tidak_berpengaruh(): void
    {
        $scale = new RatingScale([1 => null, 3 => 130, 5 => 300, 2 => 100, 4 => 170]);

        $this->assertSame(4, $scale->rate(200));
    }

    public function test_cost_susut_nol_mendapat_rating_terendah(): void
    {
        $c5 = new RatingScale([5 => 10, 4 => 3, 3 => 1, 2 => 0.001, 1 => null]);

        $this->assertSame(1, $c5->rate(0));
        $this->assertSame(2, $c5->rate(0.5));
        $this->assertSame(3, $c5->rate(1));
        $this->assertSame(5, $c5->rate(12.8));
    }

    public function test_label_rentang_untuk_tabel_sub_kriteria(): void
    {
        $this->assertSame(
            [5 => '≥ 300', 4 => '170 – < 300', 3 => '130 – < 170', 2 => '100 – < 130', 1 => '< 100'],
            $this->c1()->labels(),
        );

        $c5 = new RatingScale([5 => 10, 4 => 3, 3 => 1, 2 => 0.001, 1 => null]);
        $this->assertSame('Tidak ada (0)', $c5->labels('kg')[1]);
        $this->assertSame('> 0 – < 1 kg', $c5->labels('kg')[2]);
        $this->assertSame('≥ 10 kg', $c5->labels('kg')[5]);
    }
}
