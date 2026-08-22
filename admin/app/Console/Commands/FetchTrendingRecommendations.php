<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductRecommendation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchTrendingRecommendations extends Command
{
    /**
     * php artisan recommendations:fetch-trending
     * php artisan recommendations:fetch-trending --geo=ID --limit=20
     */
    protected $signature = 'recommendations:fetch-trending {--geo=ID} {--limit=20}';

    protected $description = 'Ambil topik trending dari Google Trends (via trends-service) dan simpan sebagai rekomendasi produk';

    public function handle(): int
    {
        $geo = $this->option('geo');
        $limit = (int) $this->option('limit');

        $baseUrl = config('services.trends.url');
        $apiKey = config('services.trends.key');

        if (empty($baseUrl)) {
            $this->error("TRENDS_SERVICE_URL belum di-set di .env");
            return self::FAILURE;
        }

        $this->info("Memanggil {$baseUrl}/trending-now ...");

        $response = Http::withHeaders([
                'X-API-Key' => $apiKey,
            ])
            ->timeout(30)
            ->get(rtrim($baseUrl, '/') . '/trending-now', [
                'geo' => $geo,
                'limit' => $limit,
            ]);

        if ($response->failed()) {
            $this->error('Gagal memanggil trends service: ' . $response->status() . ' - ' . $response->body());
            Log::warning('Trends service gagal', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return self::FAILURE;
        }

        $results = $response->json('results', []);

        if (empty($results)) {
            $this->warn('Tidak ada data trending yang didapat. Response: ' . $response->body());
            return self::SUCCESS;
        }

        $now = now();
        $saved = 0;

        foreach ($results as $item) {
            $keyword = $item['keyword'] ?? null;
            $rank = $item['rank'] ?? null;

            if (!$keyword) {
                continue;
            }

            // Coba cocokkan dengan produk existing (opsional, tidak wajib).
            // Kalau tidak ketemu, product_id tetap null — rekomendasi tetap
            // tersimpan sebagai temuan trending baru.
            $matchedProduct = Product::where('name', 'like', "%{$keyword}%")->first();

            ProductRecommendation::updateOrCreate(
                [
                    'keyword' => $keyword,
                    'fetched_at' => $now->toDateString(),
                ],
                [
                    'product_id'  => $matchedProduct?->id,
                    'priority'    => $rank,
                    'rank'        => $rank,
                    'geo'         => strtoupper($geo),
                    'source'      => 'google_trends',
                    'description' => $matchedProduct
                        ? "Cocok dengan produk existing: {$matchedProduct->name}"
                        : "Topik trending baru, belum ada di katalog produk",
                    'is_active'   => true,
                    'created_by'  => null,
                ]
            );

            $saved++;
            $this->line("- [{$rank}] {$keyword}" . ($matchedProduct ? " (cocok: {$matchedProduct->name})" : ""));
        }

        $this->info("{$saved} rekomendasi trending berhasil disimpan/diperbarui.");
        return self::SUCCESS;
    }
}