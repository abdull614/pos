<x-layouts.layout title="Pelanggan" pageTitle="Pelanggan">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-gray-900">Manajemen Pelanggan</h1>
            <p class="text-xs text-gray-400 mt-0.5">Kelola data pelanggan toko Anda</p>
        </div>
        <x-button
            :href="route('customers.create')"
            icon="bi-plus-lg"
            label="Tambah Pelanggan"
            variant="primary"
        />
    </div>

    <x-widget::list-card
        :total="$customers->total()"
        search-placeholder="Cari pelanggan..."
        empty-text="Belum ada pelanggan"
        empty-icon="bi-people"
        :is-empty="$customers->isEmpty()"
    >
        <x-slot:head>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">No. HP</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tipe</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Utang</th>
            <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
        </x-slot:head>

        @foreach ($customers as $customer)
            <tr class="hover:bg-gray-50/70 transition">
                <td class="px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-emerald-50 rounded-full flex items-center justify-center shrink-0">
                            <span class="text-xs font-bold text-emerald-600">
                                {{ strtoupper(substr($customer->name, 0, 1)) }}
                            </span>
                        </div>
                        <p class="font-semibold text-gray-900">{{ $customer->name }}</p>
                    </div>
                </td>
                <td class="px-5 py-4 text-sm text-gray-500">
                    {{ $customer->phone ?? '-' }}
                </td>
                <td class="px-5 py-4">
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full
                        {{ $customer->customer_type === 'regular' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700' }}">
                        {{ ucfirst($customer->customer_type) }}
                    </span>
                </td>
                <td class="px-5 py-4">
                    @if ($customer->total_debt > 0)
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-red-50 text-red-600">
                            Rp {{ number_format($customer->total_debt, 0, ',', '.') }}
                        </span>
                    @else
                        <span class="text-xs text-gray-400">-</span>
                    @endif
                </td>
                <td class="px-5 py-4">
                    <div class="flex items-center justify-end gap-2">
                        <x-button
                            :href="route('customers.show', $customer)"
                            icon="bi-eye"
                            label="Detail"
                            variant="icon-emerald"
                        />
                        <x-button
                            :href="route('customers.edit', $customer)"
                            icon="bi-pencil"
                            label="Edit"
                            variant="icon-warning"
                        />
                        <form action="{{ route('customers.destroy', $customer) }}" method="POST"
                              onsubmit="return confirm('Hapus pelanggan ini?')">
                            @csrf @method('DELETE')
                            <x-button
                                type="submit"
                                icon="bi-trash"
                                label="Hapus"
                                variant="icon-danger"
                            />
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot:pagination>
            {{ $customers->links() }}
        </x-slot:pagination>

    </x-widget::list-card>

</x-layouts.layout>