<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrendsService
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected string $defaultGeo;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.trends.url', 'http://localhost:5000'), '/');
        $this->apiKey = config('services.trends.key');
        $this->defaultGeo = config('services.trends.geo', 'ID');
    }

    protected function headers(): array
    {
        return $this->apiKey ? ['X-API-Key' => $this->apiKey] : [];
    }

    /**
     * Ambil daftar "produk"/topik trending.
     *
     * - Kalau $keywords KOSONG: mode discovery, ambil topik yang SEDANG
     *   trending sekarang lewat /trending-now (RSS Google Trends), tanpa
     *   perlu tahu nama produk sebelumnya.
     * - Kalau $keywords DIISI: bandingkan skor interest keyword tersebut
     *   lewat /trend-score, diurutkan dari skor tertinggi.
     *
     * Return selalu berbentuk ['data' => [ ['product'=>.., 'entity'=>..,
     * 'score'=>.., 'meta'=>..], ... ]], supaya konsumen (controller) tidak
     * perlu tahu apakah datanya dari trending-now atau trend-score.
     *
     * $raw=true akan menambahkan key 'raw' berisi response asli dari service,
     * berguna untuk debugging.
     */
    public function topProducts(array $keywords = [], string $timeframe = 'today 3-m', int $limit = 20, bool $raw = false): array
    {
        if (empty($keywords)) {
            return $this->fromTrendingNow($limit, $raw);
        }

        return $this->fromTrendScore($keywords, $timeframe, $limit, $raw);
    }

    protected function fromTrendingNow(int $limit, bool $raw): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout(60)
            ->get("{$this->baseUrl}/trending-now", [
                'geo' => $this->defaultGeo,
                'limit' => $limit,
            ]);

        if ($response->failed()) {
            Log::warning('TrendsService: gagal ambil trending-now', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return ['data' => []];
        }

        $results = $response->json('results', []);

        $data = array_map(function ($item) {
            $rank = $item['rank'] ?? null;
            // RSS trending tidak punya skor numerik, jadi kita perkirakan
            // dari posisi rank (rank 1 -> 100, menurun bertahap).
            $score = $rank ? max(0, 100 - (($rank - 1) * 8)) : 0;

            return [
                'product' => $item['keyword'] ?? 'Unknown',
                'entity'  => $item['keyword'] ?? 'Unknown',
                'score'   => $score,
                'meta'    => "Rank #{$rank} trending sekarang di Google Trends",
            ];
        }, $results);

        return $raw ? ['raw' => $response->json(), 'data' => $data] : ['data' => $data];
    }

    protected function fromTrendScore(array $keywords, string $timeframe, int $limit, bool $raw): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout(60)
            ->get("{$this->baseUrl}/trend-score", [
                'keywords' => implode(',', $keywords),
                'timeframe' => $timeframe,
                'geo' => $this->defaultGeo,
            ]);

        if ($response->failed()) {
            Log::warning('TrendsService: gagal ambil trend-score', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return ['data' => []];
        }

        $scores = $response->json('scores', []);
        arsort($scores); // urutkan skor tertinggi dulu

        $data = [];
        foreach ($scores as $keyword => $score) {
            if (count($data) >= $limit) {
                break;
            }
            $data[] = [
                'product' => $keyword,
                'entity'  => $keyword,
                'score'   => $score,
                'meta'    => "Skor interest Google Trends: {$score}",
            ];
        }

        return $raw ? ['raw' => $response->json(), 'data' => $data] : ['data' => $data];
    }

    /**
     * Ringkasan singkat (headline + description) tentang topik paling
     * trending saat ini, dipakai untuk kartu "Intelligence Insight".
     */
    public function getInsight(): array
    {
        $top = $this->topProducts([], 'today 3-m', 1)['data'][0] ?? null;

        if (!$top) {
            return [
                'headline' => 'Belum ada data trending',
                'description' => 'Jalankan pembaruan data trending untuk melihat insight terbaru.',
            ];
        }

        return [
            'headline' => "\"{$top['product']}\" sedang ramai dicari",
            'description' => 'Topik ini masuk daftar trending Google Trends hari ini. Pertimbangkan menyiapkan stok atau promosi terkait produk yang relevan.',
        ];
    }
}