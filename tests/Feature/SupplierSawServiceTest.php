<?php

namespace Tests\Feature;

use App\Models\Criterion;
use App\Models\Supplier;
use App\Models\SupplierScore;
use App\Services\Saw\SupplierSawService;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SawSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierSawServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ranking_dari_database_sama_dengan_prd(): void
    {
        $this->seed([MasterDataSeeder::class, SawSeeder::class]);

        $result = app(SupplierSawService::class)->calculate();

        $this->assertSame(
            ['Supplier C', 'Supplier A', 'Supplier B'],
            array_map(fn ($row) => $row['supplier']->name, $result['ranking']),
        );
        $this->assertSame([0.9027, 0.8775, 0.7183], array_column($result['ranking'], 'score'));
    }

    public function test_supplier_dengan_nilai_belum_lengkap_tidak_ikut_dihitung(): void
    {
        $this->seed([MasterDataSeeder::class, SawSeeder::class]);

        $baru = Supplier::create(['name' => 'Supplier Baru']);
        SupplierScore::create([
            'supplier_id' => $baru->id,
            'criteria_id' => Criterion::where('code', 'C1')->value('id'),
            'value' => 110000,
        ]);

        $result = app(SupplierSawService::class)->calculate();

        $this->assertCount(3, $result['ranking']);
        $this->assertTrue($result['incomplete']->contains('name', 'Supplier Baru'));
    }
}
