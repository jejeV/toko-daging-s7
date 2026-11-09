<?php

namespace App\Services\Saw;

use App\Enums\AdjustmentType;
use App\Models\Criterion;
use App\Models\Product;
use App\Models\ProductScore;
use App\Models\SaleItem;
use App\Models\StockAdjustment;
use App\Models\SubCriterion;
use InvalidArgumentException;

/**
 * Mengambil data produk + nilai kriteria dari database, lalu menghitung SAW untuk perangkingan produk terlaris & terbaik.
 */
class ProductSawService
{
    /** Kriteria bawaan: [kode, nama, tipe, bobot, satuan]. Total bobot = 1. */
    public const DEFAULT_CRITERIA = [
        ['C1', 'Volume Penjualan (Qty Terjual)', 'benefit', 0.30, null],
        ['C2', 'Total Omzet Penjualan (Rp)', 'benefit', 0.25, 'rp'],
        ['C3', 'Margin Keuntungan (%)', 'benefit', 0.20, 'persen'],
        ['C4', 'Frekuensi Transaksi Kasir', 'benefit', 0.15, 'struk'],
        ['C5', 'Tingkat Susut & Rusak (Kg)', 'cost', 0.10, 'kg'],
    ];

    /**
     * Sub-kriteria bawaan: batas bawah (inklusif) nilai asli untuk rating 5..2; rating 1 = di bawahnya.
     * Dibulatkan dari sebaran data TDN Pasar Cibinong (84 produk, 17 Jul – 4 Okt 2026) supaya tiap kelas terisi.
     * Untuk C5 (cost) rating 5 = susut paling banyak = paling buruk; arahnya dibalik saat normalisasi.
     */
    public const DEFAULT_SUB_CRITERIA = [
        'C1' => [5 => 300, 4 => 170, 3 => 130, 2 => 100],
        'C2' => [5 => 15_000_000, 4 => 8_000_000, 3 => 5_000_000, 2 => 1_000_000],
        'C3' => [5 => 21, 4 => 19, 3 => 16, 2 => 12],
        'C4' => [5 => 230, 4 => 140, 3 => 120, 2 => 95],
        'C5' => [5 => 10, 4 => 3, 3 => 1, 2 => 0.001],
    ];

    public function __construct(private readonly SawCalculator $calculator)
    {
    }

    /**
     * @return array{
     *     criteria: \Illuminate\Support\Collection,
     *     products: \Illuminate\Support\Collection,
     *     incomplete: \Illuminate\Support\Collection,
     *     matrix: array, normalized: array, scores: array,
     *     ranking: list<array{alternative:int, score:float, rank:int, product:Product}>
     * }
     */
    public function calculate(): array
    {
        $criteria = Criterion::query()->with('subCriteria')->orderBy('code')->get();

        // Nilai asli dikonversi ke rating sub-kriteria (1–5) sebelum dihitung SAW.
        $scales = $criteria->mapWithKeys(fn (Criterion $c) => [$c->code => $c->ratingScale()]);
        if ($missing = $scales->filter(fn ($scale) => $scale === null)->keys()->join(', ')) {
            throw new InvalidArgumentException("Sub-kriteria belum diatur untuk {$missing}.");
        }

        // Ambil semua skor produk per criteria
        $scores = ProductScore::query()
            ->with('product.category')
            ->get(['product_id', 'criteria_id', 'value'])
            ->groupBy('product_id');

        // Jika belum ada skor sama sekali di product_scores, lakukan sinkronisasi otomatis dari data penjualan
        if ($scores->isEmpty()) {
            $this->syncFromSalesData();
            $scores = ProductScore::query()
                ->with('product.category')
                ->get(['product_id', 'criteria_id', 'value'])
                ->groupBy('product_id');
        }

        $criteriaInput = $criteria->mapWithKeys(fn (Criterion $c) => [
            $c->code => ['type' => $c->type->value, 'weight' => (float) $c->weight],
        ])->all();

        $codeById = $criteria->pluck('code', 'id');

        $alternatives = [];
        $products = collect();
        $incomplete = collect();

        foreach ($scores as $productId => $rows) {
            $values = [];
            foreach ($rows as $row) {
                if ($code = $codeById->get($row->criteria_id)) {
                    $values[$code] = (float) $row->value;
                }
            }

            $product = $rows->first()->product;

            // Produk yang nilainya belum lengkap tidak ikut dihitung
            if (count($values) < count($criteriaInput)) {
                $incomplete->push($product);
                continue;
            }

            $alternatives[$productId] = $values;
            $products->put($productId, $product);
        }

        $ratings = [];
        foreach ($alternatives as $productId => $values) {
            foreach ($values as $code => $value) {
                $ratings[$productId][$code] = $scales[$code]->rate($value);
            }
        }

        $result = $this->calculator->calculate($criteriaInput, $ratings);

        // Skala 1–5 = V × rating tertinggi, supaya mudah dibaca ("4,4 dari 5").
        $maxRating = (int) $criteria->flatMap->subCriteria->max('rating');

        foreach ($result['ranking'] as &$row) {
            $row['product'] = $products->get($row['alternative']);
            $row['scaled'] = round($row['score'] * $maxRating, 2);
        }
        unset($row);

        return [
            'criteria' => $criteria,
            'products' => $products,
            'incomplete' => $incomplete,
            'raw' => $alternatives,
            'maxRating' => $maxRating,
            ...$result,
        ];
    }

    /**
     * Sinkronisasi nilai kriteria produk secara otomatis berdasarkan data transaksi riil kasir & stok.
     */
    public function syncFromSalesData(): void
    {
        $this->createDefaultCriteria(onlyMissing: true);

        $criteria = Criterion::query()->pluck('id', 'code');

        $products = Product::query()->where('is_active', true)->get();

        foreach ($products as $product) {
            // Nilai asli disimpan apa adanya; 0 sah karena nanti dikonversi ke rating sub-kriteria.
            $sellPrice = (float) $product->sell_price;
            $buyPrice = (float) $product->avg_buy_price;

            // C1: total qty terjual
            $qtySold = (float) SaleItem::where('product_id', $product->id)->sum('qty');

            // C2: total omzet (Rp)
            $totalRevenue = (float) SaleItem::where('product_id', $product->id)->sum('subtotal');

            // C3: margin (%) = (harga jual − HPP rata-rata) / harga jual; 0 kalau belum pernah dibeli
            $marginPct = $sellPrice > 0 && $buyPrice > 0 ? max(0, ($sellPrice - $buyPrice) / $sellPrice * 100) : 0;

            // C4: jumlah struk yang memuat produk ini
            $frequency = (int) SaleItem::where('product_id', $product->id)->distinct('sale_id')->count('sale_id');

            // C5: total qty susut + rusak
            $susutQty = (float) abs(StockAdjustment::where('product_id', $product->id)
                ->whereIn('type', [AdjustmentType::Susut->value, AdjustmentType::Rusak->value])
                ->sum('qty'));

            $metrics = [
                'C1' => round($qtySold, 3),
                'C2' => round($totalRevenue, 2),
                'C3' => round($marginPct, 2),
                'C4' => $frequency,
                'C5' => round($susutQty, 3),
            ];

            foreach ($metrics as $code => $value) {
                if (isset($criteria[$code])) {
                    ProductScore::updateOrCreate(
                        ['product_id' => $product->id, 'criteria_id' => $criteria[$code]],
                        ['value' => $value]
                    );
                }
            }
        }
    }

    /**
     * Buat kriteria & sub-kriteria bawaan.
     *
     * @param  bool  $onlyMissing  true: hanya lengkapi yang belum ada; pengaturan yang sudah diubah tetap dipertahankan.
     */
    public function createDefaultCriteria(bool $onlyMissing = false): void
    {
        foreach (self::DEFAULT_CRITERIA as [$code, $name, $type, $weight, $unit]) {
            $criterion = $onlyMissing
                ? Criterion::firstOrCreate(['code' => $code], compact('name', 'type', 'weight', 'unit'))
                : Criterion::updateOrCreate(['code' => $code], compact('name', 'type', 'weight', 'unit'));

            if ($criterion->unit === null && $unit !== null) {
                $criterion->update(['unit' => $unit]);
            }

            if ($onlyMissing && $criterion->subCriteria()->exists()) {
                continue;
            }

            foreach (self::DEFAULT_SUB_CRITERIA[$code] + [1 => null] as $rating => $min) {
                SubCriterion::updateOrCreate(['criteria_id' => $criterion->id, 'rating' => $rating], ['min_value' => $min]);
            }
        }
    }
}
