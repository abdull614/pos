<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\Expenditure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class StockInvoiceController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier' => 'nullable|string',
            'nomor_nota' => 'nullable|string',
            'tanggal' => 'required|date',
            'catatan' => 'nullable|string',
            'items' => 'required|json',
            'receipt_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $items = json_decode($validated['items'], true);
        if (!is_array($items) || empty($items)) {
            throw ValidationException::withMessages(['items' => 'Items harus berupa array JSON dan tidak boleh kosong.']);
        }

        DB::beginTransaction();
        try {
            $receiptPath = null;
            if ($request->hasFile('receipt_image')) {
                $receiptPath = $request->file('receipt_image')->store('purchases', 'public');
            }

            $purchase = Purchase::create([
                'invoice' => $validated['nomor_nota'] ?? null,
                'supplier' => $validated['supplier'] ?? null,
                'supplier_id' => $request->input('supplier_id') ?? null,
                'receipt_image' => $receiptPath,
                'tanggal' => $validated['tanggal'],
                'note' => $validated['catatan'] ?? null,
                'total' => 0,
                'created_by' => Auth::id(),
                'status' => 'completed',
            ]);

            // NOTE: karena ProductController::store()/update() sekarang TIDAK
            // lagi membuat Expenditure sendiri untuk item yang dikirim dengan
            // skip_purchase_record=true (lihat ProductController), maka
            // seluruh biaya nota — termasuk item produk baru (skip_stock_update)
            // — murni jadi tanggung jawab Expenditure di sini. Tidak perlu lagi
            // memisahkan $total vs $expenditureTotal seperti sebelumnya.
            $total = 0;

            foreach ($items as $index => $it) {
                if (!isset($it['product_id']) || !isset($it['quantity'])) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Item index {$index} tidak lengkap. Diperlukan product_id dan quantity."
                    ], 422);
                }

                $product = Product::find($it['product_id']);
                if (!$product) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Product id {$it['product_id']} tidak ditemukan."
                    ], 404);
                }

                $qty = intval($it['quantity']);
                $price = isset($it['purchase_price']) ? floatval($it['purchase_price']) : floatval($product->purchase_price ?? 0);

                // Flag ini dikirim untuk item yang produknya BARU SAJA dibuat
                // (lewat ProductController::store() dengan skip_purchase_record
                // =true) dalam sesi submit nota yang sama. Stoknya sudah diset
                // langsung saat produk dibuat, jadi TIDAK BOLEH ditambah lagi
                // di sini (kalau ditambah lagi, stok dobel).
                $skipStockUpdate = filter_var($it['skip_stock_update'] ?? false, FILTER_VALIDATE_BOOLEAN);

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'purchase_price' => $price,
                    'skip_stock_update' => $skipStockUpdate,
                ]);

                $productChanges = [];
                if (!$skipStockUpdate) {
                    $productChanges['stock'] = intval($product->stock) + $qty;
                }
                if (isset($it['purchase_price'])) {
                    $productChanges['purchase_price'] = $price;
                }
                if (!empty($productChanges)) {
                    $product->update($productChanges);
                }

                $total += $qty * $price;
            }

            $purchase->update(['total' => $total]);

            // Selalu buat Expenditure untuk nota ini (kecuali totalnya 0,
            // yang seharusnya tidak mungkin terjadi kalau validasi items OK).
            if ($total > 0) {
                Expenditure::create([
                    'description' => 'Pembelian stok - Nota: ' . ($purchase->invoice ?? $purchase->id),
                    'amount' => $total,
                    'expense_date' => $purchase->tanggal ?? now()->toDateString(),
                    'category' => 'pembelian_stok',
                    'created_by' => Auth::id(),
                    'purchase_id' => $purchase->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Purchase berhasil disimpan',
                'data' => $purchase->load('items.product')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan saat menyimpan purchase',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        $purchases = Purchase::with('items.product')->latest()->paginate(20);
        return response()->json($purchases);
    }

    public function show(Purchase $purchase)
    {
        return response()->json($purchase->load('items.product'));
    }

    public function updateItems(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'items' => 'required|json',
        ]);

        $items = json_decode($validated['items'], true);
        if (!is_array($items) || empty($items)) {
            throw ValidationException::withMessages([
                'items' => 'Items harus berupa array JSON dan tidak boleh kosong.',
            ]);
        }

        DB::beginTransaction();
        try {
            $newTotal = 0;

            foreach ($items as $index => $it) {
                if (!isset($it['id'])) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Item index {$index} tidak memiliki id. Endpoint ini hanya untuk mengoreksi item yang sudah ada, bukan menambah item baru.",
                    ], 422);
                }

                $purchaseItem = PurchaseItem::where('id', $it['id'])
                    ->where('purchase_id', $purchase->id)
                    ->first();

                if (!$purchaseItem) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Item id {$it['id']} tidak ditemukan pada purchase ini.",
                    ], 404);
                }

                $newQty = intval($it['quantity'] ?? $purchaseItem->quantity);
                $newPrice = isset($it['purchase_price'])
                    ? floatval($it['purchase_price'])
                    : floatval($purchaseItem->purchase_price);

                if ($newQty <= 0) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Quantity item id {$it['id']} harus lebih dari 0.",
                    ], 422);
                }

                $qtyDelta = $newQty - $purchaseItem->quantity;

                if (!$purchaseItem->skip_stock_update && $qtyDelta !== 0) {
                    $product = Product::find($purchaseItem->product_id);
                    if ($product) {
                        $product->update(['stock' => intval($product->stock) + $qtyDelta]);
                    }
                }

                $purchaseItem->update([
                    'quantity' => $newQty,
                    'purchase_price' => $newPrice,
                ]);

                $newTotal += $newQty * $newPrice;
            }

            $purchase->update(['total' => $newTotal]);

            // Sinkronkan Expenditure nota dengan total baru (selalu penuh,
            // tidak perlu exclude item skip lagi — lihat catatan di store()).
            $expenditure = Expenditure::where('purchase_id', $purchase->id)->first();
            if ($expenditure) {
                $expenditure->update(['amount' => $newTotal]);
            } elseif ($newTotal > 0) {
                Expenditure::create([
                    'description' => 'Pembelian stok - Nota: ' . ($purchase->invoice ?? $purchase->id),
                    'amount' => $newTotal,
                    'expense_date' => $purchase->tanggal ?? now()->toDateString(),
                    'category' => 'pembelian_stok',
                    'created_by' => Auth::id(),
                    'purchase_id' => $purchase->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Item pembelian berhasil diperbarui',
                'data' => $purchase->load('items.product'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui item pembelian',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}