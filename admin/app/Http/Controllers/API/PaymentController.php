<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CustomerReceivable;
use App\Models\PaymentHistory;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function getReceivables(Request $request)
    {
        $query = CustomerReceivable::with([
            'customer',
            'salesTransaction.saleItems.product',
            'paymentHistories',
        ]);

        if ($request->has('customer_receivable_id')) {
            $query->where('id', $request->customer_receivable_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        return response()->json($query->paginate(15));
    }

    public function showReceivable(CustomerReceivable $receivable)
    {
        return response()->json(
            $receivable->load([
                'customer',
                'salesTransaction.saleItems.product',
                'paymentHistories',
            ])
        );
    }

    public function recordPayment(Request $request)
    {
        $validated = $request->validate([
            'customer_receivable_id' => 'required|exists:customer_receivables,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,check,other,qris',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
            'paid_at' => 'nullable|date',
            // Bukti pembayaran: opsional, maksimal 5MB, harus berupa gambar.
            // Dikirim dari Flutter via ApiService.postMultipart(...,
            // fileFieldName: 'proof_of_payment'), jadi nama field ini HARUS
            // persis sama dengan yang dipakai di sisi Flutter.
            'proof_of_payment' => 'nullable|image|max:5120',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $receivable = CustomerReceivable::find($validated['customer_receivable_id']);

            if (!$receivable) {
                return response()->json([
                    'error' => 'Receivable not found',
                ], 404);
            }

            if ($validated['amount'] > $receivable->remaining) {
                return response()->json([
                    'error' => 'Payment amount exceeds remaining debt',
                ], 422);
            }

            // Simpan file bukti pembayaran (kalau ada) ke
            // storage/app/public/payment_proofs, lalu simpan PATH relatifnya
            // saja ke database (bukan URL lengkap -- URL lengkap dibentuk
            // otomatis lewat accessor proof_of_payment_url di model).
            $proofPath = null;
            if ($request->hasFile('proof_of_payment')) {
                $proofPath = $request->file('proof_of_payment')->store('payment_proofs', 'public');
            }

            $paymentHistory = PaymentHistory::create([
                'customer_receivable_id' => $receivable->id,
                'customer_id' => $receivable->customer_id,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'proof_of_payment' => $proofPath,
                'recorded_by' => Auth::id(),
                'paid_at' => $validated['paid_at'] ?? now(),
            ]);

            $receivable->increment('paid', $validated['amount']);
            $receivable->decrement('remaining', $validated['amount']);

            if ($receivable->remaining <= 0) {
                $receivable->update(['status' => 'paid']);
            } else {
                $receivable->update(['status' => 'partial']);
            }

            $customer = $receivable->customer;
            $customer->decrement('total_debt', $validated['amount']);

            return response()->json([
                'message' => 'Payment recorded successfully',
                'payment' => $paymentHistory->load('customerReceivable', 'customer'),
            ], 201);
        });
    }

    public function getPaymentHistory(Request $request)
    {
        $query = PaymentHistory::with(['customer', 'customerReceivable', 'recordedBy']);

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('paid_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('paid_at', '<=', $request->date_to);
        }

        return response()->json($query->latest('paid_at')->paginate(15));
    }
}