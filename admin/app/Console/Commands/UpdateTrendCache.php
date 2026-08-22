<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRecommendation;
use App\Models\SaleItem;
use App\Services\TrendsService;
use Illuminate\Console\Command;

class UpdateTrendCache extends Command
{
    /**
     * php artisan trends:update-cache
     */
    protected $signature = 'trends:update-cache';

    protected $description = 'Update cache skor trending (kategori & produk lokal terlaris) dari Google Trends ke database';

    protected const MAX_LOCAL_PRODUCTS = 20;

    /**
     * Batas total keyword yang dikirim ke Google Trends untuk sisi
     * "sembako" (kategori + produk gabungan). Dibatasi supaya proses
     * `trends:update-cache` tidak berjalan terlalu lama / kena rate-limit.
     */
    protected const MAX_SEMBAKO_KEYWORDS = 60;

    public function handle(TrendsService $svc): int
    {
        $this->info('Memperbarui skor trending kategori & produk (sembako)...');
        $this->updateSembako($svc);

        $this->info('Memperbarui skor trending produk lokal terlaris...');
        $this->updateLocalProducts($svc);

        $this->info('Cache trending berhasil diperbarui.');
        return self::SUCCESS;
    }

    /**
     * Susun daftar keyword dari DB: nama kategori (categories.name) +
     * nama produk (products.name), digabung dan dibatasi jumlahnya.
     * Kalau DB kosong (belum ada kategori/produk sama sekali), fallback
     * ke config('services.trends.sembako_keywords').
     */
    protected function collectSembakoKeywords(): array
    {
        $categoryNames = Category::query()
            ->where('is_active', true)
            ->pluck('name')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $productNames = Product::query()
            ->pluck('name')
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Prioritaskan nama kategori dulu (biasanya jumlahnya sedikit),
        // baru isi sisa kuota dengan nama produk.
        $combined = array_values(array_unique(array_merge($categoryNames, $productNames)));

        if (empty($combined)) {
            $this->warn('Tabel categories & products kosong, pakai fallback config(services.trends.sembako_keywords).');
            return config('services.trends.sembako_keywords', []);
        }

        if (count($combined) > self::MAX_SEMBAKO_KEYWORDS) {
            $this->warn(
                count($combined) . ' keyword ditemukan dari DB, dipotong jadi '
                . self::MAX_SEMBAKO_KEYWORDS . ' (kategori diprioritaskan, sisanya diisi produk).'
            );
            $combined = array_slice($combined, 0, self::MAX_SEMBAKO_KEYWORDS);
        }

        return $combined;
    }

    /**
     * Cek skor trending untuk keyword gabungan kategori + produk dari DB,
     * simpan ke product_recommendations dengan source = 'sembako'.
     * Dipakai untuk kartu Insight & sidebar "Trend Global".
     */
    protected function updateSembako(TrendsService $svc): void
    {
        $keywords = $this->collectSembakoKeywords();

        if (empty($keywords)) {
            $this->error('Tidak ada keyword sama sekali (DB kosong & config juga kosong), dilewati.');
            return;
        }

        $res = $svc->topProducts($keywords, 'today 3-m', count($keywords), false);
        $data = $res['data'] ?? []; // sudah terurut skor tertinggi dulu

        if (empty($data)) {
            $this->error('Tidak ada data yang didapat dari trends-service.');
            return;
        }

        $now = now();
        $rank = 1;

        foreach ($data as $row) {
            ProductRecommendation::updateOrCreate(
                [
                    'keyword' => $row['product'],
                    'fetched_at' => $now->toDateString(),
                ],
                [
                    'product_id' => null,
                    'trend_score' => $row['score'],
                    'rank' => $rank,
                    'priority' => $rank,
                    'geo' => config('services.trends.geo', 'ID'),
                    'source' => 'sembako',
                    'description' => $row['meta'] ?? null,
                    'is_active' => true,
                ]
            );
            $rank++;
        }

        // Nonaktifkan entri sembako dari hari-hari sebelumnya supaya tidak menumpuk
        ProductRecommendation::where('source', 'sembako')
            ->whereDate('fetched_at', '<', $now->toDateString())
            ->update(['is_active' => false]);

        $this->line(count($data) . ' keyword sembako (kategori + produk) diperbarui.');
    }

    /**
     * Cek skor trending untuk nama produk yang paling sering dibeli
     * (berdasarkan sale_items), simpan ke product_recommendations dengan
     * source = 'local_product' dan product_id terisi. Dipakai untuk
     * "Daftar Rekomendasi".
     */
    protected function updateLocalProducts(TrendsService $svc): void
    {
        $topSold = SaleItem::query()
            ->selectRaw('product_id, SUM(quantity) as total_sold')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(self::MAX_LOCAL_PRODUCTS)
            ->with('product')
            ->get()
            ->filter(fn ($row) => $row->product !== null);

        if ($topSold->isEmpty()) {
            $this->warn('Belum ada data penjualan (sale_items kosong), dilewati.');
            return;
        }

        $names = $topSold->map(fn ($row) => $row->product->name)->filter()->unique()->values()->all();

        $res = $svc->topProducts($names, 'today 3-m', count($names), false);
        $scoreMap = collect($res['data'] ?? [])->keyBy(fn ($row) => $row['product']);

        $now = now();

        foreach ($topSold as $row) {
            $product = $row->product;
            $entry = $scoreMap->get($product->name);
            $score = $entry['score'] ?? 0;

            ProductRecommendation::updateOrCreate(
                [
                    'keyword' => $product->name,
                    'fetched_at' => $now->toDateString(),
                ],
                [
                    'product_id' => $product->id,
                    'trend_score' => $score,
                    'rank' => null,
                    'priority' => (int) round($score),
                    'geo' => config('services.trends.geo', 'ID'),
                    'source' => 'local_product',
                    'description' => "Terjual {$row->total_sold} pcs \u{00B7} Skor interest Google Trends: {$score}",
                    'is_active' => true,
                ]
            );
        }

        // Nonaktifkan entri produk lokal dari hari-hari sebelumnya
        ProductRecommendation::where('source', 'local_product')
            ->whereDate('fetched_at', '<', $now->toDateString())
            ->update(['is_active' => false]);

        $this->line($topSold->count() . ' produk lokal diperbarui.');
    }
}