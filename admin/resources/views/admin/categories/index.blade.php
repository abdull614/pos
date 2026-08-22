<x-layouts.layout title="Kategori" pageTitle="Kategori" no-scroll>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-base sm:text-lg font-bold text-gray-900">Manajemen Kategori</h1>
            <p class="text-xs text-gray-400 mt-0.5">Kelola kategori produk toko Anda</p>
        </div>
        <x-button
            :href="route('categories.create')"
            icon="bi-plus-lg"
            label="Tambah Kategori"
            variant="primary"
        />
    </div>

    <x-widget::list-card
        :total="$categories->total()"
        search-placeholder="Cari kategori..."
        empty-text="Belum ada kategori"
        empty-icon="bi-tags"
        :is-empty="$categories->isEmpty()"
    >
        <x-slot:head>
            <th class="text-left px-3 sm:px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama</th>
            <th class="hidden sm:table-cell text-left px-3 sm:px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Deskripsi</th>
            <th class="text-right px-3 sm:px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
            <th class="w-28 text-center px-3 sm:px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
        </x-slot:head>

        @foreach ($categories as $category)
            <tr class="hover:bg-gray-50/70 transition">
                <td class="px-3 sm:px-5 py-3 sm:py-4">
                    <p class="font-semibold text-gray-900 text-sm">{{ $category->name }}</p>
                    {{-- Deskripsi muncul di bawah nama saat mobile --}}
                    <p class="sm:hidden text-xs text-gray-400 mt-0.5 truncate max-w-40">
                        {{ $category->description ?? '-' }}
                    </p>
                </td>
                <td class="hidden sm:table-cell px-3 sm:px-5 py-3 sm:py-4 text-sm text-gray-500">
                    {{ $category->description ?? '-' }}
                </td>
                {{-- Aksi --}}
                <td class="px-3 sm:px-5 py-3 sm:py-4">
                    <div class="flex items-center justify-end gap-1.5 sm:gap-2">
                        <x-button
                            :href="route('categories.edit', $category)"
                            icon="bi-pencil"
                            label="Edit"
                            variant="icon-warning"
                        />
                    </div>
                </td>
                {{-- Status: badge hijau/merah, klik untuk toggle --}}
                <td class="px-3 sm:px-5 py-3 sm:py-4 text-center">
                    <form action="{{ route('categories.toggle-active', $category) }}" method="POST"
                        onsubmit="return confirm('{{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }} kategori \'{{ $category->name }}\'?')">
                        @csrf @method('PATCH')
                        <button type="submit"
                            class="inline-block w-24 text-xs font-semibold px-3 py-1.5 rounded-full transition text-center whitespace-nowrap
                                {{ $category->is_active
                                    ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                    : 'bg-red-50 text-red-600 hover:bg-red-100' }}"
                            title="{{ $category->is_active ? 'Nonaktifkan kategori' : 'Aktifkan kategori' }}">
                            {{ $category->is_active ? 'Aktif' : 'Non-Aktif' }}
                        </button>
                    </form>
                </td>
            </tr>
        @endforeach

        <x-slot:pagination>
            {{ $categories->links() }}
        </x-slot:pagination>

    </x-widget::list-card>

</x-layouts.layout>