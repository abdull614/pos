<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ProductRecommendation;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function index()
    {
        $recommendations = ProductRecommendation::with(['product', 'createdBy'])
                                                ->where('is_active', true)
                                                ->orderBy('priority', 'desc')
                                                ->get();

        return response()->json($recommendations);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'priority' => 'integer|min:0',
            'description' => 'nullable|string',
        ]);

        $recommendation = ProductRecommendation::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        return response()->json($recommendation->load('product'), 201);
    }

    public function update(Request $request, ProductRecommendation $recommendation)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'priority' => 'integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $recommendation->update($validated);

        return response()->json($recommendation->load('product'));
    }

    public function destroy(ProductRecommendation $recommendation)
    {
        $recommendation->delete();

        return response()->json(['message' => 'Recommendation deleted']);
    }
}
