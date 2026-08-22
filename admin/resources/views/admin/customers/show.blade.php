<x-layouts.layout title="Detail Pelanggan" pageTitle="Pelanggan">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('customers.index') }}"
               class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 transition shadow-sm">
                <i class="bi bi-arrow-left text-sm"></i>
            </a>
            <div>
                <h1 class="text-lg font-bold text-gray-900">Detail Pelanggan</h1>
                <p class="text-xs text-gray-400 mt-0.5">Informasi lengkap pelanggan</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <x-button :href="route('customers.edit', $customer)" icon="bi-pencil" label="Edit" variant="primary" />
            <form action="{{ route('customers.destroy', $customer) }}" method="POST"
                  onsubmit="return confirm('Hapus pelanggan ini?')">
                @csrf @method('DELETE')
                <x-button type="submit" icon="bi-trash" label="Hapus" variant="secondary" />
            </form>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-6">

        <!-- Kolom Kiri: Info Pelanggan -->
        <div class="col-span-1 space-y-4">

            <!-- Info Card -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2.5">
                    <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center">
                        <i class="bi bi-person text-emerald-600 text-sm"></i>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800">Informasi Pelanggan</h3>
                </div>

                <div class="p-5 space-y-3">
                    <!-- Avatar & Nama -->
                    <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                        <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center shrink-0">
                            <span class="text-lg font-bold text-emerald-600">
                                {{ strtoupper(substr($customer->name, 0, 1)) }}
                            </span>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900">{{ $customer->name }}</p>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                                {{ $customer->customer_type === 'regular' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700' }}">
                                {{ ucfirst($customer->customer_type) }}
                            </span>
                        </div>
                    </div>

                    <!-- Detail -->
                    <div class="space-y-2.5">
                        <div class="flex items-center gap-2.5">
                            <i class="bi bi-telephone text-gray-400 text-sm w-4"></i>
                            <span class="text-sm text-gray-600">{{ $customer->phone ?? '-' }}</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <i class="bi bi-envelope text-gray-400 text-sm w-4"></i>
                            <span class="text-sm text-gray-600">{{ $customer->email ?? '-' }}</span>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <i class="bi bi-geo-alt text-gray-400 text-sm w-4 mt-0.5"></i>
                            <span class="text-sm text-gray-600">{{ $customer->address ?? '-' }}</span>
                        </div>
                    </div>

                    <!-- Total Utang -->
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">Total Utang</p>
                        <p class="text-xl font-bold {{ $customer->total_debt > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                            Rp {{ number_format($customer->total_debt, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Kolom Kanan: Riwayat -->
        <div class="col-span-2 space-y-4">

            <!-- Riwayat Utang -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2.5">
                    <div class="w-8 h-8 bg-red-50 rounded-lg flex items-center justify-center">
                        <i class="bi bi-journal-text text-red-500 text-sm"></i>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800">Riwayat Utang</h3>
                </div>

                @if ($receivables->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100">
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Invoice</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Jumlah</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Dibayar</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Sisa</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach ($receivables as $receivable)
                                    <tr class="hover:bg-gray-50/70 transition">
                                        <td class="px-5 py-3">
                                            <span class="font-mono text-xs font-semibold text-gray-500 bg-gray-100 px-2 py-1 rounded-lg">
                                                {{ $receivable->salesTransaction->invoice_number }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-sm text-gray-700">
                                            Rp {{ number_format($receivable->amount, 0, ',', '.') }}
                                        </td>
                                        <td class="px-5 py-3 text-sm text-emerald-600 font-semibold">
                                            Rp {{ number_format($receivable->paid, 0, ',', '.') }}
                                        </td>
                                        <td class="px-5 py-3 text-sm text-red-600 font-semibold">
                                            Rp {{ number_format($receivable->remaining, 0, ',', '.') }}
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full
                                                {{ $receivable->status === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                                {{ ucfirst($receivable->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12">
                        <i class="bi bi-journal-text text-gray-300 text-3xl block mb-2"></i>
                        <p class="text-gray-400 text-sm">Belum ada utang</p>
                    </div>
                @endif
            </div>

            <!-- Riwayat Pembayaran -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2.5">
                    <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center">
                        <i class="bi bi-cash-stack text-emerald-600 text-sm"></i>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800">Riwayat Pembayaran</h3>
                </div>

                @if ($paymentHistories->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100">
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Jumlah</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Metode</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Pencatat</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach ($paymentHistories as $payment)
                                    <tr class="hover:bg-gray-50/70 transition">
                                        <td class="px-5 py-3 text-sm text-gray-500">
                                            {{ $payment->paid_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-5 py-3 font-semibold text-emerald-600">
                                            Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-50 text-blue-700">
                                                {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-sm text-gray-600">
                                            {{ $payment->recordedBy->name }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12">
                        <i class="bi bi-cash-stack text-gray-300 text-3xl block mb-2"></i>
                        <p class="text-gray-400 text-sm">Belum ada pembayaran</p>
                    </div>
                @endif
            </div>

        </div>
    </div>

</x-layouts.layout>