<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use App\Models\Product;
use App\Models\ProductRecommendation;

class AdminRecommendationController extends Controller
{
    /**
     * Halaman ini SEKARANG murni baca dari database (tabel
     * product_recommendations), yang diisi setiap hari oleh
     * `php artisan trends:update-cache` (dijadwalkan lewat scheduler).
     * Jadi halaman ini tetap cepat meskipun daftar keyword sembako panjang,
     * karena tidak ada panggilan ke Google Trends saat halaman dibuka.
     */
    public function index(): View
    {
        // ============================================================
        // 1) INSIGHT (kartu hijau) & TREND GLOBAL (sidebar kanan)
        //    -> dari cache source='sembako', terurut skor tertinggi.
        // ============================================================
        $sembakoRecs = ProductRecommendation::where('source', 'sembako')
            ->where('is_active', true)
            ->orderByDesc('trend_score')
            ->get();

        $insight = $this->buildInsight($sembakoRecs->first());

        $trends = $sembakoRecs->take(5)->map(function (ProductRecommendation $r) {
            return [
                'name' => $r->keyword,
                'label' => 'Tingkat Minat: ' . (int) ($r->trend_score ?? 0),
                'label_cls' => 'text-emerald-600',
                'icon' => 'bi-fire',
                'icon_bg' => 'bg-red-50',
                'icon_color' => 'text-red-500',
            ];
        })->toArray();

        // ============================================================
        // 2) DAFTAR REKOMENDASI (tabel utama)
        //    -> dari cache source='local_product', produk yang paling
        //    sering dibeli, terurut skor trending tertinggi.
        // ============================================================
        $localRecs = ProductRecommendation::where('source', 'local_product')
            ->where('is_active', true)
            ->with('product')
            ->orderByDesc('trend_score')
            ->get();

        $products = $localRecs->map(function (ProductRecommendation $r) {
            $score = (int) ($r->trend_score ?? 0);
            $saranCls = $score > 80 ? 'restock-up' : ($score >= 30 ? 'restock-zero' : 'restock-up');

            return [
                'icon' => 'bi-bag-fill',
                'icon_bg' => 'bg-sky-50',
                'icon_color' => 'text-sky-400',
                'name' => $r->product->name ?? $r->keyword,
                'badge' => $score > 80 ? 'CRITICAL' : 'REKOMENDASI',
                'badge_cls' => $score > 80 ? 'badge-critical' : 'badge-normal',
                'stok' => $this->stockFor($r->product),
                'unit' => 'pcs',
                'prediksi' => '-',
                'pred_cls' => 'text-gray-700',
                'saran' => '+0',
                'saran_unit' => 'PCS',
                'saran_cls' => $saranCls,
                'saran_note' => $r->description ?? '',
            ];
        })->toArray();

        $stats = [
            'efisiensi' => ['value' => '92%', 'badge' => '+5% MoM', 'badge_cls' => 'text-emerald-600 bg-emerald-50', 'desc' => '...'],
            'potensi_rugi' => ['value' => 'Rp 450k', 'badge' => 'High Risk', 'badge_cls' => 'text-red-600 bg-red-50', 'desc' => '...'],
        ];

        return view('admin.recommendations.index', compact('insight', 'trends', 'products', 'stats'));
    }

    protected function buildInsight(?ProductRecommendation $top): array
    {
        if (!$top) {
            return [
                'headline' => 'Belum ada data trending sembako',
                'description' => 'Jalankan "php artisan trends:update-cache" dulu untuk mengisi data, lalu refresh halaman ini.',
            ];
        }

        return [
            'headline' => "\"{$top->keyword}\" sedang ramai dicari",
            'description' => 'Kata kunci ini masuk daftar produk sembako dengan minat pencarian tertinggi di Google Trends. Pertimbangkan menyiapkan stok atau promosi untuk produk ini.',
        ];
    }

    /**
     * Ambil jumlah stok dari model Product, mencoba beberapa nama kolom
     * yang umum dipakai (stock/stok/quantity/qty).
     */
    protected function stockFor(?Product $product): string
    {
        if (!$product) {
            return '-';
        }

        foreach (['stock', 'stok', 'quantity', 'qty'] as $field) {
            if (array_key_exists($field, $product->getAttributes())) {
                return (string) $product->getAttributes()[$field];
            }
        }

        return '-';
    }
}