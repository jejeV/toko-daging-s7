<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Services\Saw\ProductSawService;
use App\Services\SalesReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    private const PERIODS = [
        '30-hari' => '30 hari',
        'bulan-ini' => 'Bulan ini',
        'bulan-lalu' => 'Bulan lalu',
    ];

    public function __construct(private readonly SalesReportService $report)
    {
    }

    public function index(Request $request, ProductSawService $sawService)
    {
        $today = Carbon::today();

        // Statistik Penjualan Hari Ini
        // Rentang waktu (bukan whereDate) supaya index sale_date terpakai.
        $todaySales = Sale::whereBetween('sale_date', [$today, $today->copy()->endOfDay()]);
        $todaySalesCount = (clone $todaySales)->count();
        $todaySalesTotal = $todaySales->sum('total');

        // Total Pembelian Bulan Ini
        $thisMonthPurchasesTotal = Purchase::whereBetween('purchase_date', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])
            ->sum('total');

        // Master count
        $totalProducts = Product::count();
        $totalCategories = Category::count();
        $totalSuppliers = Supplier::count();

        // Peringatan stok menipis
        $lowStockProducts = Product::with('category')
            ->where('is_active', true)
            ->whereRaw('stock <= min_stock')
            ->orderBy('stock')
            ->take(5)
            ->get();

        // Transaksi penjualan terbaru
        $recentSales = Sale::with('user')->withCount('items')
            ->latest('sale_date')
            ->take(5)
            ->get();

        // Rekomendasi produk terlaris & terbaik via SAW
        $topProduct = null;
        try {
            $sawData = $sawService->calculate();
            if (!empty($sawData['ranking'])) {
                $topProduct = $sawData['ranking'][0];
            }
        } catch (\Throwable $e) {
            // Abaikan jika data kriteria/nilai belum lengkap
        }

        // Penjualan tidak bisa diubah/dihapus, jadi id terakhir cukup sebagai versi cache:
        // transaksi baru otomatis membuat cache lama tidak terpakai.
        $period = (string) $request->query('periode', '');
        $performance = Cache::remember(
            sprintf('dashboard.performance.%s.%s.%d', $period ?: 'default', $today->toDateString(), (int) Sale::max('id')),
            now()->addMinutes(30),
            fn () => $this->salesPerformance($period ?: null),
        );

        return view('dashboard', compact(
            'performance',
            'todaySalesCount',
            'todaySalesTotal',
            'thisMonthPurchasesTotal',
            'totalProducts',
            'totalCategories',
            'totalSuppliers',
            'lowStockProducts',
            'recentSales',
            'topProduct'
        ));
    }

    /**
     * Ringkasan penjualan untuk satu periode: KPI (dibanding periode sebelumnya), omzet harian,
     * omzet per kategori, dan produk teratas.
     */
    private function salesPerformance(?string $period): array
    {
        $report = $this->report;
        $today = Carbon::today();
        $months = $report->months();

        if (! array_key_exists($period ?? '', self::PERIODS) && ! $months->has($period)) {
            // Tanpa pilihan: 30 hari terakhir. Kalau seminggu terakhir sepi (mis. data historis),
            // pakai bulan terbaru yang punya penjualan di minimal 7 hari.
            $hasRecent = Sale::where('sale_date', '>=', $today->copy()->subDays(6))->exists();
            $period = $hasRecent || $months->isEmpty() ? '30-hari' : $report->latestFullMonth();
        }

        [$start, $end] = match ($period) {
            '30-hari' => [$today->copy()->subDays(29), $today->copy()->endOfDay()],
            'bulan-ini' => [$today->copy()->startOfMonth(), $today->copy()->endOfDay()],
            'bulan-lalu' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            default => [Carbon::parse($period.'-01')->startOfMonth(), Carbon::parse($period.'-01')->endOfMonth()->min($today->copy()->endOfDay())],
        };

        // Pembanding: 30 hari → 30 hari sebelumnya; bulan penuh → bulan sebelumnya penuh;
        // bulan berjalan → tanggal yang sama di bulan sebelumnya.
        $days = (int) $start->diffInDays($end->copy()->startOfDay()) + 1;
        $prevMonth = $start->copy()->subMonthNoOverflow();
        [$prevStart, $prevEnd] = match (true) {
            $period === '30-hari' => [$start->copy()->subDays($days), $start->copy()->subSecond()],
            $end->isSameDay($start->copy()->endOfMonth()) => [$prevMonth, $prevMonth->copy()->endOfMonth()],
            default => [$prevMonth, $prevMonth->copy()->addDays($days - 1)->endOfDay()->min($start->copy()->subSecond())],
        };

        // Persentase hanya berarti kalau data periode pembanding lengkap dari awal.
        $firstSale = $report->firstSaleDate();
        $comparable = $firstSale !== null && $firstSale->lte($prevStart);

        $current = $report->summary($start, $end);
        $previous = $report->summary($prevStart, $prevEnd);

        $kpi = fn (string $key) => [
            'value' => $current->{$key},
            'previous' => $previous->{$key},
            'change' => $comparable && $previous->{$key} > 0
                ? ($current->{$key} - $previous->{$key}) / $previous->{$key} * 100
                : null,
        ];

        $daily = $report->daily($start, $end);

        return [
            'period' => $period,
            'periods' => self::PERIODS,
            'months' => $months,
            'comparable' => $comparable,
            'range' => $start->locale('id')->translatedFormat('j M').' – '.$end->locale('id')->translatedFormat('j M Y'),
            'compareLabel' => $period === '30-hari' ? '30 hari sebelumnya' : 'bulan sebelumnya',
            'revenue' => $kpi('revenue'),
            'orders' => $kpi('orders'),
            'profit' => $kpi('profit'),
            'avgOrder' => $kpi('avg_order'),
            'daily' => $daily,
            'categories' => $report->byCategory($start, $end),
            'topProducts' => $report->products($start, $end, 5),
        ];
    }
}
