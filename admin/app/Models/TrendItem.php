<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrendItem extends Model
{
    protected $fillable = [
        'external_name','normalized_name','score','source','payload',
        'matched_product_id','match_confidence','status',
    ];

    protected $casts = ['payload' => 'array'];

    public function matchedProduct()
    {
        return $this->belongsTo(Product::class, 'matched_product_id');
    }
}
