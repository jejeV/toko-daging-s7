<?php

namespace Database\Seeders;

use App\Services\Saw\ProductSawService;
use Illuminate\Database\Seeder;

class SawSeeder extends Seeder
{
    public function run(ProductSawService $sawService): void
    {
        $sawService->createDefaultCriteria();

        $sawService->syncFromSalesData();
    }
}
