<?php

namespace App\Jobs;

use App\Models\TrendItem;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MatchTrendItemsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $items = TrendItem::whereIn('status', ['new','review'])->limit(50)->get();
        foreach ($items as $it) {
            $name = $it->normalized_name ?? $it->external_name;
            $product = Product::where('sku', $name)->first()
                ?? Product::where('name', 'like', "%{$name}%")->first();

            $confidence = 0;
            if ($product) {
                $confidence = 100;
            } else {
                $candidates = Product::limit(200)->pluck('name','id')->toArray();
                $bestId = null; $bestDist = PHP_INT_MAX;
                foreach ($candidates as $id => $pname) {
                    $dist = levenshtein(mb_strtolower($name), mb_strtolower($pname));
                    if ($dist < $bestDist) { $bestDist = $dist; $bestId = $id; }
                }
                if ($bestId !== null && $bestDist <= 3) {
                    $product = Product::find($bestId);
                    $confidence = max(50, 100 - ($bestDist * 10));
                }
            }

            if ($product && $confidence >= 80) {
                $it->update(['matched_product_id'=>$product->id,'match_confidence'=>$confidence,'status'=>'matched']);
            } elseif ($product && $confidence >= 50) {
                $it->update(['match_confidence'=>$confidence,'status'=>'review']);
            } else {
                $it->update(['status'=>'review']);
            }
        }
    }
}
