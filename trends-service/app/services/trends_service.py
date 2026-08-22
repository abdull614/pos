import time
import logging
import xml.etree.ElementTree as ET

import requests
from pytrends.request import TrendReq

logger = logging.getLogger(__name__)

TRENDING_RSS_URL = "https://trends.google.com/trends/trending/rss"


def _chunk(items, size):
    """Bagi list menjadi beberapa bagian sesuai ukuran maksimal."""
    for i in range(0, len(items), size):
        yield items[i:i + size]


class TrendsService:
    """
    Membungkus semua interaksi dengan pytrends (library tidak resmi untuk
    Google Trends). Semua konfigurasi (retry, batch size, dsb) diambil dari
    Config supaya mudah diubah lewat environment variable.
    """

    def __init__(self, config):
        self.config = config

    def _client(self):
        return TrendReq(hl=self.config.DEFAULT_HL, tz=self.config.DEFAULT_TZ)

    def fetch_scores(self, keywords: list[str], geo: str | None = None,
                      timeframe: str | None = None) -> dict[str, float]:
        """
        Ambil rata-rata interest score (0-100, relatif per batch) untuk
        sekumpulan keyword. Otomatis dipecah jadi batch maksimal
        `BATCH_SIZE` keyword karena itu batas Google Trends.
        """
        geo = geo or self.config.DEFAULT_GEO
        timeframe = timeframe or self.config.DEFAULT_TIMEFRAME

        all_scores: dict[str, float] = {}
        client = self._client()

        for batch in _chunk(keywords, self.config.BATCH_SIZE):
            batch_scores = self._fetch_batch(client, batch, geo, timeframe)
            all_scores.update(batch_scores)
            time.sleep(1)  # jeda kecil antar batch untuk mengurangi risiko rate-limit

        return all_scores

    def _fetch_batch(self, client, keywords, geo, timeframe) -> dict[str, float]:
        for attempt in range(1, self.config.RETRY_ATTEMPTS + 1):
            try:
                client.build_payload(keywords, timeframe=timeframe, geo=geo)
                df = client.interest_over_time()

                if df.empty:
                    return {kw: 0.0 for kw in keywords}

                scores = {}
                for kw in keywords:
                    scores[kw] = round(float(df[kw].mean()), 2) if kw in df.columns else 0.0
                return scores

            except Exception as exc:
                logger.warning("Gagal ambil batch %s (percobaan %d/%d): %s",
                                keywords, attempt, self.config.RETRY_ATTEMPTS, exc)
                if attempt == self.config.RETRY_ATTEMPTS:
                    return {kw: 0.0 for kw in keywords}
                time.sleep(self.config.RETRY_DELAY_SEC * attempt)

    def trending_now(self, geo: str | None = None, hours: int = 48, limit: int = 20) -> list[dict]:
        """
        Ambil daftar topik yang sedang trending saat ini di suatu negara,
        tanpa perlu keyword awal.

        Sumber data: RSS feed resmi dari Google Trends
        (https://trends.google.com/trends/trending/rss?geo=XX&hours=48), BUKAN
        pytrends.trending_searches() — fitur itu sudah tidak berfungsi
        karena Google mengubah endpoint lamanya (banyak dilaporkan sebagai
        404 error di pytrends). RSS feed ini adalah fitur resmi yang masih
        aktif dan gratis, jadi tidak butuh pytrends sama sekali untuk endpoint ini.

        `geo` pakai kode ISO negara (mis. 'ID', 'US'), konsisten dengan
        parameter geo di /trend-score.
        """
        geo = geo or self.config.DEFAULT_GEO

        for attempt in range(1, self.config.RETRY_ATTEMPTS + 1):
            try:
                resp = requests.get(
                    TRENDING_RSS_URL,
                    params={"geo": geo, "hours": hours},
                    headers={"User-Agent": "Mozilla/5.0"},
                    timeout=10,
                )
                resp.raise_for_status()

                root = ET.fromstring(resp.content)
                items = root.findall(".//item")[:limit]

                results = []
                for i, item in enumerate(items):
                    title_el = item.find("title")
                    if title_el is not None and title_el.text:
                        results.append({"keyword": title_el.text.strip(), "rank": i + 1})

                return results

            except Exception as exc:
                logger.warning("Gagal ambil trending RSS geo=%s (percobaan %d/%d): %s",
                                geo, attempt, self.config.RETRY_ATTEMPTS, exc)
                if attempt == self.config.RETRY_ATTEMPTS:
                    return []
                time.sleep(self.config.RETRY_DELAY_SEC * attempt)

    def calculate_recommendation_score(self, products_data: list[dict]) -> list[dict]:
        """
        Menghitung skor total rekomendasi berdasarkan perbandingan data internal 
        dan eksternal sesuai dengan laporan: S_total = (0.6 * S_int) + (0.4 * S_ext).
        Menggunakan properti 'keyword' terpisah dari 'name' (nama produk/kategori) 
        agar pencarian Google Trends lebih optimal dan tidak kaku.
        """
        if not products_data:
            return []

        # Ambil 'keyword' jika ada, jika tidak ada fallback ke 'name' produk
        keywords = [p.get("keyword") or p["name"] for p in products_data]
        external_scores = self.fetch_scores(keywords)

        max_freq = max([p.get("purchase_frequency", 0) for p in products_data]) or 1.0
        max_ext = 100.0 

        results = []
        for p in products_data:
            name = p["name"]
            kw = p.get("keyword") or name
            
            # Normalisasi data internal (S_int) berdasarkan frekuensi pembelian
            raw_int = p.get("purchase_frequency", 0)
            s_int = (raw_int / max_freq) * 100.0

            # Normalisasi data eksternal / Google Trends (S_ext) berdasarkan keyword
            raw_ext = external_scores.get(kw, 0.0)
            s_ext = (raw_ext / max_ext) * 100.0

            # Hitung skor total dengan bobot 60% internal & 40% eksternal
            s_total = (0.6 * s_int) + (0.4 * s_ext)
            s_total = round(s_total, 2)

            # Threshold keputusan (>= 60 direkomendasikan)
            is_recommended = s_total >= 60.0

            results.append({
                "product_id": p.get("product_id"),
                "name": name,
                "keyword": kw,
                "s_int": round(s_int, 2),
                "s_ext": round(s_ext, 2),
                "s_total": s_total,
                "recommended": is_recommended
            })

        return results
