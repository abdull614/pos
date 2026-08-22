<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Expenditure;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json($query->paginate(15));
    }

    public function show(Product $product)
    {
        return response()->json($product->load('category', 'saleItems'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sku' => 'required|unique:products',
            'name' => 'required|string',
            'barcode' => 'nullable|string|max:50|unique:products,barcode',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'integer|min:0',
            'unit' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            // BARU: dikirim Flutter (tambah_produk_screen.dart) saat produk
            // ini dibuat sebagai BAGIAN dari submit nota (StockInvoiceController).
            // Kalau true, endpoint ini TIDAK membuat Purchase/Expenditure
            // sendiri — biarkan StockInvoiceController::store() yang mencatat
            // SATU nota utuh (dengan foto, supplier, invoice) yang mencakup
            // biaya produk baru ini juga. Tanpa flag ini (mis. dipanggil
            // berdiri sendiri dari panel admin), perilaku lama tetap berlaku:
            // otomatis catat Expenditure untuk stok awal.
            'skip_purchase_record' => 'nullable|boolean',
        ]);

        $skipPurchaseRecord = filter_var(
            $request->input('skip_purchase_record', false),
            FILTER_VALIDATE_BOOLEAN
        );

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image'] = $path;
        }

        $product = DB::transaction(function () use ($validated, $skipPurchaseRecord) {
            $product = Product::create($validated);

            // Hanya buat nota otomatis kalau TIDAK sedang jadi bagian dari
            // submit nota manual (skip_purchase_record=false, default).
            if (!$skipPurchaseRecord && ($validated['stock'] ?? 0) > 0) {
                $purchase = Purchase::create([
                    'invoice'      => null,
                    'supplier'     => null,
                    'supplier_id'  => null,
                    'tanggal'      => now()->toDateString(),
                    'note'         => 'Stok awal saat produk dibuat',
                    'total'        => $validated['purchase_price'] * $validated['stock'],
                    'created_by'   => Auth::id(),
                    'status'       => 'completed',
                ]);

                PurchaseItem::create([
                    'purchase_id'       => $purchase->id,
                    'product_id'        => $product->id,
                    'quantity'          => $validated['stock'],
                    'purchase_price'    => $validated['purchase_price'],
                    'skip_stock_update' => true,
                ]);

                Expenditure::create([
                    'description'  => 'Pembelian stok - Nota: ' . ($purchase->invoice ?? $purchase->id),
                    'amount'       => $purchase->total,
                    'expense_date' => $purchase->tanggal,
                    'category'     => 'pembelian_stok',
                    'created_by'   => Auth::id(),
                    'purchase_id'  => $purchase->id,
                ]);
            }

            return $product;
        });

        return response()->json($product->load('category'), 201);
    }

    public function showByBarcode($barcode)
    {
        $product = Product::where('barcode', $barcode)->first();

        if (!$product) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        return response()->json($product->load('category'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'sku' => "required|unique:products,sku,{$product->id}",
            'name' => 'required|string',
            'barcode' => "nullable|string|max:50|unique:products,barcode,{$product->id}",
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'integer|min:0',
            'unit' => 'required|string',
            'is_active' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'skip_purchase_record' => 'nullable|boolean',
        ]);

        $skipPurchaseRecord = filter_var(
            $request->input('skip_purchase_record', false),
            FILTER_VALIDATE_BOOLEAN
        );

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $path = $request->file('image')->store('products', 'public');
            $validated['image'] = $path;
        }

        $stockBefore = $product->stock;
        $stockAfter = $validated['stock'];
        $stockAdded = $stockAfter - $stockBefore;

        DB::transaction(function () use ($product, $validated, $stockAdded, $skipPurchaseRecord) {
            $product->update($validated);

            if (!$skipPurchaseRecord && $stockAdded > 0) {
                $purchase = Purchase::create([
                    'invoice'      => null,
                    'supplier'     => null,
                    'supplier_id'  => null,
                    'tanggal'      => now()->toDateString(),
                    'note'         => 'Restock via halaman edit produk',
                    'total'        => $validated['purchase_price'] * $stockAdded,
                    'created_by'   => Auth::id(),
                    'status'       => 'completed',
                ]);

                PurchaseItem::create([
                    'purchase_id'       => $purchase->id,
                    'product_id'        => $product->id,
                    'quantity'          => $stockAdded,
                    'purchase_price'    => $validated['purchase_price'],
                    'skip_stock_update' => true,
                ]);

                Expenditure::create([
                    'description'  => 'Restock - Nota: ' . ($purchase->invoice ?? $purchase->id),
                    'amount'       => $purchase->total,
                    'expense_date' => $purchase->tanggal,
                    'category'     => 'pembelian_stok',
                    'created_by'   => Auth::id(),
                    'purchase_id'  => $purchase->id,
                ]);
            }
        });

        return response()->json($product->fresh()->load('category'));
    }

    public function destroy(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();
        return response()->json(['message' => 'Product deleted']);
    }

    public function lowStock()
    {
        $products = Product::whereColumn('stock', '<=', 'min_stock')
            ->where('is_active', true)
            ->get();

        return response()->json($products);
    }
}