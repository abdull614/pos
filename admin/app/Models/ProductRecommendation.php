<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class ProductRecommendation extends Model
{
    protected $fillable = [
        'product_id',     // nullable — hanya diisi kalau cocok dengan produk existing
        'keyword',        // topik/keyword trending dari Google Trends
        'priority',
        'rank',           // posisi trending (1 = paling atas)
        'trend_score',    // skor numerik (dari endpoint /trend-score), boleh null
        'geo',
        'source',         // default 'google_trends'
        'fetched_at',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'trend_score' => 'float',
        'rank'        => 'integer',
        'fetched_at'  => 'datetime',
        // Jika kolom description menyimpan JSON, aktifkan casting:
        // 'description' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: hanya rekomendasi yang belum terhubung ke produk existing
     * (murni temuan trending baru).
     */
    public function scopeUnlinked($query)
    {
        return $query->whereNull('product_id');
    }

    /**
     * Scope: urutkan berdasarkan posisi trending, paling trending duluan.
     */
    public function scopeMostTrending($query)
    {
        return $query->orderBy('rank');
    }
}