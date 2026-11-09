<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use App\Models\Product;
use App\Models\ProductScore;
use App\Models\SubCriterion;
use App\Services\Saw\ProductSawService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SawController extends Controller
{
    public function __construct(private readonly ProductSawService $sawService)
    {
    }

    public function index()
    {
        $sawData = null;
        $error = null;

        try {
            $sawData = $this->sawService->calculate();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        $criteria = Criterion::orderBy('code')->get();
        $products = Product::with('scores')->where('is_active', true)->orderBy('name')->get();

        return view('saw.index', compact('sawData', 'error', 'criteria', 'products'));
    }

    public function scores()
    {
        $criteria = Criterion::orderBy('code')->get();
        $products = Product::with('scores')->where('is_active', true)->orderBy('name')->get();

        // Siapkan matriks nilai saat ini: product_id -> criteria_id -> value
        $scoresMatrix = [];
        foreach ($products as $product) {
            foreach ($product->scores as $score) {
                $scoresMatrix[$product->id][$score->criteria_id] = (float) $score->value;
            }
        }

        return view('saw.scores', compact('criteria', 'products', 'scoresMatrix'));
    }

    public function updateScores(Request $request)
    {
        $validated = $request->validate([
            'scores' => ['required', 'array'],
            'scores.*.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach ($validated['scores'] as $productId => $criteriaValues) {
            foreach ($criteriaValues as $criteriaId => $value) {
                if ($value !== null && $value !== '') {
                    ProductScore::updateOrCreate(
                        ['product_id' => $productId, 'criteria_id' => $criteriaId],
                        ['value' => (float) $value]
                    );
                } else {
                    ProductScore::where('product_id', $productId)
                        ->where('criteria_id', $criteriaId)
                        ->delete();
                }
            }
        }

        return redirect()->route('saw.index')
            ->with('success', 'Nilai kriteria produk berhasil diperbarui! Perhitungan SAW produk terlaris telah dihitung ulang.');
    }

    public function syncFromSales()
    {
        try {
            $this->sawService->syncFromSalesData();
            return redirect()->route('saw.index')
                ->with('success', 'Berhasil melakukan sinkronisasi otomatis nilai kriteria produk dari data transaksi kasir!');
        } catch (\Throwable $e) {
            return redirect()->route('saw.index')
                ->with('error', 'Gagal sinkronisasi data: ' . $e->getMessage());
        }
    }

    public function subCriteria()
    {
        $criteria = Criterion::with('subCriteria')->orderBy('code')->get();

        return view('saw.sub-criteria', compact('criteria'));
    }

    /**
     * Simpan batas bawah sub-kriteria. Input: bounds[criteria_id][rating] = batas bawah (rating 2..5).
     * Rating 1 selalu tanpa batas bawah. Batas harus naik seiring rating supaya tidak ada rentang yang tumpang tindih.
     */
    public function updateSubCriteria(Request $request)
    {
        $validated = $request->validate([
            'bounds' => ['required', 'array'],
            'bounds.*' => ['required', 'array'],
            'bounds.*.*' => ['required', 'numeric', 'min:0'],
        ], [], ['bounds.*.*' => 'batas sub-kriteria']);

        $criteria = Criterion::whereIn('id', array_keys($validated['bounds']))->get()->keyBy('id');
        $errors = [];

        foreach ($validated['bounds'] as $criteriaId => $bounds) {
            ksort($bounds);
            $previous = null;
            foreach ($bounds as $rating => $min) {
                if ($previous !== null && (float) $min <= $previous) {
                    $errors["bounds.{$criteriaId}.{$rating}"] = sprintf(
                        '%s: batas rating %d harus lebih besar dari batas rating %d.',
                        $criteria[$criteriaId]->code ?? '?', $rating, $rating - 1,
                    );
                }
                $previous = (float) $min;
            }
        }

        if ($errors) {
            return back()->withErrors($errors)->withInput();
        }

        DB::transaction(function () use ($validated, $criteria) {
            foreach ($validated['bounds'] as $criteriaId => $bounds) {
                if (! $criteria->has($criteriaId)) {
                    continue;
                }
                foreach ($bounds + [1 => null] as $rating => $min) {
                    SubCriterion::updateOrCreate(
                        ['criteria_id' => $criteriaId, 'rating' => (int) $rating],
                        ['min_value' => $min === null ? null : (float) $min],
                    );
                }
            }
        });

        return redirect()->route('saw.index')->with('success', 'Sub-kriteria disimpan. Peringkat SAW dihitung ulang dengan rating baru.');
    }

    public function resetSubCriteria()
    {
        $this->sawService->createDefaultCriteria();

        return redirect()->route('saw.sub-criteria')->with('success', 'Kriteria dan sub-kriteria dikembalikan ke pengaturan bawaan.');
    }
}
