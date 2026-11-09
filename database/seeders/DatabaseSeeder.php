<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            MasterDataSeeder::class,
            TdnSalesHistorySeeder::class,
            // Terakhir, supaya nilai kriteria SAW dihitung dari seluruh transaksi di atas.
            SawSeeder::class,
        ]);
    }
}
