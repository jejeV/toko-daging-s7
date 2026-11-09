<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with('user')->withCount('items')->latest('sale_date');

        if ($request->filled('search')) {
            $query->where('invoice_no', 'like', "%{$request->search}%");
        }

        if ($request->filled('date_from')) {
            $query->where('sale_date', '>=', Carbon::parse($request->date_from)->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('sale_date', '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        $sales = $query->paginate(15)->withQueryString();

        return view('sales.index', compact('sales'));
    }

    public function show(Sale $sale)
    {
        $sale->load(['user', 'items.product']);

        return view('sales.show', compact('sale'));
    }
}

