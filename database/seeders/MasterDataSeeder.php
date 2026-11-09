<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;


class MasterDataSeeder extends Seeder
{
    /** Nama supplier fiktif; pemetaan kategori => supplier ada di TdnSalesHistorySeeder. */
    public const SUPPLIERS = [
        ['CV Sapi Segar Bogor', '081211000101', 'Jl. Raya Jakarta–Bogor Km 46, Cibinong'],
        ['PT Unggas Prima Cibinong', '081211000102', 'Jl. Tegar Beriman No. 8, Cibinong'],
        ['UD Bahari Ikan Muara', '081211000103', 'Pasar Ikan Muara Baru, Jakarta Utara'],
        ['Distributor Frozen Sentul', '081211000104', 'Kawasan Industri Sentul, Bogor'],
        ['Grosir Sembako Cibinong', '081211000105', 'Pasar Cibinong Blok C, Bogor'],
        ['UD Buah Segar Kramat Jati', '081211000106', 'Pasar Induk Kramat Jati, Jakarta Timur'],
    ];

    public function run(): void
    {
        $rows = $this->readCsv(database_path('seeders/data/tdn-produk.csv'));

        $categories = collect($rows)->pluck('kategori')->unique()
            ->mapWithKeys(fn ($name) => [$name => Category::firstOrCreate(['name' => $name])]);

        // Stok awal 0: stok hanya masuk lewat pembelian, supaya products.stock selalu cocok dengan stock_movements.
        foreach ($rows as $row) {
            $avg = (float) $row['rata_rata_harian'];

            Product::updateOrCreate(['code' => $row['icode']], [
                'category_id' => $categories[$row['kategori']]->id,
                'name' => $row['nama'],
                'unit' => $row['satuan'],
                'sell_price' => (int) $row['harga_jual'],
                // Peringatan saat stok tinggal cukup untuk ±2 hari penjualan.
                'min_stock' => $row['satuan'] === 'kg' ? max(1, round($avg * 2, 1)) : max(2, (int) ceil($avg * 2)),
                'is_active' => true,
            ]);
        }

        foreach (self::SUPPLIERS as [$name, $phone, $address]) {
            Supplier::updateOrCreate(['name' => $name], ['phone' => $phone, 'address' => $address]);
        }
    }

    /** @return list<array<string, string>> */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            $rows[] = array_combine($header, $line);
        }

        fclose($handle);

        return $rows;
    }
}
