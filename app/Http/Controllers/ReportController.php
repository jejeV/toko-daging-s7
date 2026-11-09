<?php

namespace App\Http\Controllers;

use App\Services\SalesReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly SalesReportService $report)
    {
    }

    public function index(Request $request)
    {
        [$from, $to, $month] = $this->period($request);
        $report = $this->report;

        $summary = $report->summary($from, $to);

        // Pembanding: periode sama panjang tepat sebelumnya, hanya kalau datanya lengkap.
        $days = (int) $from->diffInDays($to->copy()->startOfDay()) + 1;
        $prevFrom = $month ? $from->copy()->subMonthNoOverflow()->startOfMonth() : $from->copy()->subDays($days);
        $prevTo = $month ? $prevFrom->copy()->endOfMonth() : $from->copy()->subSecond();
        $firstSale = $report->firstSaleDate();
        $previous = $firstSale && $firstSale->lte($prevFrom) ? $report->summary($prevFrom, $prevTo) : null;

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'month' => $month,
            'months' => $report->months(),
            'previous' => $previous,
            'previousLabel' => $month ? $prevFrom->locale('id')->translatedFormat('F Y') : $days.' hari sebelumnya',
            'summary' => $summary,
            'purchasesTotal' => $report->purchasesTotal($from, $to),
            'daily' => $report->daily($from, $to),
            'weekdays' => $report->byWeekday($from, $to),
            'hours' => $report->byHour($from, $to),
            'categories' => $report->byCategory($from, $to),
            'products' => $report->products($from, $to),
            'cashiers' => $report->byCashier($from, $to),
            'weeks' => $report->weeklyCashflow($from, $to),
            'adjustments' => $report->adjustments($from, $to),
            'isOwner' => $request->user()->isOwner(),
        ]);
    }

    /** Rekap per produk dalam CSV (dibuka langsung di Excel, pemisah titik koma). */
    public function productsCsv(Request $request): StreamedResponse
    {
        [$from, $to] = $this->period($request);
        $rows = $this->report->products($from, $to);
        $withProfit = $request->user()->isOwner();

        $filename = sprintf('laporan-produk_%s_%s.csv', $from->format('Ymd'), $to->format('Ymd'));

        return response()->streamDownload(function () use ($rows, $withProfit) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8

            $header = ['Kode', 'Produk', 'Kategori', 'Satuan', 'Qty terjual', 'Jumlah struk', 'Omzet (Rp)'];
            if ($withProfit) {
                array_push($header, 'HPP (Rp)', 'Laba kotor (Rp)', 'Margin (%)');
            }
            fputcsv($out, $header, ';');

            // Desimal pakai koma, sesuai Excel berpengaturan Indonesia.
            $decimal = fn ($v, $d) => number_format((float) $v, $d, ',', '');

            foreach ($rows as $row) {
                $line = [$row->code, $row->name, $row->category, $row->unit, $decimal($row->qty, 3), $row->orders, round($row->revenue)];
                if ($withProfit) {
                    $profit = $row->revenue - $row->cost;
                    array_push($line, round($row->cost), round($profit), $row->revenue > 0 ? $decimal($profit / $row->revenue * 100, 1) : '0,0');
                }
                fputcsv($out, $line, ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Periode dari query string: ?bulan=YYYY-MM, atau ?dari=YYYY-MM-DD&sampai=YYYY-MM-DD.
     * Tanpa keduanya: bulan terbaru yang datanya cukup.
     *
     * @return array{0: Carbon, 1: Carbon, 2: ?string}
     */
    private function period(Request $request): array
    {
        $request->validate([
            'bulan' => ['nullable', 'date_format:Y-m'],
            'dari' => ['nullable', 'date_format:Y-m-d'],
            'sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dari'],
        ]);

        if ($request->filled('dari') && $request->filled('sampai')) {
            return [Carbon::parse($request->dari)->startOfDay(), Carbon::parse($request->sampai)->endOfDay(), null];
        }

        $month = $request->input('bulan') ?: ($this->report->latestFullMonth() ?? now()->format('Y-m'));
        $start = Carbon::parse($month.'-01');

        return [$start->copy()->startOfMonth(), $start->copy()->endOfMonth(), $month];
    }
}
