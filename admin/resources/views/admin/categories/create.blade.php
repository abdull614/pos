<x-layouts.layout title="Tambah Kategori" pageTitle="Kategori">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 mb-3">
        <a href="{{ route('dashboard') }}" class="hover:text-gray-600 transition-colors">Dashboard</a>
        <i class="bi bi-chevron-right text-[10px]"></i>
        <a href="{{ route('categories.index') }}" class="hover:text-gray-600 transition-colors">Kategori</a>
        <i class="bi bi-chevron-right text-[10px]"></i>
        <span class="text-gray-700 font-medium">Tambah Kategori</span>
    </nav>

    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('categories.index') }}"
           class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition shadow-sm shrink-0">
            <i class="bi bi-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-lg sm:text-xl font-extrabold text-gray-900">Tambah Kategori</h1>
            <p class="text-xs text-gray-400 mt-0.5">Buat kelompok produk baru untuk mempermudah pencarian di kasir</p>
        </div>
    </div>

    <div class="w-full space-y-4">

        <!-- Card Form -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

            <!-- Card Header -->
            <div class="px-5 sm:px-6 py-5 border-b border-gray-100 flex items-center gap-3 bg-gradient-to-r from-emerald-50/60 to-transparent">
                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center shrink-0">
                    <i class="bi bi-tags-fill text-emerald-600 text-base"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-gray-900">Informasi Kategori</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Nama akan tampil di daftar produk & filter kasir</p>
                </div>
            </div>

            <form action="{{ route('categories.store') }}" method="POST" class="p-5 sm:p-6 space-y-5">
                @csrf

                <x-form-input
                    label="Nama Kategori"
                    name="name"
                    placeholder="cth: Minuman, Makanan..."
                    :required="true"
                />

                <x-form-textarea
                    label="Deskripsi"
                    name="description"
                    placeholder="Deskripsi singkat kategori..."
                    :optional="true"
                />

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 pt-4 border-t border-gray-100">
                    <x-button type="submit" icon="bi-check-circle" label="Simpan Kategori" variant="primary" class="flex-1 justify-center" />
                    <x-button :href="route('categories.index')" icon="bi-x-circle" label="Batal" variant="secondary" class="justify-center" />
                </div>
            </form>

        </div>

        <!-- Tips Info -->
        <div class="bg-emerald-50/60 border border-emerald-100 rounded-2xl p-4 flex items-start gap-3">
            <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                <i class="bi bi-lightbulb-fill text-emerald-600 text-sm"></i>
            </div>
            <p class="text-xs text-emerald-800 leading-relaxed">
                Gunakan nama kategori yang singkat dan konsisten (mis. "Minuman", bukan "minuman-dingin-botol") agar mudah dicari saat menambah produk baru.
            </p>
        </div>

    </div>

</x-layouts.layout>