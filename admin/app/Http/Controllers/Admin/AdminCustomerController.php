<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;

use App\Models\Customer;
use App\Models\CustomerReceivable;
use App\Models\PaymentHistory;
use Illuminate\Http\Request;

class AdminCustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::paginate(15);
        return view('admin.customers.index', compact('customers'));
    }

    public function show(Customer $customer)
    {
        $receivables = $customer->receivables()->with('salesTransaction')->get();
        $paymentHistories = $customer->paymentHistories()->latest()->get();

        return view('admin.customers.show', compact('customer', 'receivables', 'paymentHistories'));
    }

    public function create()
    {
        return view('admin.customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'email' => 'nullable|email',
            'customer_type' => 'required|in:regular,reseller',
        ]);

        Customer::create($validated);

        return redirect('/admin/customers')->with('success', 'Pelanggan berhasil ditambahkan');
    }

    public function edit(Customer $customer)
    {
        return view('admin.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'email' => 'nullable|email',
            'customer_type' => 'required|in:regular,reseller',
        ]);

        $customer->update($validated);

        return redirect('/admin/customers')->with('success', 'Pelanggan berhasil diperbarui');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect('/admin/customers')->with('success', 'Pelanggan berhasil dihapus');
    }

    public function receivables(Customer $customer)
    {
        $receivables = $customer->receivables()->with('salesTransaction')->paginate(10);
        return view('admin.customers.receivables', compact('customer', 'receivables'));
    }
}
