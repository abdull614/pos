<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SalesTransaction;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function adminDashboard()
    {
        $today = now('Asia/Jakarta')->toDateString(); // ← tambah timezone

        $todaysSales = SalesTransaction::whereDate('created_at', $today) // ← ganti sold_at
                                    ->where('status', 'completed')
                                    ->sum('total');

        $totalDebt = Customer::sum('total_debt');

        $topProducts = DB::table('sale_items')
                        ->join('products', 'sale_items.product_id', '=', 'products.id')
                        ->select('products.id', 'products.name', 'products.sku',
                                DB::raw('SUM(sale_items.quantity) as total_sold'))
                        ->whereDate('sale_items.created_at', $today) // ← sudah benar, tapi timezone perlu disesuaikan
                        ->groupBy('products.id', 'products.name', 'products.sku')
                        ->orderBy('total_sold', 'desc')
                        ->limit(5)
                        ->get();

        $lowStockProducts = Product::where('stock', '<=', DB::raw('min_stock'))
                                ->where('is_active', true)
                                ->count();

        return response()->json([
            'todays_sales'    => $todaysSales,
            'total_debt'      => $totalDebt,
            'low_stock_count' => $lowStockProducts,
            'top_products'    => $topProducts,
        ]);
    }

    public function kasirDashboard()
    {
        // Gunakan timezone Asia/Jakarta agar tanggal selalu sesuai WIB
        $today = now('Asia/Jakarta')->toDateString();

        $myTransactions = SalesTransaction::where('kasir_id', auth()->id())
                                        ->whereDate('created_at', $today) // ← ganti sold_at → created_at
                                        ->where('status', 'completed')
                                        ->sum('total');

        $pendingTransactions = SalesTransaction::where('kasir_id', auth()->id())
                                            ->where('status', 'pending')
                                            ->count();

        $debtTransactions = SalesTransaction::where('kasir_id', auth()->id())
                                        ->whereDate('created_at', $today) // ← ganti sold_at → created_at
                                        ->where('payment_type', 'debt')
                                        ->where('status', 'completed')
                                        ->sum('total');

        return response()->json([
            'todays_sales'        => $myTransactions,
            'pending_transactions' => $pendingTransactions,
            'debt_sales'          => $debtTransactions,
        ]);
    }
}
