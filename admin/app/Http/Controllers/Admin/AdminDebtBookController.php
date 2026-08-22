<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerReceivable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDebtBookController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');
        $search = $request->input('search');

        $query = CustomerReceivable::with(['customer', 'salesTransaction']);

        // FIX: sebelumnya tab "Semua" masih diam-diam difilter
        // `remaining > 0`, jadi debitur yang sudah lunas (remaining = 0)
        // tidak pernah muncul di tab ini. Sekarang tab "Semua" benar-benar
        // menampilkan semua data apa pun statusnya (belum bayar, sebagian,
        // lunas). Filter status hanya diterapkan kalau user memilih tab
        // spesifik (unpaid/partial/paid).
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', function ($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%");
                })->orWhereHas('salesTransaction', function ($qq) use ($search) {
                    $qq->where('invoice_number', 'like', "%{$search}%");
                });
            });
        }

        // FIX: kalau satu debitur (customer) punya beberapa catatan utang
        // (mis. beberapa transaksi/kasbon terpisah), sebelumnya masing-
        // masing tampil sebagai kartu sendiri-sendiri meski nama & data
        // customernya sama persis. Sekarang digabung jadi SATU kartu per
        // customer: total sisa utang, persentase terbayar, dan status
        // dihitung gabungan dari semua catatan miliknya.
        //
        // Karena pengelompokan ini butuh agregasi lintas baris, kita ambil
        // dulu semua baris yang lolos filter (belum dipaginate), lalu
        // dikelompokkan & diurutkan pakai Collection, baru dipaginasi
        // manual. Untuk skala data warung/toko ini masih ringan; kalau
        // datanya sudah sangat besar, pendekatan ini perlu dioptimasi jadi
        // agregasi di level query (groupBy + SUM di SQL).
        $allReceivables = $query->latest()->get();

        $grouped = $allReceivables
            ->groupBy('customer_id')
            ->map(function ($items) {
                $customer = $items->first()->customer;

                $totalAmount    = $items->sum('amount');
                $totalRemaining = $items->sum('remaining');
                $totalPaid      = $totalAmount - $totalRemaining;
                $percentPaid    = $totalAmount > 0 ? round(($totalPaid / $totalAmount) * 100) : 0;

                if ($totalRemaining <= 0) {
                    $overallStatus = 'paid';
                } elseif ($totalPaid > 0) {
                    $overallStatus = 'partial';
                } else {
                    $overallStatus = 'unpaid';
                }

                // Jatuh tempo yang dipakai untuk badge = due_date paling
                // lama di antara catatan utang yang masih ada sisanya.
                $nearestDue = $items->where('remaining', '>', 0)
                    ->sortBy('due_date')
                    ->first()?->due_date;

                return (object) [
                    'customer'        => $customer,
                    'receivables'     => $items->values(),
                    'total_amount'    => $totalAmount,
                    'total_remaining' => $totalRemaining,
                    'percent_paid'    => $percentPaid,
                    'status'          => $overallStatus,
                    'due_date'        => $nearestDue,
                    'latest_created'  => $items->max('created_at'),
                ];
            })
            // Lunas selalu paling bawah, sisanya diurutkan dari catatan
            // utang terbaru.
            ->sort(function ($a, $b) {
                $aPaid = $a->status === 'paid' ? 1 : 0;
                $bPaid = $b->status === 'paid' ? 1 : 0;

                if ($aPaid !== $bPaid) {
                    return $aPaid <=> $bPaid;
                }

                return $b->latest_created <=> $a->latest_created;
            })
            ->values();

        $perPage = 10;
        $page    = (int) $request->input('page', 1);
        $total   = $grouped->count();

        $receivables = new \Illuminate\Pagination\LengthAwarePaginator(
            $grouped->slice(($page - 1) * $perPage, $perPage)->values(),
            $total,
            $perPage,
            $page,
            [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]
        );

        if ($request->ajax()) {
            return view('admin.debt_book.receivables-list', compact('receivables', 'status'));
        }

        // FIX: sebelumnya 'total' dihitung dari sum('amount') SEMUA baris
        // termasuk yang statusnya sudah "paid" (lunas), dan memakai nilai
        // utang awal (amount) bukan sisa (remaining). Akibatnya angka
        // "Total Piutang Aktif" jauh lebih besar daripada penjumlahan sisa
        // utang yang sebenarnya masih outstanding di daftar debitur.
        // Sekarang 'total' = unpaid + partial (keduanya sudah pakai
        // remaining), jadi benar-benar mencerminkan piutang yang MASIH aktif.
        $unpaidTotal  = CustomerReceivable::where('status', 'unpaid')->sum('remaining');
        $partialTotal = CustomerReceivable::where('status', 'partial')->sum('remaining');

        $summary = [
            'total'   => $unpaidTotal + $partialTotal,
            'unpaid'  => $unpaidTotal,
            'partial' => $partialTotal,
        ];

        $totalDebitur = CustomerReceivable::where('remaining', '>', 0)
            ->distinct('customer_id')
            ->count('customer_id');

        return view('admin.debt_book.index', compact('receivables', 'summary', 'status', 'totalDebitur'));
    }

    public function riwayat(Request $request)
    {
        $status = $request->input('status', 'all');
        $search = $request->input('search');

        $query = CustomerReceivable::with(['customer', 'salesTransaction'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search, function ($q) use ($search) {
                $q->whereHas('customer', function ($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%");
                })->orWhereHas('salesTransaction', function ($qq) use ($search) {
                    $qq->where('invoice_number', 'like', "%{$search}%");
                });
            });

        // FIX: sama seperti index(), status "Lunas" ditaruh paling bawah
        // supaya konsisten.
        $receivables = $query
            ->orderByRaw("CASE WHEN status = 'paid' THEN 1 ELSE 0 END")
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.debt_book.riwayat', compact('receivables', 'status'));
    }

    public function show(Customer $customer)
    {
        // Eager-load item transaksi (produk yang dibeli) untuk ditampilkan
        // sebagai "Keterangan" di timeline detail utang.
        // NOTE: sesuaikan nama relasi 'saleItems' dan 'product' dengan
        // model SalesTransaction / SaleItem yang sebenarnya kalau berbeda.
        $receivables = CustomerReceivable::with(['salesTransaction.saleItems.product'])
            ->where('customer_id', $customer->id)
            ->latest()
            ->get();

        // ⚠️ FIX: eager-load relasi 'recordedBy' (user yang mencatat
        // pembayaran) supaya nama admin bisa ditampilkan di timeline,
        // tanpa memicu N+1 query per baris pembayaran.
        $paymentHistories = $customer->paymentHistories()
            ->with('recordedBy')
            ->latest('paid_at')
            ->get();

        return view('admin.debt_book.show', compact('customer', 'receivables', 'paymentHistories'));
    }

    public function create()
    {
        return view('admin.debt_book.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'address'  => ['nullable', 'string', 'max:500'],
            'amount'   => ['required', 'numeric', 'min:1'],
            'due_date' => ['required', 'date'],
            'notes'    => ['nullable', 'string', 'max:500'],
        ], [
            'name.required'     => 'Nama debitur wajib diisi.',
            'amount.required'   => 'Nominal utang wajib diisi.',
            'amount.min'        => 'Nominal utang harus lebih dari 0.',
            'due_date.required' => 'Tanggal jatuh tempo wajib diisi.',
        ]);

        $customer = DB::transaction(function () use ($validated) {
            $customer = Customer::create([
                'name'       => $validated['name'],
                'phone'      => $validated['phone'] ?? null,
                'address'    => $validated['address'] ?? null,
                'total_debt' => $validated['amount'],
            ]);

            CustomerReceivable::create([
                'customer_id'          => $customer->id,
                'sales_transaction_id' => null,
                'amount'               => $validated['amount'],
                'paid'                 => 0,
                'remaining'            => $validated['amount'],
                'due_date'             => $validated['due_date'],
                'status'               => 'unpaid',
                'notes'                => $validated['notes'] ?? null,
            ]);

            return $customer;
        });

        return redirect()
            ->route('debt-book.show', $customer->id)
            ->with('success', 'Debitur baru berhasil ditambahkan.');
    }
}