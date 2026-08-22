<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        }

        if ($request->has('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }

        if ($request->boolean('with_debt')) {
            $query->where('total_debt', '>', 0);
        }

        return response()->json($query->paginate(15));
    }

    public function show(Customer $customer)
    {
        return response()->json($customer->load([
            'receivables',
            'paymentHistories',
            'salesTransactions',
        ]));
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

        $customer = Customer::create($validated);

        return response()->json($customer, 201);
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

        return response()->json($customer);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->json(['message' => 'Customer deleted']);
    }

    public function getDebtors()
    {
        $debtors = Customer::where('total_debt', '>', 0)
                          ->orderBy('total_debt', 'desc')
                          ->get();

        return response()->json($debtors);
    }
}
