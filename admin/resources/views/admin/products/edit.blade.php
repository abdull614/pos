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
                                <input type="checkbox" name="remove_image" value="1" id="remove_image_checkbox" class="rounded border-gray-300 text-red-500 focus:ring-red-400">
                                Hapus gambar saat ini
                            </label>
                        @endif
                    </div>
                </x-form-section>

                {{-- Harga Utama (gabungan harga dasar + harga per unit) --}}
                <x-form-section title="Harga Utama">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-1">
                        <x-form-input label="Harga Beli" name="purchase_price" type="number" :value="$product->purchase_price" prefix="Rp" :required="true" />
                    </div>

                    <div id="prices-wrapper" class="space-y-3 mt-4">
                        @if($product->prices && $product->prices->count())
                            @foreach($product->prices as $i => $price)
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 price-row {{ $i > 0 ? 'items-end' : '' }}">
                                    <x-form-input label="Satuan" name="prices[{{ $i }}][unit]" :value="$price->unit" placeholder="pcs, renceng, kg" :required="$i === 0" />
                                    <x-form-input label="Harga Beli" name="prices[{{ $i }}][purchase_price]" type="number" prefix="Rp" :value="$price->purchase_price" />
                                    @if($i === 0)
                                        <x-form-input label="Harga Jual" name="prices[{{ $i }}][selling_price]" type="number" prefix="Rp" :value="$price->selling_price" :required="true" />
                                    @else
                                        <div class="flex gap-2 items-end">
                                            <div class="flex-1">
                                                <x-form-input label="Harga Jual" name="prices[{{ $i }}][selling_price]" type="number" prefix="Rp" :value="$price->selling_price" />
                                            </div>
                                            <button type="button" onclick="removePriceRow(this)"
                                                    class="mb-0.5 w-10 h-10 flex items-center justify-center rounded-xl bg-red-50 text-red-500 hover:bg-red-100 transition shrink-0">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 price-row">
                                <x-form-input label="Satuan" name="prices[0][unit]" placeholder="pcs, renceng, kg" :required="true" />
                                <x-form-input label="Harga Beli" name="prices[0][purchase_price]" type="number" prefix="Rp" placeholder="0" />
                                <x-form-input label="Harga Jual" name="prices[0][selling_price]" type="number" prefix="Rp" placeholder="0" :required="true" />
                            </div>
                        @endif
                    </div>

                    <button type="button" onclick="addPriceRow()"
                            class="mt-3 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-amber-50 text-amber-600 text-xs font-semibold hover:bg-amber-100 transition">
                        <i class="bi bi-plus-lg"></i> Tambah Harga Unit
                    </button>
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
        // === Preview Gambar ===
        function previewGambar(event) {
            const file = event.target.files && event.target.files[0];
            const wrapper = document.getElementById('preview-wrapper');
            const preview = document.getElementById('preview-image');

            if (file && preview && wrapper) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    wrapper.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        }

        // === Remove image checkbox handling ===
        (function() {
            const removeImageCheckbox = document.getElementById('remove_image_checkbox');
            const imageInput = document.getElementById('image');
            if (removeImageCheckbox && imageInput) {
                removeImageCheckbox.addEventListener('change', function() {
                    const wrapper = document.getElementById('preview-wrapper');
                    if (this.checked) {
                        imageInput.value = '';
                        if (wrapper) wrapper.classList.add('hidden');
                        imageInput.disabled = true;
                    } else {
                        imageInput.disabled = false;
                    }
                });
            }
        })();

        // === Harga Per Unit Dinamis (clone baris pertama biar tampilan identik) ===
        let priceIndex = {{ $product->prices && $product->prices->count() ? $product->prices->count() : 1 }};

        function addPriceRow() {
            const wrapper = document.getElementById('prices-wrapper');
            const firstRow = wrapper.querySelector('.price-row');
            if (!wrapper || !firstRow) return;

            const newRow = firstRow.cloneNode(true);

            // Kosongkan value & ganti index nama field
            newRow.querySelectorAll('input').forEach(input => {
                input.value = '';
                input.name = input.name.replace(/prices\[\d+\]/, `prices[${priceIndex}]`);
                input.removeAttribute('required'); // hanya baris pertama yang wajib
                input.id = '';
            });

            // Hapus tanda bintang wajib (*) yang ikut ter-clone dari baris pertama
            newRow.querySelectorAll('.text-red-500').forEach(el => {
                if (el.textContent.trim() === '*') el.remove();
            });

            // Hapus tombol hapus lama (kalau ikut ter-clone), lalu pasang yang baru + fungsional
            newRow.querySelectorAll('button').forEach(btn => btn.remove());

            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.onclick = function () { removePriceRow(this); };
            deleteBtn.className = 'mb-0.5 w-10 h-10 flex items-center justify-center rounded-xl bg-red-50 text-red-500 hover:bg-red-100 transition shrink-0 self-end';
            deleteBtn.innerHTML = '<i class="bi bi-trash3"></i>';

            newRow.classList.add('items-end');
            newRow.appendChild(deleteBtn);

            wrapper.appendChild(newRow);
            priceIndex++;
        }

        function removePriceRow(btn) {
            const row = btn.closest('.price-row');
            if (row) row.remove();
        }

        // === Validasi dasar sebelum submit ===
        (function attachFormValidation() {
            const form = document.querySelector('form[action][method="POST"]');
            if (!form) return;
            form.addEventListener('submit', function(e) {
                const wrapper = document.getElementById('prices-wrapper');
                if (!wrapper) return;
                const rows = wrapper.querySelectorAll('.price-row');
                for (const row of rows) {
                    const unitInput = row.querySelector('input[name$="[unit]"]');
                    const buyInput = row.querySelector('input[name$="[purchase_price]"]');
                    const sellInput = row.querySelector('input[name$="[selling_price]"]');
                    const allEmpty = (!unitInput || !unitInput.value.trim()) &&
                                     (!buyInput || !buyInput.value.trim()) &&
                                     (!sellInput || !sellInput.value.trim());
                    if (allEmpty) continue;
                    if (unitInput && !unitInput.value.trim()) {
                        e.preventDefault();
                        unitInput.focus();
                        alert('Isi satuan untuk setiap baris harga yang diisi.');
                        return;
                    }
                }
            });
        })();
    </script>
    @endpush

</x-layouts.layout>