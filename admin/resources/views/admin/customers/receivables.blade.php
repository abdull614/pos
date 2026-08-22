<x-layouts.layout title="Buku Utang" pageTitle="Buku Utang">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-gray-900">Buku Utang</h1>
            <p class="text-xs text-gray-400 mt-0.5">Riwayat utang seluruh pelanggan</p>
        </div>
    </div>

    <x-widget::list-card
        :total="$receivables->total()"
        search-placeholder="Cari pelanggan atau invoice..."
        empty-text="Belum ada data utang"
        empty-icon="bi-journal-text"
        :is-empty="$receivables->isEmpty()"
    >
        <x-slot:head>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Pelanggan</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Invoice</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Jumlah</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Dibayar</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Sisa</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
            <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
        </x-slot:head>

        @foreach ($receivables as $receivable)
            <tr class="hover:bg-gray-50/70 transition">
                <td class="px-5 py-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 bg-emerald-50 rounded-full flex items-center justify-center shrink-0">
                            <span class="text-xs font-bold text-emerald-600">
                                {{ strtoupper(substr($receivable->customer->name, 0, 1)) }}
                            </span>
                        </div>
                        <p class="font-semibold text-gray-900 text-sm">{{ $receivable->customer->name }}</p>
                    </div>
                </td>
                <td class="px-5 py-4">
                    <span class="font-mono text-xs font-semibold text-gray-500 bg-gray-100 px-2 py-1 rounded-lg">
                        {{ $receivable->salesTransaction->invoice_number }}
                    </span>
                </td>
                <td class="px-5 py-4 text-sm text-gray-700 font-medium">
                    Rp {{ number_format($receivable->amount, 0, ',', '.') }}
                </td>
                <td class="px-5 py-4 text-sm text-emerald-600 font-semibold">
                    Rp {{ number_format($receivable->paid, 0, ',', '.') }}
                </td>
                <td class="px-5 py-4 text-sm text-red-600 font-semibold">
                    Rp {{ number_format($receivable->remaining, 0, ',', '.') }}
                </td>
                <td class="px-5 py-4">
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full
                        {{ $receivable->status === 'paid'
                            ? 'bg-emerald-50 text-emerald-700'
                            : 'bg-amber-50 text-amber-700' }}">
                        {{ ucfirst($receivable->status) }}
                    </span>
                </td>
                <td class="px-5 py-4">
                    <div class="flex items-center justify-end">
                        <x-button
                            :href="route('customers.show', $receivable->customer)"
                            icon="bi-eye"
                            label="Detail"
                            variant="icon-emerald"
                        />
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot:pagination>
            {{ $receivables->links() }}
        </x-slot:pagination>

    </x-widget::list-card>

</x-layouts.layout>