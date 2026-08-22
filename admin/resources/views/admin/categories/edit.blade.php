<x-layouts.layout title="Edit Kategori" pageTitle="Kategori">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 mb-3">
        <a href="{{ route('dashboard') }}" class="hover:text-gray-600 transition-colors">Dashboard</a>
        <i class="bi bi-chevron-right text-[10px]"></i>
        <a href="{{ route('categories.index') }}" class="hover:text-gray-600 transition-colors">Kategori</a>
        <i class="bi bi-chevron-right text-[10px]"></i>
        <span class="text-gray-700 font-medium truncate max-w-[160px]">{{ $category->name }}</span>
    </nav>

    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('categories.index') }}"
           class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition shadow-sm shrink-0">
            <i class="bi bi-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-lg sm:text-xl font-extrabold text-gray-900">Edit Kategori</h1>
            <p class="text-xs text-gray-400 mt-0.5">Perbarui informasi kategori</p>
        </div>
    </div>

    <div class="w-full">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

            <!-- Card Header -->
            <div class="px-5 sm:px-6 py-5 border-b border-gray-100 flex items-center gap-3 bg-gradient-to-r from-amber-50/60 to-transparent">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center shrink-0">
                    <i class="bi bi-pencil-square text-amber-600 text-base"></i>
                </div>
                <div class="min-w-0">
                    <h2 class="text-sm font-bold text-gray-900 truncate">{{ $category->name }}</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Dibuat {{ $category->created_at?->translatedFormat('d M Y') ?? '-' }}</p>
                </div>
            </div>

            <form action="{{ route('categories.update', $category) }}" method="POST" class="p-5 sm:p-6 space-y-5">
                @csrf
                @method('PUT')

                <x-form-input
                    label="Nama Kategori"
                    name="name"
                    :value="$category->name"
                    :required="true"
                />

                <x-form-textarea
                    label="Deskripsi"
                    name="description"
                    :value="$category->description"
                    :optional="true"
                />

                {{-- Toggle Aktif --}}
                <div x-data="{ active: {{ old('is_active', $category->is_active) ? 'true' : 'false' }} }"
                     class="flex items-center justify-between p-4 rounded-xl border transition-colors"
                     :class="active ? 'bg-emerald-50/60 border-emerald-100' : 'bg-gray-50 border-gray-200'">
                    <div class="pr-4">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold text-gray-700">Status Kategori</p>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                  :class="active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600'"
                                  x-text="active ? 'AKTIF' : 'NON-AKTIF'"></span>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Kategori non-aktif tidak muncul di kasir</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="sr-only peer"
                               x-model="active"
                               @checked(old('is_active', $category->is_active))>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-500 rounded-full peer
                            peer-checked:after:translate-x-full peer-checked:after:border-white
                            after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                            after:bg-white after:border-gray-300 after:border after:rounded-full
                            after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 pt-4 border-t border-gray-100">
                    <x-button type="submit" icon="bi-check-circle" label="Simpan Perubahan" variant="primary" class="flex-1 justify-center" />
                    <x-button :href="route('categories.index')" icon="bi-x-circle" label="Batal" variant="secondary" class="justify-center" />
                </div>

            </form>
        </div>
    </div>

</x-layouts.layout>