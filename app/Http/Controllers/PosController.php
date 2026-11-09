<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PosController extends Controller
{
    public function __construct(private readonly SaleService $saleService)
    {
    }

    public function index()
    {
        $categories = Category::orderBy('name')->get();
        $products = Product::with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pos.index', compact('categories', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.001'],
            'paid' => ['required', 'numeric', 'min:0'],
        ]);

        $cashier = Auth::user();

        try {
            $sale = $this->saleService->create(
                $cashier,
                $validated['items'],
                (float) $validated['paid']
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Transaksi {$sale->invoice_no} berhasil disimpan!",
                    'sale_id' => $sale->id,
                    'invoice_no' => $sale->invoice_no,
                    'receipt_url' => route('pos.receipt', $sale),
                ]);
            }

            return redirect()->route('pos.receipt', $sale)
                ->with('success', "Transaksi {$sale->invoice_no} berhasil disimpan!");

        } catch (BusinessRuleException|InsufficientStockException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan sistem: '.$e->getMessage(),
                ], 500);
            }

            return back()->withInput()->with('error', 'Gagal memproses transaksi: '.$e->getMessage());
        }
    }

    public function receipt(Sale $sale)
    {
        $sale->load(['user', 'items.product']);

        return view('pos.receipt', compact('sale'));
    }
}

