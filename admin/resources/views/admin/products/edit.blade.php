<x-layouts.layout title="Edit Produk" pageTitle="Produk">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('products.index') }}"
           class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:-translate-x-0.5 active:scale-95 transition-all duration-150 shadow-sm shrink-0">
            <i class="bi bi-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 tracking-tight">Edit Produk</h1>
            <p class="text-xs text-gray-400 mt-0.5">Perbarui informasi produk</p>
        </div>
    </div>

    <div class="w-full">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow duration-300 overflow-hidden">

            <div class="px-5 sm:px-8 py-5 border-b border-gray-100 bg-linear-to-r from-amber-50/60 via-white to-white flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 bg-linear-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center shrink-0 shadow-sm shadow-amber-200">
                        <i class="bi bi-pencil-square text-white text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-gray-800 truncate">{{ $product->name }}</h2>
                        <p class="text-[11px] font-mono text-gray-400">{{ $product->sku }}</p>
                    </div>
                </div>
                @if ($product->stock <= $product->min_stock)
                    <span class="text-[10px] font-bold text-red-600 bg-red-50 px-2.5 py-1 rounded-full flex items-center gap-1 shrink-0">
                        <i class="bi bi-exclamation-circle"></i>
                        <span class="hidden sm:inline">Stok Rendah</span>
                    </span>
                @endif
            </div>

            <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data" class="p-5 sm:p-8 space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <x-form-input label="SKU" name="sku" :value="$product->sku" :required="true" />
                    <x-form-input label="Nama Produk" name="name" :value="$product->name" :required="true" />
                    <x-form-select
                        label="Kategori"
                        name="category_id"
                        :options="$categories->toArray()"
                        placeholder="-- Pilih Kategori --"
                        :required="true"
                        :value="$product->category_id"
                    />
                </div>

                <x-form-textarea
                    label="Deskripsi"
                    name="description"
                    :value="$product->description"
                    :optional="true"
                />

                {{-- Gambar Produk --}}
                <x-form-section title="Gambar Produk">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-2">
                            Foto Produk <span class="text-gray-400 font-normal">(opsional)</span>
                        </label>
                        <div class="flex items-center gap-4">
                            <div id="preview-wrapper" class="{{ $product->image_url ? '' : 'hidden' }} w-20 h-20 rounded-xl overflow-hidden border-2 border-amber-100 shrink-0 shadow-sm relative">
                                <img id="preview-image" src="{{ $product->image_url }}" alt="Preview" class="w-full h-full object-cover">
                            </div>
                            <label for="image" class="flex-1 max-w-md group cursor-pointer">
                                <div class="border-2 border-dashed border-gray-200 rounded-xl px-4 py-3.5 flex items-center gap-3 group-hover:border-amber-300 group-hover:bg-amber-50/40 transition-all duration-200">
                                    <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-amber-100 flex items-center justify-center shrink-0 transition-colors">
                                        <i class="bi bi-cloud-arrow-up text-gray-400 group-hover:text-amber-600 transition-colors"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-gray-600 group-hover:text-amber-700 transition-colors">Klik untuk ganti foto</p>
                                        <p class="text-[11px] text-gray-400">JPG, PNG, atau WEBP · Maks 2MB</p>
                                    </div>
                                </div>
                                <input
                                    type="file"
                                    name="image"
                                    id="image"
                                    accept="image/png, image/jpeg, image/jpg, image/webp"
                                    onchange="previewGambar(event)"
                                    class="hidden"
                                >
                            </label>
                        </div>
                        @error('image')
                            <p class="text-xs text-red-500 mt-2 flex items-center gap-1">
                                <i class="bi bi-exclamation-circle"></i> {{ $message }}
                            </p>
                        @enderror

                        @if ($product->image_url)
                            <label class="inline-flex items-center gap-1.5 mt-3 text-xs text-red-500 cursor-pointer hover:text-red-600 transition-colors">
                                <input type="checkbox" name="remove_image" value="1" class="rounded border-gray-300 text-red-500 focus:ring-red-400">
                                Hapus gambar saat ini
                            </label>
                        @endif
                    </div>
                </x-form-section>

                <x-form-section title="Harga">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <x-form-input label="Harga Beli" name="purchase_price" type="number" :value="$product->purchase_price" prefix="Rp" :required="true" />
                        <x-form-input label="Harga Jual" name="selling_price" type="number" :value="$product->selling_price" prefix="Rp" :required="true" />
                    </div>
                </x-form-section>

                <x-form-section title="Stok & Satuan">
                    <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                        <x-form-input label="Stok" name="stock" type="number" :value="$product->stock" :required="true" />
                        <x-form-input label="Min. Stok" name="min_stock" type="number" :value="$product->min_stock" />
                        <x-form-input label="Satuan" name="unit" :value="$product->unit" :required="true" />
                    </div>
                </x-form-section>

                <!-- Sticky action bar -->
                <div class="sticky bottom-0 -mx-5 sm:-mx-8 -mb-5 sm:-mb-8 px-5 sm:px-8 py-4 bg-white/90 backdrop-blur-sm border-t border-gray-100 flex flex-wrap items-center gap-3">
                    <x-button type="submit" icon="bi-check-circle" label="Simpan Perubahan" variant="primary" />
                    <x-button :href="route('products.index')" icon="bi-x-circle" label="Batal" variant="secondary" />
                </div>

            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function previewGambar(event) {
            const file = event.target.files[0];
            const wrapper = document.getElementById('preview-wrapper');
            const preview = document.getElementById('preview-image');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    wrapper.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        }

        // Kalau user centang "Hapus gambar", nonaktifkan input file (opsional, biar tidak konflik)
        const removeImageCheckbox = document.querySelector('input[name="remove_image"]');
        const imageInput = document.getElementById('image');
        if (removeImageCheckbox && imageInput) {
            removeImageCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    imageInput.value = '';
                    document.getElementById('preview-wrapper').classList.add('hidden');
                    imageInput.disabled = true;
                } else {
                    imageInput.disabled = false;
                }
            });
        }
    </script>
    @endpush
</x-layouts.layout>