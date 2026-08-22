<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expenditure;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminExpenditureController extends Controller
{
    public function index()
    {
        $expenditures = Expenditure::with('user', 'purchase.items')->latest()->paginate(15);
        return view('admin.expenditures.index', compact('expenditures'));
    }

    public function show(Expenditure $expenditure)
    {
        $expenditure->load(['user', 'purchase.items.product']);
        return view('admin.expenditures.show', compact('expenditure'));
    }

    public function create()
    {
        // Daftar pembelian untuk dikaitkan (opsional) dengan pengeluaran ini
        $purchases = Purchase::orderByDesc('tanggal')->limit(50)->get(['id', 'invoice', 'supplier', 'total']);

        return view('admin.expenditures.create', compact('purchases'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'description'   => 'required|string',
            'amount'        => 'required|numeric|min:0',
            'expense_date'  => 'required|date',
            'category'      => 'nullable|string',
            'purchase_id'   => 'nullable|exists:purchases,id',
            'receipt_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // FIX: kalau user tidak memilih pembelian di dropdown, browser
        // mengirim string kosong '' (bukan null) karena value option
        // placeholder-nya "". Kolom purchase_id di DB bertipe integer, jadi
        // insert '' langsung ditolak MySQL (strict mode): "Incorrect
        // integer value: ''". Perlu dikonversi eksplisit ke null di sini.
        $validated['purchase_id'] = $validated['purchase_id'] ?: null;

        $validated['created_by'] = Auth::id();

        DB::transaction(function () use ($request, $validated) {
            // Nota disimpan ke record Purchase yang dikaitkan (bukan ke Expenditure),
            // sesuai relasi purchase_id yang sudah ada di kedua model.
            if ($request->hasFile('receipt_image') && !empty($validated['purchase_id'])) {
                $purchase = Purchase::find($validated['purchase_id']);
                if ($purchase) {
                    if ($purchase->receipt_image && Storage::disk('public')->exists($purchase->receipt_image)) {
                        Storage::disk('public')->delete($purchase->receipt_image);
                    }
                    $path = $request->file('receipt_image')->store('receipts', 'public');
                    $purchase->update(['receipt_image' => $path]);
                }
            }

            Expenditure::create($validated);
        });

        return redirect()->route('expenditures.index')->with('success', 'Pengeluaran berhasil dicatat');
    }

    public function edit(Expenditure $expenditure)
    {
        $purchases = Purchase::orderByDesc('tanggal')->limit(50)->get(['id', 'invoice', 'supplier', 'total']);

        return view('admin.expenditures.edit', compact('expenditure', 'purchases'));
    }

    public function update(Request $request, Expenditure $expenditure)
    {
        $validated = $request->validate([
            'description'   => 'required|string',
            'amount'        => 'required|numeric|min:0',
            'expense_date'  => 'required|date',
            'category'      => 'nullable|string',
            'purchase_id'   => 'nullable|exists:purchases,id',
            'receipt_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // FIX: sama seperti store() — string kosong dari dropdown yang
        // tidak dipilih harus dikonversi ke null sebelum masuk ke kolom
        // integer purchase_id.
        $validated['purchase_id'] = $validated['purchase_id'] ?: null;

        DB::transaction(function () use ($request, $validated, $expenditure) {
            if ($request->hasFile('receipt_image') && !empty($validated['purchase_id'])) {
                $purchase = Purchase::find($validated['purchase_id']);
                if ($purchase) {
                    if ($purchase->receipt_image && Storage::disk('public')->exists($purchase->receipt_image)) {
                        Storage::disk('public')->delete($purchase->receipt_image);
                    }
                    $path = $request->file('receipt_image')->store('receipts', 'public');
                    $purchase->update(['receipt_image' => $path]);
                }
            }

            $expenditure->update($validated);
        });

        return redirect()->route('expenditures.show', $expenditure)->with('success', 'Pengeluaran diperbarui');
    }

    public function destroy(Expenditure $expenditure)
    {
        // Hanya hapus record expenditure; nota tetap di purchase (milik purchase, bukan expenditure)
        $expenditure->delete();

        return redirect()->route('expenditures.index')->with('success', 'Pengeluaran dihapus');
    }
}