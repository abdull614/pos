<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SalesTransaction;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\CustomerReceivable;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesTransaction::with(['kasir', 'customer', 'saleItems.product']);

        if ($request->has('date_from')) {
            $query->whereDate('sold_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('sold_at', '<=', $request->date_to);
        }

        if ($request->has('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->latest('sold_at')->paginate(15));
    }

    public function show(SalesTransaction $salesTransaction)
    {
        return response()->json($salesTransaction->load(['kasir', 'customer', 'saleItems.product', 'receivable']));
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'customer_id'   => 'nullable|exists:customers,id',
        'payment_type'  => 'required|in:cash,debt,qris,transfer',
        'due_date'          => 'nullable|date', 
        'notes_receivable'  => 'nullable|string',
        'discount'      => 'numeric|min:0',
        'tax'           => 'numeric|min:0',
        'items'         => 'required|array',
        'items.*.product_id'  => 'required|exists:products,id',
        'items.*.quantity'    => 'required|integer|min:1',
        'items.*.unit_price'  => 'required|numeric|min:0',
        'notes'         => 'nullable|string',
    ]);

    return DB::transaction(function () use ($validated) {
        // 1. Hitung subtotal & siapkan items
        $subtotal = 0;
        $items = [];

        foreach ($validated['items'] as $item) {
            $product = Product::find($item['product_id']);
            $itemSubtotal = $item['quantity'] * $item['unit_price'];
            $subtotal += $itemSubtotal;

            $items[] = [
                'product_id' => $item['product_id'],
                'quantity'   => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal'   => $itemSubtotal,
            ];

            // Kurangi stok produk
            $product->decrement('stock', $item['quantity']);
        }

        // 2. Diskon, pajak, total
        $discount = $validated['discount'] ?? 0;
        $tax      = $validated['tax'] ?? 0;
        $total    = ($subtotal - $discount) + $tax;

        // 3. Simpan transaksi
        $transaction = SalesTransaction::create([
            'invoice_number' => 'INV-' . date('YmdHis'),
            'kasir_id'       => auth()->id(),
            'customer_id'    => $validated['customer_id'] ?? null,
            'subtotal'       => $subtotal,
            'discount'       => $discount,
            'tax'            => $tax,
            'total'          => $total,
            'payment_type'   => $validated['payment_type'],
            'status'         => 'completed',
            'notes'          => $validated['notes'] ?? null,
        ]);

        // 4. Simpan item penjualan
        foreach ($items as $item) {
            SaleItem::create([
                'sales_transaction_id' => $transaction->id,
                ...$item,
            ]);
        }

        // 5. Jika pembayaran utang, catat piutang
        if ($validated['payment_type'] === 'debt' && $validated['customer_id']) {
            CustomerReceivable::create([
                'customer_id'            => $validated['customer_id'],
                'sales_transaction_id'   => $transaction->id,
                'amount'                 => $total,
                'remaining'              => $total,
                'status'                 => 'unpaid',
                'due_date'               => $validated['due_date'] ?? null,
                'notes'                  => $validated['notes_receivable'] ?? null, // ✅ sudah diperbaiki
            ]);

            Customer::find($validated['customer_id'])->increment('total_debt', $total);
        }

        return response()->json($transaction->load('saleItems.product'), 201);
    });
}

    public function todaysSalesReport()
    {
        $today = now('Asia/Jakarta')->toDateString();
        $sales = SalesTransaction::whereDate('created_at', $today)
                                ->where('status', 'completed')
                                ->get();

        $totalSales = $sales->sum('total');
        $cashSales = $sales->where('payment_type', 'cash')->sum('total');
        $debtSales = $sales->where('payment_type', 'debt')->sum('total');
        $transactionCount = $sales->count();

        return response()->json([
            'date' => $today,
            'total_sales' => $totalSales,
            'cash_sales' => $cashSales,
            'debt_sales' => $debtSales,
            'transaction_count' => $transactionCount,
        ]);
    }
}
