<x-layouts.layout title="Produk" pageTitle="Produk" no-scroll>

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-gray-900">Manajemen Produk</h1>
            <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">Kelola semua produk toko Anda</p>
        </div>
        <x-button
            :href="route('products.create')"
            icon="bi-plus-lg"
            label="Tambah Produk"
            variant="primary"
        />
    </div>

    <!-- Flash Messages -->
    @if (session('success'))
        <div class="mb-4 p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-2.5 text-emerald-800 text-sm">
            <i class="bi bi-check-circle-fill text-emerald-500"></i>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 p-3.5 bg-red-50 border border-red-200 rounded-xl flex items-center gap-2.5 text-red-800 text-sm">
            <i class="bi bi-exclamation-triangle-fill text-red-500"></i>
            {{ session('error') }}
        </div>
    @endif

    <x-widget::list-card
        :total="$products->total()"
        search-placeholder="Cari produk..."
        empty-text="Belum ada produk"
        empty-icon="bi-box"
        :is-empty="$products->isEmpty()"
    >
            <x-slot:head>
                {{-- Sembunyikan kolom SKU & Kategori di HP --}}
                <th class="hidden sm:table-cell text-left px-3 sm:px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">SKU</th>
                <th class="text-left px-3 sm:px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Produk</th>
                <th class="hidden md:table-cell text-left px-3 sm:px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kategori</th>
                <th class="hidden sm:table-cell text-left px-3 sm:px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Harga Jual</th>
                <th class="text-left px-3 sm:px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Stok</th>
                <th class="text-right px-3 sm:px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                <th class="w-28 text-center px-3 sm:px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
            </x-slot:head>

            @foreach ($products as $product)
                <tr class="hover:bg-gray-50/70 transition {{ !$product->is_active ? 'opacity-50' : '' }}">
                    {{-- SKU: hidden di HP --}}
                    <td class="hidden sm:table-cell px-3 sm:px-5 py-3 sm:py-4">
                        <span class="font-mono text-xs font-semibold text-gray-500 bg-gray-100 px-2 py-1 rounded-lg">
                            {{ $product->sku }}
                        </span>
                    </td>
                    {{-- Nama --}}
                    <td class="px-3 sm:px-5 py-3 sm:py-4">
                        <p class="font-semibold text-gray-900 text-sm">{{ $product->name }}</p>
                        {{-- Tampilkan SKU di HP sebagai subtitle --}}
                        <p class="sm:hidden text-[10px] font-mono text-gray-400 mt-0.5">{{ $product->sku }}</p>
                    </td>
                    {{-- Kategori: hidden di HP --}}
                    <td class="hidden md:table-cell px-3 sm:px-5 py-3 sm:py-4">
                        <span class="text-xs font-medium text-gray-600 bg-gray-100 px-2.5 py-1 rounded-full">
                            {{ $product->category->name }}
                        </span>
                    </td>
                    {{-- Harga: hidden di HP --}}
                    <td class="hidden sm:table-cell px-3 sm:px-5 py-3 sm:py-4 font-semibold text-gray-900 text-sm">
                        Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                    </td>
                    {{-- Stok --}}
                    <td class="px-3 sm:px-5 py-3 sm:py-4">
                        @php $isLow = $product->stock <= $product->min_stock; @endphp
                        <span class="text-xs font-semibold px-2 sm:px-2.5 py-1 rounded-full
                            {{ $isLow ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">
                            {{ $product->stock }} {{ $product->unit }}
                        </span>
                    </td>
                    {{-- Aksi --}}
                    <td class="px-3 sm:px-5 py-3 sm:py-4">
                        <div class="flex items-center justify-end gap-2 sm:gap-3">
                            <x-button
                                :href="route('products.edit', $product)"
                                icon="bi-pencil"
                                label="Edit"
                                variant="icon-warning"
                            />
                        </div>
                    </td>
                    {{-- Status: badge hijau/merah, klik untuk toggle --}}
                    <td class="px-3 sm:px-5 py-3 sm:py-4 text-center">
                        <form action="{{ route('products.toggle-active', $product) }}" method="POST"
                            onsubmit="return confirm('{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }} produk \'{{ $product->name }}\'?')">
                            @csrf @method('PATCH')
                            <button type="submit"
                                class="inline-block w-24 text-xs font-semibold px-3 py-1.5 rounded-full transition text-center
                                    {{ $product->is_active
                                        ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                        : 'bg-red-50 text-red-700 hover:bg-red-100' }}"
                                title="{{ $product->is_active ? 'Nonaktifkan produk' : 'Aktifkan produk' }}">
                                {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach

            <x-slot:pagination>
                {{ $products->links() }}
            </x-slot:pagination>

        </x-widget::list-card>

</x-layouts.layout>