<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockAdjustment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Query agregat penjualan untuk dashboard dan laporan. Semua rentang inklusif [$from, $to].
 *
 * Laba kotor dihitung dari sale_items.buy_price (HPP yang terkunci saat transaksi),
 * bukan HPP produk saat ini.
 */
class SalesReportService
{
    /** Bulan yang punya penjualan, terbaru dulu: ['2026-10' => 'Oktober 2026', ...] */
    public function months(): Collection
    {
        return Sale::query()
            ->selectRaw("DATE_FORMAT(sale_date, '%Y-%m') AS ym")
            ->distinct()
            ->orderByDesc('ym')
            ->pluck('ym')
            ->mapWithKeys(fn ($ym) => [$ym => Carbon::parse($ym.'-01')->locale('id')->translatedFormat('F Y')]);
    }

    /** Bulan terbaru yang punya penjualan di minimal 7 hari (bulan yang "layak dilaporkan"). */
    public function latestFullMonth(): ?string
    {
        return Sale::query()
            ->selectRaw("DATE_FORMAT(sale_date, '%Y-%m') AS ym, COUNT(DISTINCT DATE(sale_date)) AS days")
            ->groupBy('ym')
            ->having('days', '>=', 7)
            ->orderByDesc('ym')
            ->value('ym')
            ?? $this->months()->keys()->first();
    }

    public function firstSaleDate(): ?Carbon
    {
        $first = Sale::min('sale_date');

        return $first ? Carbon::parse($first)->startOfDay() : null;
    }

    /** @return object{revenue: float, cost: float, profit: float, orders: int, avg_order: float, margin: float} */
    public function summary(Carbon $from, Carbon $to): object
    {
        $row = $this->items($from, $to)
            ->selectRaw('COALESCE(SUM(sale_items.subtotal), 0) AS revenue')
            ->selectRaw('COALESCE(SUM(sale_items.buy_price * sale_items.qty), 0) AS cost')
            ->selectRaw('COUNT(DISTINCT sales.id) AS orders')
            ->first();

        $revenue = (float) $row->revenue;
        $cost = (float) $row->cost;
        $orders = (int) $row->orders;

        return (object) [
            'revenue' => $revenue,
            'cost' => $cost,
            'profit' => $revenue - $cost,
            'orders' => $orders,
            'avg_order' => $orders > 0 ? $revenue / $orders : 0,
            'margin' => $revenue > 0 ? ($revenue - $cost) / $revenue * 100 : 0,
        ];
    }

    /** Omzet & jumlah struk per hari, termasuk hari tanpa penjualan. */
    public function daily(Carbon $from, Carbon $to): array
    {
        $byDate = Sale::query()
            ->whereBetween('sale_date', [$from, $to])
            ->selectRaw('DATE(sale_date) AS day, SUM(total) AS revenue, COUNT(*) AS orders')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $days = [];
        for ($d = $from->copy()->startOfDay(); $d->lte($to); $d->addDay()) {
            $row = $byDate->get($d->toDateString());
            $days[] = [
                'date' => $d->toDateString(),
                'day' => $d->day,
                'label' => $d->locale('id')->translatedFormat('D, j M'),
                'weekend' => $d->isWeekend(),
                'revenue' => (float) ($row->revenue ?? 0),
                'orders' => (int) ($row->orders ?? 0),
            ];
        }

        return $days;
    }

    public function byCategory(Carbon $from, Carbon $to): Collection
    {
        return $this->items($from, $to)
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->groupBy('categories.name')
            ->select('categories.name')
            ->selectRaw('SUM(sale_items.subtotal) AS revenue')
            ->selectRaw('SUM((sale_items.price - sale_items.buy_price) * sale_items.qty) AS profit')
            ->orderByDesc('revenue')
            ->get();
    }

    /** Per produk, urut omzet terbesar. */
    public function products(Carbon $from, Carbon $to, ?int $limit = null): Collection
    {
        return $this->items($from, $to)
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->groupBy('products.id', 'products.code', 'products.name', 'products.unit', 'categories.name')
            ->select('products.code', 'products.name', 'products.unit', 'categories.name AS category')
            ->selectRaw('SUM(sale_items.qty) AS qty')
            ->selectRaw('SUM(sale_items.subtotal) AS revenue')
            ->selectRaw('SUM(sale_items.buy_price * sale_items.qty) AS cost')
            ->selectRaw('COUNT(DISTINCT sales.id) AS orders')
            ->orderByDesc('revenue')
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get();
    }

    /** Rata-rata omzet & struk per hari dalam seminggu (Senin dulu), dihitung dari hari yang ada penjualannya. */
    public function byWeekday(Carbon $from, Carbon $to): array
    {
        $rows = Sale::query()
            ->whereBetween('sale_date', [$from, $to])
            ->selectRaw('WEEKDAY(sale_date) AS wd, COUNT(DISTINCT DATE(sale_date)) AS days, SUM(total) AS revenue, COUNT(*) AS orders')
            ->groupBy('wd')
            ->get()
            ->keyBy('wd');

        $names = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        return array_map(function ($wd) use ($rows, $names) {
            $row = $rows->get($wd);
            $days = (int) ($row->days ?? 0);

            return [
                'name' => $names[$wd],
                'days' => $days,
                'revenue' => $days ? (float) $row->revenue / $days : 0,
                'orders' => $days ? (int) $row->orders / $days : 0,
            ];
        }, range(0, 6));
    }

    /** Rata-rata jumlah struk per jam (dari hari yang ada penjualannya). */
    public function byHour(Carbon $from, Carbon $to): array
    {
        $activeDays = max(1, (int) Sale::whereBetween('sale_date', [$from, $to])->selectRaw('COUNT(DISTINCT DATE(sale_date)) AS d')->value('d'));

        $rows = Sale::query()
            ->whereBetween('sale_date', [$from, $to])
            ->selectRaw('HOUR(sale_date) AS h, COUNT(*) AS orders, SUM(total) AS revenue')
            ->groupBy('h')
            ->get()
            ->keyBy('h');

        if ($rows->isEmpty()) {
            return [];
        }

        return array_map(fn ($h) => [
            'hour' => $h,
            'orders' => (int) ($rows->get($h)->orders ?? 0) / $activeDays,
            'revenue' => (float) ($rows->get($h)->revenue ?? 0) / $activeDays,
        ], range($rows->keys()->min(), $rows->keys()->max()));
    }

    public function byCashier(Carbon $from, Carbon $to): Collection
    {
        return Sale::query()
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->groupBy('users.id', 'users.name')
            ->select('users.name')
            ->selectRaw('COUNT(*) AS orders, SUM(sales.total) AS revenue')
            ->orderByDesc('revenue')
            ->get();
    }

    /** Penjualan vs pembelian per minggu (Senin–Minggu, dipotong ke batas periode). */
    public function weeklyCashflow(Carbon $from, Carbon $to): array
    {
        $sales = Sale::query()
            ->whereBetween('sale_date', [$from, $to])
            ->selectRaw('YEARWEEK(sale_date, 3) AS wk, SUM(total) AS total')
            ->groupBy('wk')
            ->pluck('total', 'wk');

        $purchases = Purchase::query()
            ->whereBetween('purchase_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('YEARWEEK(purchase_date, 3) AS wk, SUM(total) AS total')
            ->groupBy('wk')
            ->pluck('total', 'wk');

        $weeks = [];
        for ($w = $from->copy()->startOfWeek(Carbon::MONDAY); $w->lte($to); $w->addWeek()) {
            $start = $w->copy()->max($from);
            $end = $w->copy()->endOfWeek(Carbon::SUNDAY)->min($to);
            $key = (int) $w->format('oW');

            $weeks[] = [
                'label' => $start->isSameMonth($end)
                    ? $start->day.'–'.$end->locale('id')->translatedFormat('j M')
                    : $start->locale('id')->translatedFormat('j M').'–'.$end->locale('id')->translatedFormat('j M'),
                'sales' => (float) ($sales[$key] ?? 0),
                'purchases' => (float) ($purchases[$key] ?? 0),
            ];
        }

        return $weeks;
    }

    public function purchasesTotal(Carbon $from, Carbon $to): float
    {
        return (float) Purchase::whereBetween('purchase_date', [$from->toDateString(), $to->toDateString()])->sum('total');
    }

    /**
     * Penyesuaian stok per produk & jenis. Nilai = qty × HPP rata-rata produk saat ini (estimasi,
     * karena HPP tidak disimpan di baris penyesuaian).
     */
    public function adjustments(Carbon $from, Carbon $to): Collection
    {
        return StockAdjustment::query()
            ->join('products', 'products.id', '=', 'stock_adjustments.product_id')
            ->whereBetween('stock_adjustments.created_at', [$from, $to])
            ->groupBy('products.id', 'products.name', 'products.unit', 'stock_adjustments.type')
            ->select('products.name', 'products.unit', 'stock_adjustments.type')
            ->selectRaw('COUNT(*) AS times, SUM(stock_adjustments.qty) AS qty')
            ->selectRaw('SUM(stock_adjustments.qty * products.avg_buy_price) AS value')
            ->orderBy('value')
            ->get();
    }

    private function items(Carbon $from, Carbon $to)
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereBetween('sales.sale_date', [$from, $to]);
    }
}
