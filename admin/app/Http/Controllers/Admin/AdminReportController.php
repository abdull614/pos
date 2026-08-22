<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\SalesTransaction;
use App\Models\Product;
use App\Models\CustomerReceivable;
use App\Models\Expenditure;

use App\Exports\SalesReportExport;
use Maatwebsite\Excel\Facades\Excel;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\View\View;

class AdminReportController extends Controller
{
    public function sales(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->subDays(30)->toDateString());
        $dateTo   = $request->input('date_to', now()->toDateString());

        // ── Detail penjualan harian ──────────────────────────────
        $sales = SalesTransaction::selectRaw('DATE(sold_at) as date, SUM(total) as total, COUNT(*) as count')
            ->whereDate('sold_at', '>=', $dateFrom)
            ->whereDate('sold_at', '<=', $dateTo)
            ->where('status', 'completed')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalSales         = (float) $sales->sum('total');       // omzet/pendapatan kotor
        $totalTransactions  = (int) $sales->sum('count');

        // ── Total pengeluaran pada rentang filter ────────────────
        $totalExpenses = (float) Expenditure::whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo)
            ->sum('amount');

        // ── Keuntungan bersih = pendapatan - pengeluaran ─────────
        $netProfit = $totalSales - $totalExpenses;

        // ── Efisiensi margin ──────────────────────────────────────
        $targetMargin   = 28; // target internal toko; pindahkan ke config/settings kalau ada
        $marginPercent  = $totalSales > 0 ? round(($netProfit / $totalSales) * 100, 1) : 0;
        $marginProgress = $targetMargin > 0 ? min(100, round(($marginPercent / $targetMargin) * 100, 1)) : 0;

        // ── Perbandingan dengan periode sebelumnya (durasi sama) ─
        $days         = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) + 1;
        $prevDateTo   = Carbon::parse($dateFrom)->subDay()->toDateString();
        $prevDateFrom = Carbon::parse($dateFrom)->subDays($days)->toDateString();

        $prevTotalSales = (float) SalesTransaction::whereDate('sold_at', '>=', $prevDateFrom)
            ->whereDate('sold_at', '<=', $prevDateTo)
            ->where('status', 'completed')
            ->sum('total');

        if ($prevTotalSales > 0) {
            $growthPercent = round((($totalSales - $prevTotalSales) / $prevTotalSales) * 100, 1);
        } else {
            $growthPercent = $totalSales > 0 ? 100 : 0;
        }

        // ── Pengeluaran Restock terbaru dalam rentang filter ─────
        $restocks = Expenditure::whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo)
            ->orderBy('expense_date', 'desc')
            ->take(5)
            ->get();

        // ── Aktivitas terakhir: gabungan penjualan + pengeluaran ─
        $recentSales = SalesTransaction::where('status', 'completed')
            ->whereDate('sold_at', '>=', $dateFrom)
            ->whereDate('sold_at', '<=', $dateTo)
            ->latest('sold_at')
            ->take(5)
            ->get()
            ->map(function ($trx) {
                return [
                    'time'       => Carbon::parse($trx->sold_at),
                    'title'      => 'Penjualan Kasir - Invoice ' . ($trx->invoice_number ?? '#' . $trx->id),
                    'sub'        => 'Metode: ' . ($trx->payment_method ?? '-'),
                    'type'       => 'in',
                    'amount'     => $trx->total,
                    'icon'       => 'bi-bag-check-fill',
                    'icon_bg'    => 'bg-emerald-100',
                    'icon_color' => 'text-emerald-600',
                ];
            });

        $recentExpenses = Expenditure::whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo)
            ->latest('expense_date')
            ->take(5)
            ->get()
            ->map(function ($exp) {
                return [
                    'time'       => Carbon::parse($exp->expense_date),
                    'title'      => 'Pengeluaran - ' . $exp->description,
                    'sub'        => 'Kategori: ' . ($exp->category ?? '-'),
                    'type'       => 'out',
                    'amount'     => $exp->amount,
                    'icon'       => 'bi-box-arrow-up-right',
                    'icon_bg'    => 'bg-rose-100',
                    'icon_color' => 'text-rose-500',
                ];
            });

        $activities = $recentSales->concat($recentExpenses)
            ->sortByDesc('time')
            ->take(6)
            ->values()
            ->map(function ($act) {
                $act['time_label'] = $this->formatActivityTime($act['time']);
                return $act;
            });

        return view('admin.reports.sales', compact(
            'sales',
            'totalSales',
            'totalTransactions',
            'totalExpenses',
            'netProfit',
            'marginPercent',
            'targetMargin',
            'marginProgress',
            'growthPercent',
            'restocks',
            'activities',
            'dateFrom',
            'dateTo'
        ));
    }

    public function salesExcel(Request $request)
    {
        $allTime  = $request->input('range_mode') === 'all';
        $dateFrom = $request->input('date_from', now()->subDays(30)->toDateString());
        $dateTo   = $request->input('date_to', now()->toDateString());

        $filename = $allTime
            ? 'laporan-penjualan-semua-data.xlsx'
            : 'laporan-penjualan-' . $dateFrom . '_' . $dateTo . '.xlsx';

        return Excel::download(new SalesReportExport($dateFrom, $dateTo, $allTime), $filename);
    }

    private function formatActivityTime(Carbon $date): string
    {
        $time = $date->format('H:i');

        if ($date->isToday()) {
            return "Hari Ini, {$time}";
        }
        if ($date->isYesterday()) {
            return "Kemarin, {$time}";
        }
        return $date->translatedFormat('d M') . ", {$time}";
    }

    public function products(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->subDays(30)->toDateString());
        $dateTo   = $request->input('date_to', now()->toDateString());

        // PERBAIKAN:
        // 1. Join ke sales_transactions supaya filter tanggal memakai `sold_at`
        //    (tanggal transaksi terjadi), bukan `sale_items.created_at` (tanggal
        //    baris item dibuat di database — bisa berbeda / tidak relevan).
        // 2. Tambahkan filter status 'completed' supaya transaksi yang masih
        //    pending/dibatalkan tidak ikut dihitung sebagai penjualan.
        // 3. Query di-assign ke variabel supaya bisa dipakai ulang untuk
        //    menghitung total (opsional, lihat catatan di bawah).
        $products = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales_transactions', 'sale_items.sales_transaction_id', '=', 'sales_transactions.id')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                DB::raw('SUM(sale_items.quantity) as total_sold'),
                DB::raw('SUM(sale_items.subtotal) as revenue')
            )
            ->where('sales_transactions.status', 'completed')
            ->whereDate('sales_transactions.sold_at', '>=', $dateFrom)
            ->whereDate('sales_transactions.sold_at', '<=', $dateTo)
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy('total_sold', 'desc')
            ->paginate(15)
            ->withQueryString(); // <-- otomatis membawa date_from & date_to saat pindah halaman

        return view('admin.reports.products', compact('products', 'dateFrom', 'dateTo'));
    }

    public function receivables(Request $request): View
    {
        $status = $request->input('status', 'all');

        $query = CustomerReceivable::with(['customer', 'salesTransaction'])
            ->when($status !== 'all', fn($q) => $q->where('status', $status));

        $receivables = $query->latest()->paginate(10);

        $summary = [
            'total'   => CustomerReceivable::sum('amount'),
            'unpaid'  => CustomerReceivable::where('status', 'unpaid')->sum('remaining'),
            'partial' => CustomerReceivable::where('status', 'partial')->sum('remaining'),
        ];

        return view('admin.reports.receivables', compact('receivables', 'summary', 'status'));
    }

    public function salesDetail(Request $request, string $date)
    {
        $date = \Carbon\Carbon::parse($date)->toDateString();

        $transactions = SalesTransaction::with(['saleItems.product', 'customer'])
            ->whereDate('sold_at', $date)
            ->where('status', 'completed')
            ->orderBy('sold_at', 'asc')
            ->paginate(20);

        $daySummary = [
            'total'      => SalesTransaction::whereDate('sold_at', $date)->where('status', 'completed')->sum('total'),
            'count'      => SalesTransaction::whereDate('sold_at', $date)->where('status', 'completed')->count(),
            'by_payment' => SalesTransaction::whereDate('sold_at', $date)
                                ->where('status', 'completed')
                                ->selectRaw('payment_type, COUNT(*) as jumlah, SUM(total) as total')
                                ->groupBy('payment_type')
                                ->get(),
        ];

        return view('admin.reports.sales-detail', compact('transactions', 'daySummary', 'date'));
    }
}