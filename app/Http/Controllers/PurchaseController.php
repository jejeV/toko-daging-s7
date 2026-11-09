<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessRuleException;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    public function __construct(private readonly PurchaseService $purchaseService)
    {
    }

    public function index()
    {
        $purchases = Purchase::with(['supplier', 'user'])->withCount('items')
            ->latest('purchase_date')
            ->paginate(15);

        return view('purchases.index', compact('purchases'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('purchases.create', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.001'],
            'items.*.buy_price' => ['required', 'numeric', 'min:0'],
            'items.*.expired_at' => ['nullable', 'date'],
        ]);

        try {
            $purchase = $this->purchaseService->create(
                Auth::user(),
                (int) $validated['supplier_id'],
                $validated['purchase_date'],
                $validated['items'],
                $validated['note'] ?? null
            );

            return redirect()->route('purchases.show', $purchase)
                ->with('success', 'Pembelian berhasil dicatat! Stok produk dan harga beli rata-rata telah diperbarui.');

        } catch (BusinessRuleException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal mencatat pembelian: '.$e->getMessage());
        }
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'user', 'items.product']);

        return view('purchases.show', compact('purchase'));
    }
}

