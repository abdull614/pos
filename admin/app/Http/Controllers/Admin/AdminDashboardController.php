<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\CustomerReceivable;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\CarbonPeriod;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->toDateString();
        $mode = $request->get('mode', 'weekly');

        // ========================
        // 💰 PENDAPATAN HARI INI
        // ========================
        $todaysSales = SalesTransaction::whereDate('sold_at', $today)
            ->whereIn('status', ['completed', 'paid', 'success'])
            ->sum('total');

        // ========================
        // 💸 PENGELUARAN
        // ========================
        $todaysExpense = 0;
        $monthlyExpense = 0;

        if (\Schema::hasTable('purchases')) {
            $todaysExpense = DB::table('purchases')
                ->whereDate('created_at', $today)
                ->sum('total');

            $monthlyExpense = DB::table('purchases')
                ->whereMonth('created_at', now()->month)
                ->sum('total');
        }

        // ========================
        // 📦 PRODUK
        // ========================
        $totalProducts = Product::count();

        $lowStockProducts = Product::whereColumn('stock', '<=', 'min_stock')
            ->where('is_active', true)
            ->limit(5)
            ->get();

        // ========================
        // 🏆 PRODUK TERLARIS
        // ========================
        $topProducts = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(sale_items.quantity) as total_sold'),
                DB::raw('SUM(sale_items.subtotal) as revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        // ========================
        // ⚠️ UTANG JATUH TEMPO
        // ========================
        // Ambil piutang yang masih ada sisa (remaining > 0) dan tanggal jatuh
        // tempo sudah lewat dari hari ini. Di-map ke object sederhana dengan
        // field 'name' & 'total_debt' supaya kompatibel dengan struktur yang
        // sudah dipakai x-alerts-card, ditambah 'due_date' & 'days_overdue'
        // untuk info seberapa lama sudah telat.
        $customersWithDebt = CustomerReceivable::with('customer')
            ->where('remaining', '>', 0)
            ->whereDate('due_date', '<', $today)
            ->orderBy('due_date') // paling lama telat tampil di atas
            ->limit(5)
            ->get()
            ->map(function ($receivable) {
                return (object) [
                    'name'         => $receivable->customer->name ?? 'Tanpa nama',
                    'total_debt'   => $receivable->remaining,
                    'due_date'     => $receivable->due_date,
                    'days_overdue' => now()->diffInDays($receivable->due_date),
                ];
            });

        // ========================
        // 📊 TREND DATA (PENDAPATAN vs PENGELUARAN)
        // ========================
        $hasPurchases = \Schema::hasTable('purchases');

        if ($mode === 'monthly') {
            $salesRaw = SalesTransaction::selectRaw('MONTH(sold_at) as month, SUM(total) as total')
                ->whereYear('sold_at', now()->year)
                ->whereIn('status', ['completed', 'paid', 'success'])
                ->groupBy('month')
                ->get()
                ->keyBy('month');

            $expenseRaw = $hasPurchases
                ? DB::table('purchases')
                    ->selectRaw('MONTH(created_at) as month, SUM(total) as total')
                    ->whereYear('created_at', now()->year)
                    ->groupBy('month')
                    ->get()
                    ->keyBy('month')
                : collect();

            $trendData = collect();

            // PENTING: hanya generate sampai bulan BERJALAN (now()->month),
            // bukan sampai Desember. Kalau sampai Desember, bulan-bulan yang
            // belum terjadi ikut digambar sebagai garis kosong/flat sampai
            // ujung kanan chart, padahal belum ada datanya sama sekali.
            // Dengan dibatasi begini, garis trend akan berhenti tepat di
            // bulan terakhir yang sudah berjalan (mis. Juli), bukan menjulur
            // kosong ke bulan-bulan mendatang.
            for ($m = 1; $m <= now()->month; $m++) {
                $trendData->push([
                    'label'   => \Carbon\Carbon::create()->month($m)->translatedFormat('M'),
                    'income'  => $salesRaw[$m]->total ?? 0,
                    'expense' => $expenseRaw[$m]->total ?? 0,
                ]);
            }
        } else {
            // Mingguan: 7 hari terakhir termasuk hari ini
            $salesRaw = SalesTransaction::selectRaw('DATE(sold_at) as date, SUM(total) as total')
                ->whereBetween('sold_at', [
                    now()->subDays(6)->startOfDay(),
                    now()->endOfDay()
                ])
                ->whereIn('status', ['completed', 'paid', 'success'])
                ->groupBy('date')
                ->get()
                ->keyBy('date');

            $expenseRaw = $hasPurchases
                ? DB::table('purchases')
                    ->selectRaw('DATE(created_at) as date, SUM(total) as total')
                    ->whereBetween('created_at', [
                        now()->subDays(6)->startOfDay(),
                        now()->endOfDay()
                    ])
                    ->groupBy('date')
                    ->get()
                    ->keyBy('date')
                : collect();

            // Catatan: mode mingguan sudah otomatis berhenti "hari ini" dan
            // tidak pernah menjulur ke hari yang belum terjadi, jadi tidak
            // perlu perubahan seperti mode bulanan di atas.
            $period = CarbonPeriod::create(now()->subDays(6), now());
            $trendData = collect();

            foreach ($period as $date) {
                $d = $date->format('Y-m-d');
                $trendData->push([
                    'label'   => $date->translatedFormat('d M'),
                    'income'  => $salesRaw[$d]->total ?? 0,
                    'expense' => $expenseRaw[$d]->total ?? 0,
                ]);
            }
        }

        // ========================
        // 🔥 NORMALISASI DATA UNTUK CHART DUA ARAH
        // ========================
        // Skala dihitung dari nilai TERBESAR di antara income & expense
        // gabungan, supaya kedua sisi (atas & bawah) sebanding satu sama
        // lain dan proporsional terhadap 0 (basis garis tengah).
        $maxIncome  = $trendData->max('income') ?: 0;
        $maxExpense = $trendData->max('expense') ?: 0;
        $maxVal     = max($maxIncome, $maxExpense) ?: 1;

        $topIncomeIndex = $maxIncome > 0
            ? $trendData->keys()->first(fn($i) => $trendData[$i]['income'] === $maxIncome)
            : null;

        $topExpenseIndex = $maxExpense > 0
            ? $trendData->keys()->first(fn($i) => $trendData[$i]['expense'] === $maxExpense)
            : null;

        $trendChart = $trendData->values()->map(function ($item, $index) use ($maxVal, $topIncomeIndex, $topExpenseIndex) {
            $incomePercent  = $maxVal > 0 ? round(($item['income'] / $maxVal) * 100) : 0;
            $expensePercent = $maxVal > 0 ? round(($item['expense'] / $maxVal) * 100) : 0;

            return [
                'label'           => $item['label'],
                'income'          => $item['income'],
                'expense'         => $item['expense'],
                'income_percent'  => $incomePercent,
                'expense_percent' => $expensePercent,
                'income_display'  => $item['income'] > 0
                    ? 'Rp ' . number_format($item['income'], 0, ',', '.') : '',
                'expense_display' => $item['expense'] > 0
                    ? 'Rp ' . number_format($item['expense'], 0, ',', '.') : '',
                'income_active'   => ($index === $topIncomeIndex),
                'expense_active'  => ($index === $topExpenseIndex),
            ];
        });

        // ========================
        // 📈 LABA BERSIH
        // ========================
        $monthlySales = SalesTransaction::whereIn('status', ['completed', 'paid', 'success'])
            ->whereMonth('sold_at', now()->month)
            ->sum('total');

        $netProfit = $monthlySales - $monthlyExpense;

        // ========================
        // 🚀 RETURN
        // ========================
        return view('admin.dashboard', compact(
            'todaysSales',
            'todaysExpense',
            'totalProducts',
            'lowStockProducts',
            'topProducts',
            'trendChart',
            'mode',
            'netProfit',
            'customersWithDebt'
        ));
    }
}