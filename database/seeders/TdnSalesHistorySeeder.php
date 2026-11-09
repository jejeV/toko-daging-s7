<?php

namespace Database\Seeders;

use App\Enums\AdjustmentType;
use App\Enums\ProductUnit;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockAdjustment;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use App\Services\SaleService;
use App\Services\StockAdjustmentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;


class TdnSalesHistorySeeder extends Seeder
{
    /** Kategori => [supplier, margin kotor, umur simpan (hari)] */
    private const CATEGORY = [
        'Daging sapi' => ['CV Sapi Segar Bogor', 0.20, 4],
        'Daging kerbau' => ['CV Sapi Segar Bogor', 0.18, 60],
        'Ayam & telur' => ['PT Unggas Prima Cibinong', 0.15, 3],
        'Ikan' => ['UD Bahari Ikan Muara', 0.22, 60],
        'Olahan beku' => ['Distributor Frozen Sentul', 0.20, 180],
        'Sembako & bumbu' => ['Grosir Sembako Cibinong', 0.10, 365],
        'Susu & minuman' => ['Grosir Sembako Cibinong', 0.15, 120],
        'Perlengkapan' => ['Grosir Sembako Cibinong', 0.40, null],
        'Buah & sayur' => ['UD Buah Segar Kramat Jati', 0.25, 7],
    ];

    /** Ukuran beli per struk dalam gram (bobot), untuk produk kiloan. */
    private const KG_PORTIONS = [250 => 4, 500 => 20, 750 => 8, 1000 => 34, 1500 => 12, 2000 => 14, 3000 => 6, 5000 => 2];

    private const PCS_PORTIONS = [1 => 70, 2 => 22, 3 => 6, 5 => 2];

    /** Jumlah baris produk per struk (bobot). */
    private const BASKET = [1 => 38, 2 => 30, 3 => 18, 4 => 9, 5 => 5];

    /** Jam buka 07–21, ramai pagi dan sore. */
    private const HOURS = [7 => 3, 8 => 7, 9 => 10, 10 => 10, 11 => 8, 12 => 5, 13 => 4, 14 => 4, 15 => 6, 16 => 9, 17 => 10, 18 => 9, 19 => 7, 20 => 5];

    /** Cadangan stok setelah restock, dalam hari rata-rata penjualan. */
    private const COVER_DAYS = 4;

    public function run(PurchaseService $purchases, SaleService $sales, StockAdjustmentService $adjustments): void
    {
        $lastSale = Sale::max('sale_date');
        $resumeFrom = $lastSale ? Carbon::parse($lastSale)->toDateString() : null;

        mt_srand($resumeFrom ? crc32($resumeFrom) : 20260717);

        $owner = User::where('email', 'owner@tokodaging.test')->firstOrFail();
        $cashiers = User::whereIn('email', ['kasir@tokodaging.test', 'kasir2@tokodaging.test'])->get()->all();
        $products = Product::with('category')->get()->keyBy('code');
        $suppliers = Supplier::pluck('id', 'name');

        $daily = $this->readDailySales();
        $average = $this->averagePerDay($daily);
        $lastDate = array_key_last($daily);

        if ($resumeFrom) {
            $this->command?->warn("Melanjutkan seeding dari {$resumeFrom}.");
        }

        $this->command?->getOutput()->progressStart(count($daily));

        try {
            foreach ($daily as $date => $quantities) {
                if ($resumeFrom && $date < $resumeFrom) {
                    $this->command?->getOutput()->progressAdvance();
                    continue;
                }

                $shrinkageDone = false;
                if ($date === $resumeFrom) {
                    $quantities = $this->remainingQuantities($date, $quantities);
                    $shrinkageDone = StockAdjustment::whereBetween('created_at', [$date.' 00:00:00', $date.' 23:59:59'])->exists();

                    if ($quantities === [] && $shrinkageDone) {
                        $this->command?->getOutput()->progressAdvance();
                        continue; // hari ini sudah lengkap
                    }
                }

                $day = Carbon::parse($date);


                DB::transaction(function () use ($purchases, $sales, $adjustments, $owner, $cashiers, $suppliers, $products, $quantities, $average, $day, $date, $lastDate, $shrinkageDone) {
                    $this->restock($purchases, $owner, $suppliers, $products, $quantities, $average, $day);
                    $this->sellDay($sales, $cashiers, $products, $quantities, $day);
                    if (! $shrinkageDone) {
                        $this->recordShrinkage($adjustments, $owner, $products, $day, $date === $lastDate);
                    }
                });

                $this->command?->getOutput()->progressAdvance();
            }
        } finally {
            Carbon::setTestNow();
            $this->command?->getOutput()->progressFinish();
        }

        $this->command?->info(sprintf(
            'TDN Pasar Cibinong: %s struk, omzet Rp %s (%d hari).',
            number_format(Sale::count(), 0, ',', '.'),
            number_format((float) Sale::sum('total'), 0, ',', '.'),
            count($daily),
        ));
    }

    /**
     * Setiap pagi, produk yang stoknya kurang dari kebutuhan hari ini + 1 hari cadangan dibeli lagi
     * sampai cukup untuk hari ini + COVER_DAYS. Satu faktur per supplier.
     */
    private function restock(PurchaseService $purchases, User $owner, Collection $suppliers, Collection $products, array $quantities, array $average, Carbon $day): void
    {
        $stock = Product::pluck('stock', 'code');
        $orders = [];

        foreach ($products as $code => $product) {
            $need = $quantities[$code] ?? 0;
            $avg = $average[$code] ?? 0;
            $current = (float) $stock[$code];

            if ($current >= $need + $avg) {
                continue;
            }

            $qty = $need + $avg * self::COVER_DAYS - $current;
            $qty = $product->unit === ProductUnit::Pcs ? (int) ceil($qty) : ceil($qty * 2) / 2;

            if ($qty <= 0) {
                continue;
            }

            [$supplier, $margin, $shelfLife] = self::CATEGORY[$product->category->name];
            $price = round((float) $product->sell_price * (1 - $margin) * (1 + $this->rand(-0.03, 0.03)) / 100) * 100;

            $orders[$supplier][] = [
                'product_id' => $product->id,
                'qty' => $qty,
                'buy_price' => $price,
                'expired_at' => $shelfLife ? $day->copy()->addDays($shelfLife)->toDateString() : null,
            ];
        }

        foreach ($orders as $supplier => $items) {
            Carbon::setTestNow($day->copy()->setTime(6, mt_rand(0, 50)));
            $purchases->create(
                $owner,
                $suppliers[$supplier],
                $day->toDateString(),
                $items,
                sprintf('Surat jalan SJ-%s-%03d', $day->format('ymd'), mt_rand(1, 999)),
            );
        }
    }

    /**
     * Pecah qty harian tiap produk jadi porsi per pembeli, acak, lalu kemas jadi struk.
     * Dihitung dalam milli-satuan (gram / 0,001 pcs) supaya total per produk persis sama dengan Excel.
     */
    private function sellDay(SaleService $sales, array $cashiers, Collection $products, array $quantities, Carbon $day): void
    {
        $portions = [];

        foreach ($quantities as $code => $qty) {
            $isKg = $products[$code]->unit === ProductUnit::Kg;
            $left = (int) round($qty * 1000);

            while ($left > 0) {
                $size = $isKg
                    ? (int) round($this->weighted(self::KG_PORTIONS) * $this->rand(0.92, 1.1) / 10) * 10
                    : $this->weighted(self::PCS_PORTIONS) * 1000;

                // Sisa yang terlalu kecil digabung ke porsi ini.
                if ($left - $size < ($isKg ? 150 : 1000)) {
                    $size = $left;
                }

                $portions[] = [$code, $size];
                $left -= $size;
            }
        }

        shuffle($portions);

        $receipts = [];
        while ($portions !== []) {
            $lines = [];
            foreach (array_splice($portions, 0, $this->weighted(self::BASKET)) as [$code, $milli]) {
                $lines[$code] = ($lines[$code] ?? 0) + $milli;
            }
            $receipts[] = $lines;
        }

        $times = [];
        foreach ($receipts as $_) {
            $times[] = $day->copy()->setTime($this->weighted(self::HOURS), mt_rand(0, 59), mt_rand(0, 59));
        }
        sort($times);

        foreach ($receipts as $i => $lines) {
            $items = [];
            $total = 0.0;

            foreach ($lines as $code => $milli) {
                $qty = $milli / 1000;
                $items[] = ['product_id' => $products[$code]->id, 'qty' => $qty];
                $total += round($qty * (float) $products[$code]->sell_price, 2);
            }

            Carbon::setTestNow($times[$i]);
            $sales->create($cashiers[array_rand($cashiers)], $items, $this->cashPaid(round($total, 2)), $times[$i]);
        }
    }

    /**
     * Susut daging kiloan tiap Senin & Kamis, kerusakan sesekali, opname di akhir bulan dan hari terakhir data.
     */
    private function recordShrinkage(StockAdjustmentService $adjustments, User $owner, Collection $products, Carbon $day, bool $isLastDay): void
    {
        $record = function (string $code, AdjustmentType $type, float $qty, string $reason, int $minute) use ($adjustments, $owner, $products, $day) {
            $stock = (float) $products[$code]->refresh()->stock;
            if ($type === AdjustmentType::Opname) {
                $qty = max(0, $stock - $qty);
            } elseif ($qty > $stock) {
                return;
            }
            Carbon::setTestNow($day->copy()->setTime(21, $minute));
            $adjustments->record($owner, $products[$code]->id, $type, $qty, $reason);
        };

        if (in_array($day->dayOfWeekIso, [1, 4], true)) {
            $record('100001', AdjustmentType::Susut, round($this->rand(0.3, 0.9), 2), 'Susut air daging rendang di chiller', 5);
            $record('100153', AdjustmentType::Susut, round($this->rand(0.3, 0.8), 2), 'Susut trimming lemak daging rendang sengkel', 8);
            $record(['100002', '100004', '100086'][mt_rand(0, 2)], AdjustmentType::Susut, round($this->rand(0.1, 0.3), 2), 'Susut saat potong dan giling', 11);
        }

        if (mt_rand(1, 100) <= 12) {
            [$code, $qty, $reason] = [
                ['100362', 1, 'Ayam fresh berbau, tidak layak jual'],
                ['101050', 0.5, 'Telur pecah saat bongkar muat'],
                ['106436', 1, 'Anggur busuk sebagian'],
                ['100636', 3, 'Jamur enoki layu'],
                ['101030', 1, 'Kemasan bakso bocor, cair di freezer'],
            ][mt_rand(0, 4)];
            $record($code, AdjustmentType::Rusak, $qty, $reason, 15);
        }

        if ($day->isLastOfMonth() || $isLastDay) {
            // Qty opname di sini = selisih kurang dari stok sistem.
            $record('103432', AdjustmentType::Opname, 3, 'Opname: tas belanja terpakai untuk kemas ulang', 30);
            $record('102573', AdjustmentType::Opname, 1, 'Opname: 1 botol penyok dibuang', 32);
            $record('104519', AdjustmentType::Opname, 0.4, 'Opname: selisih timbang jeruk', 34);
        }
    }

    /** Qty yang belum terjual pada tanggal itu (untuk melanjutkan hari yang terpotong). */
    private function remainingQuantities(string $date, array $quantities): array
    {
        $sold = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereBetween('sales.sale_date', [$date.' 00:00:00', $date.' 23:59:59'])
            ->groupBy('products.code')
            ->selectRaw('products.code, SUM(sale_items.qty) AS qty')
            ->pluck('qty', 'code');

        $remaining = [];
        foreach ($quantities as $code => $qty) {
            $left = round($qty - (float) ($sold[$code] ?? 0), 3);
            if ($left > 0.0005) {
                $remaining[$code] = $left;
            }
        }

        return $remaining;
    }

    /** @return array<string, array<string, float>> tanggal => [icode => qty] */
    private function readDailySales(): array
    {
        $handle = fopen(database_path('seeders/data/tdn-penjualan-harian.csv'), 'r');
        fgetcsv($handle);
        $daily = [];

        while (($line = fgetcsv($handle)) !== false) {
            [$date, $code, $qty] = $line;
            $daily[$date][$code] = (float) $qty;
        }

        fclose($handle);
        ksort($daily);

        return $daily;
    }

    /** @return array<string, float> rata-rata qty per hari tiap produk */
    private function averagePerDay(array $daily): array
    {
        $sum = [];
        foreach ($daily as $quantities) {
            foreach ($quantities as $code => $qty) {
                $sum[$code] = ($sum[$code] ?? 0) + $qty;
            }
        }

        return array_map(fn ($total) => $total / count($daily), $sum);
    }

    private function weighted(array $weights): int
    {
        $roll = mt_rand(1, array_sum($weights));
        foreach ($weights as $value => $weight) {
            if (($roll -= $weight) <= 0) {
                return $value;
            }
        }

        return array_key_last($weights);
    }

    /** Pembeli kadang bayar pas, kadang dibulatkan ke pecahan uang terdekat. */
    private function cashPaid(float $total): float
    {
        $roll = mt_rand(1, 100);

        return match (true) {
            $roll <= 35 => $total,
            $roll <= 65 => ceil($total / 10000) * 10000,
            $roll <= 88 => ceil($total / 50000) * 50000,
            default => ceil($total / 100000) * 100000,
        };
    }

    private function rand(float $min, float $max): float
    {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }
}
