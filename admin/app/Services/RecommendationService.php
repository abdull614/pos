<?php
// Simpan di: app/Services/RecommendationService.php

namespace App\Services;

use App\Models\Product;
use App\Models\SaleItem;
use App\Models\TrendItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RecommendationService
{
    /**
     * Panel 1: Produk paling laris berdasarkan transaksi internal
     * (default 30 hari terakhir berdasarkan SalesTransaction.sold_at,
     * ubah $days untuk rentang lain / null untuk all-time).
     *
     * Disesuaikan dengan struktur asli:
     * - SaleItem: sales_transaction_id, product_id, quantity, unit_price, subtotal
     * - SalesTransaction: sold_at (datetime), status
     */
    public function bestSellers(?int $days = 30, int $limit = 10, ?string $status = 'completed')
    {
        $query = SaleItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->with('product:id,name,image,selling_price,category_id')
            ->whereHas('salesTransaction', function ($q) use ($days, $status) {
                if ($days !== null) {
                    $q->where('sold_at', '>=', Carbon::now()->subDays($days));
                }
                if ($status !== null) {
                    $q->where('status', $status);
                    // Sesuaikan nilai status ini dengan enum/value asli di
                    // kolom `status` project Anda (mis. 'completed', 'paid', dll).
                    // Set $status = null kalau tidak mau filter status sama sekali.
                }
            })
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit($limit);

        return $query->get();
    }

    /**
     * Panel 2: Tren pencarian dari Google Trends. Tampil semua trend_items
     * terbaru (matched maupun belum), diurutkan dari skor tertinggi.
     * Kalau matched_product_id ada, ikut disertakan data produknya
     * supaya bisa langsung di-link di frontend.
     */
    public function googleTrends(int $limit = 10)
    {
        return TrendItem::with('matchedProduct:id,name,image,selling_price')
            ->where('source', 'google_trends')
            ->orderByDesc('score')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->unique('normalized_name') // ambil entri terbaru/tertinggi per keyword saja
            ->values();
    }
}
