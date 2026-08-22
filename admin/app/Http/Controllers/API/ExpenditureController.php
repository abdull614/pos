<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Expenditure;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ExpenditureController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $query = Expenditure::query()->with('user', 'purchase')->orderByDesc('expense_date');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('from')) {
            $query->whereDate('expense_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('expense_date', '<=', $request->to);
        }

        return response()->json($query->paginate(20));
    }

    // BARU: dipakai Flutter untuk mengisi dropdown "kaitkan ke pembelian"
    // di form tambah/edit pengeluaran. Meniru query yang sama seperti di
    // AdminExpenditureController@create/edit (Blade).
    public function purchasesLookup(Request $request)
    {
        $query = Purchase::query()->orderByDesc('tanggal');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice', 'like', "%{$search}%")
                  ->orWhere('supplier', 'like', "%{$search}%");
            });
        }

        // FIX: 'receipt_image' harus ikut di-select, karena accessor
        // getReceiptUrlAttribute() (yang menghasilkan 'receipt_url')
        // membacanya dari atribut model. Tanpa ini, receipt_url akan
        // selalu null walau purchase sebenarnya sudah punya nota.
        $purchases = $query->limit(50)->get(['id', 'invoice', 'supplier', 'total', 'receipt_image']);

        // 'receipt_url' sudah otomatis ikut ter-serialize karena
        // didaftarkan di $appends pada model Purchase. Di sini kita cuma
        // menyembunyikan 'receipt_image' (path mentah di storage) karena
        // client cukup butuh URL publiknya saja.
        $purchases->each(function ($purchase) {
            $purchase->makeHidden('receipt_image');
        });

        return response()->json(['data' => $purchases]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'description'   => 'required|string',
            'amount'        => 'required|numeric|min:0',
            'expense_date'  => 'required|date',
            'category'      => 'nullable|string',
            'purchase_id'   => 'nullable|exists:purchases,id',
            'receipt_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // FIX: dropdown kosong dari client bisa mengirim string kosong '',
        // bukan null. Kolom purchase_id integer, jadi konversi eksplisit.
        $data['purchase_id'] = $data['purchase_id'] ?: null;
        $data['created_by'] = Auth::id();

        $expenditure = DB::transaction(function () use ($request, $data) {
            // Nota disimpan ke record Purchase yang dikaitkan (bukan ke
            // Expenditure), sesuai relasi purchase_id yang sudah ada di
            // kedua model — konsisten dengan AdminExpenditureController.
            if ($request->hasFile('receipt_image') && !empty($data['purchase_id'])) {
                $purchase = Purchase::find($data['purchase_id']);
                if ($purchase) {
                    if ($purchase->receipt_image && Storage::disk('public')->exists($purchase->receipt_image)) {
                        Storage::disk('public')->delete($purchase->receipt_image);
                    }
                    $path = $request->file('receipt_image')->store('receipts', 'public');
                    $purchase->update(['receipt_image' => $path]);
                }
            }

            return Expenditure::create($data);
        });

        return response()->json([
            'message' => 'Expenditure created',
            'data' => $expenditure->load('user', 'purchase'),
        ], 201);
    }

    public function show(Expenditure $expenditure)
    {
        return response()->json($expenditure->load('user', 'purchase'));
    }

    public function update(Request $request, Expenditure $expenditure)
    {
        // pastikan Anda punya policy jika ingin authorize; jika belum, hapus baris ini
        $this->authorize('update', $expenditure);

        $data = $request->validate([
            'description'   => 'required|string',
            'amount'        => 'required|numeric|min:0',
            'expense_date'  => 'required|date',
            'category'      => 'nullable|string',
            'purchase_id'   => 'nullable|exists:purchases,id',
            'receipt_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $data['purchase_id'] = $data['purchase_id'] ?: null;

        DB::transaction(function () use ($request, $data, $expenditure) {
            if ($request->hasFile('receipt_image') && !empty($data['purchase_id'])) {
                $purchase = Purchase::find($data['purchase_id']);
                if ($purchase) {
                    if ($purchase->receipt_image && Storage::disk('public')->exists($purchase->receipt_image)) {
                        Storage::disk('public')->delete($purchase->receipt_image);
                    }
                    $path = $request->file('receipt_image')->store('receipts', 'public');
                    $purchase->update(['receipt_image' => $path]);
                }
            }

            $expenditure->update($data);
        });

        return response()->json([
            'message' => 'Expenditure updated',
            'data' => $expenditure->fresh()->load('user', 'purchase'),
        ]);
    }

    public function destroy(Expenditure $expenditure)
    {
        $this->authorize('delete', $expenditure);
        $expenditure->delete();

        return response()->json(['message' => 'Expenditure deleted']);
    }
}