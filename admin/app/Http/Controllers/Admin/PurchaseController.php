<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with('items.product', 'createdBy')->latest()->paginate(15);
        return view('admin.purchases.index', compact('purchases'));
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('items.product', 'createdBy');
        return view('admin.purchases.show', compact('purchase'));
    }

    public function create()
    {
        return view('admin.purchases.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice' => 'nullable|string',
            'supplier' => 'nullable|string',
            'tanggal' => 'nullable|date',
            'note' => 'nullable|string',
            'total' => 'nullable|numeric',
            'receipt_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ($request->hasFile('receipt_image')) {
            $validated['receipt_image'] = $request->file('receipt_image')->store('receipts', 'public');
        }

        $validated['created_by'] = Auth::id();

        DB::transaction(function () use ($validated) {
            Purchase::create($validated);
        });

        return redirect()->route('purchases.index')->with('success', 'Pembelian berhasil dibuat');
    }

    public function edit(Purchase $purchase)
    {
        return view('admin.purchases.edit', compact('purchase'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'invoice' => 'nullable|string',
            'supplier' => 'nullable|string',
            'tanggal' => 'nullable|date',
            'note' => 'nullable|string',
            'total' => 'nullable|numeric',
            'receipt_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ($request->hasFile('receipt_image')) {
            if ($purchase->receipt_image && Storage::disk('public')->exists($purchase->receipt_image)) {
                Storage::disk('public')->delete($purchase->receipt_image);
            }
            $validated['receipt_image'] = $request->file('receipt_image')->store('receipts', 'public');
        }

        $purchase->update($validated);

        return redirect()->route('purchases.show', $purchase)->with('success', 'Pembelian diperbarui');
    }

    public function destroy(Purchase $purchase)
    {
        if ($purchase->receipt_image && Storage::disk('public')->exists($purchase->receipt_image)) {
            Storage::disk('public')->delete($purchase->receipt_image);
        }

        $purchase->delete();

        return redirect()->route('purchases.index')->with('success', 'Pembelian dihapus');
    }
}
