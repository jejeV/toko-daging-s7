<?php

namespace App\Services\Saw;

use App\Models\Criterion;
use App\Models\Supplier;
use App\Models\SupplierScore;

/**
 * Mengambil data supplier + nilai dari database (2 query), lalu menghitung SAW.
 */
class SupplierSawService
{
    public function __construct(private readonly SawCalculator $calculator)
    {
    }

    /**
     * @return array{
     *     criteria: \Illuminate\Support\Collection,
     *     suppliers: \Illuminate\Support\Collection,
     *     incomplete: \Illuminate\Support\Collection,
     *     matrix: array, normalized: array, scores: array,
     *     ranking: list<array{alternative:int, score:float, rank:int, supplier:Supplier}>
     * }
     */
    public function calculate(): array
    {
        $criteria = Criterion::query()->orderBy('code')->get();

        // Query 2: semua nilai sekaligus, lalu dikelompokkan per supplier di PHP.
        $scores = SupplierScore::query()
            ->with('supplier:id,name')
            ->get(['supplier_id', 'criteria_id', 'value'])
            ->groupBy('supplier_id');

        $criteriaInput = $criteria->mapWithKeys(fn (Criterion $c) => [
            $c->code => ['type' => $c->type->value, 'weight' => (float) $c->weight],
        ])->all();

        $codeById = $criteria->pluck('code', 'id');

        $alternatives = [];
        $suppliers = collect();
        $incomplete = collect();

        foreach ($scores as $supplierId => $rows) {
            $values = [];
            foreach ($rows as $row) {
                if ($code = $codeById->get($row->criteria_id)) {
                    $values[$code] = (float) $row->value;
                }
            }

            $supplier = $rows->first()->supplier;

            // Supplier yang nilainya belum lengkap tidak ikut dihitung (PRD F7).
            if (count($values) < count($criteriaInput)) {
                $incomplete->push($supplier);
                continue;
            }

            $alternatives[$supplierId] = $values;
            $suppliers->put($supplierId, $supplier);
        }

        $result = $this->calculator->calculate($criteriaInput, $alternatives);

        foreach ($result['ranking'] as &$row) {
            $row['supplier'] = $suppliers->get($row['alternative']);
        }
        unset($row);

        return [
            'criteria' => $criteria,
            'suppliers' => $suppliers,
            'incomplete' => $incomplete,
            ...$result,
        ];
    }
}
