<x-layouts.layout title="Pengeluaran" pageTitle="Pengeluaran" no-scroll>

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-gray-900">Manajemen Pengeluaran</h1>
            <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">Kelola semua pengeluaran toko Anda</p>
        </div>
        <x-button
            :href="route('expenditures.create')"
            icon="bi-plus-lg"
            label="Tambah Pengeluaran"
            variant="primary"
        />
    </div>

    <x-widget::list-card
        :total="$expenditures->total()"
        search-placeholder="Cari pengeluaran..."
        empty-text="Belum ada pengeluaran"
        empty-icon="bi-wallet2"
        :is-empty="$expenditures->isEmpty()"
    >
        <x-slot:head>
            <th class="text-left px-3 sm:px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Deskripsi</th>
            <th class="hidden sm:table-cell text-left px-3 sm:px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kategori</th>
            <th class="text-left px-3 sm:px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Jumlah</th>
            <th class="hidden sm:table-cell text-left px-3 sm:px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
            <th class="hidden md:table-cell text-left px-3 sm:px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Dicatat Oleh</th>
            <th class="text-right px-3 sm:px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
        </x-slot:head>

        @foreach ($expenditures as $exp)
            <tr class="hover:bg-gray-50/70 transition">
                {{-- Deskripsi --}}
                <td class="px-3 sm:px-5 py-3 sm:py-4">
                    <a href="{{ route('expenditures.show', $exp) }}" class="font-semibold text-gray-900 text-sm hover:text-emerald-600">
                        {{ $exp->description }}
                    </a>
                    @if ($exp->purchase && $exp->purchase->items->count() > 0)
                        <p class="text-[11px] text-gray-400 mt-0.5">
                            {{ $exp->purchase->items->count() }} produk
                        </p>
                    @endif
                    <p class="sm:hidden text-[10px] font-medium text-gray-400 mt-0.5">{{ $exp->category ?? '-' }}</p>
                </td>
                {{-- Kategori: hidden di HP --}}
                <td class="hidden sm:table-cell px-3 sm:px-5 py-3 sm:py-4">
                    <span class="text-xs font-medium text-gray-600 bg-gray-100 px-2.5 py-1 rounded-full">
                        {{ $exp->category ?? '-' }}
                    </span>
                </td>
                {{-- Jumlah --}}
                <td class="px-3 sm:px-5 py-3 sm:py-4">
                    <span class="text-xs sm:text-sm font-semibold text-red-600">
                        - Rp {{ number_format($exp->amount, 0, ',', '.') }}
                    </span>
                </td>
                {{-- Tanggal: hidden di HP --}}
                <td class="hidden sm:table-cell px-3 sm:px-5 py-3 sm:py-4 text-sm text-gray-600">
                    {{ \Carbon\Carbon::parse($exp->expense_date)->format('d M Y') }}
                </td>
                {{-- Dicatat oleh: hidden di HP & tablet --}}
                <td class="hidden md:table-cell px-3 sm:px-5 py-3 sm:py-4 text-sm text-gray-600">
                    {{ $exp->user->name ?? '-' }}
                </td>
                {{-- Aksi --}}
                <td class="px-3 sm:px-5 py-3 sm:py-4">
                    <div class="flex items-center justify-end gap-1 sm:gap-2">
                        <x-button
                            :href="route('expenditures.show', $exp)"
                            icon="bi-eye"
                            label="Detail"
                            variant="icon-secondary"
                        />
                        <x-button
                            :href="route('expenditures.edit', $exp)"
                            icon="bi-pencil"
                            label="Edit"
                            variant="icon-warning"
                        />
                        <form action="{{ route('expenditures.destroy', $exp) }}" method="POST"
                            onsubmit="return confirm('Hapus pengeluaran ini?')">
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
            {{ $expenditures->links() }}
        </x-slot:pagination>

    </x-widget::list-card>

</x-layouts.layout>