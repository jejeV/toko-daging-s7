<?php

namespace App\Http\Controllers;

use App\Enums\AdjustmentType;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Services\StockAdjustmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StockAdjustmentController extends Controller
{
    public function __construct(private readonly StockAdjustmentService $adjustmentService)
    {
    }

    public function index()
    {
        $adjustments = StockAdjustment::with(['product.category', 'user'])
            ->latest()
            ->paginate(15);

        return view('adjustments.index', compact('adjustments'));
    }

    public function create()
    {
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $types = AdjustmentType::cases();

        return view('adjustments.create', compact('products', 'types'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'type' => ['required', Rule::enum(AdjustmentType::class)],
            'qty' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $type = AdjustmentType::from($validated['type']);

            $this->adjustmentService->record(
                Auth::user(),
                (int) $validated['product_id'],
                $type,
                (float) $validated['qty'],
                $validated['reason']
            );

            return redirect()->route('adjustments.index')
                ->with('success', 'Penyesuaian stok berhasil dicatat dan saldo stok telah disesuaikan.');

        } catch (BusinessRuleException|InsufficientStockException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal memproses penyesuaian: '.$e->getMessage());
        }
    }
}

