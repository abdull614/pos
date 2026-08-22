<x-layouts.layout title="Produk" pageTitle="Produk">

    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('products.index') }}"
           class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:-translate-x-0.5 active:scale-95 transition-all duration-150 shadow-sm shrink-0">
            <i class="bi bi-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 tracking-tight">Tambah Produk</h1>
            <p class="text-xs text-gray-400 mt-0.5">Isi detail produk baru untuk ditambahkan ke inventori</p>
        </div>
    </div>

    <div class="w-full">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow duration-300 overflow-hidden">

            <!-- Card Header -->
            <div class="px-5 sm:px-8 py-5 border-b border-gray-100 bg-linear-to-r from-emerald-50/60 via-white to-white flex items-center gap-3">
                <div class="w-9 h-9 bg-linear-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center shrink-0 shadow-sm shadow-emerald-200">
                    <i class="bi bi-box-seam text-white text-sm"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-gray-800">Informasi Produk</h2>
                    <p class="text-[11px] text-gray-400">Kolom bertanda * wajib diisi</p>
                </div>
            </div>

            <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" class="p-5 sm:p-8 space-y-6">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <x-form-input
                        label="SKU"
                        name="sku"
                        placeholder="Otomatis terisi..."
                        :required="true"
                        :readonly="true"
                    />

                    <x-form-input
                        label="Nama Produk"
                        name="name"
                        placeholder="Nama produk"
                        :required="true"
                    />

                    <div class="flex gap-2 items-end">
                        <div class="flex-1">
                            <x-form-select
                                name="category_id"
                                label="Kategori"
                                :options="$categories->toArray()"
                                placeholder="-- Pilih Kategori --"
                                :required="true"
                            />
                        </div>
                        <button type="button"
                                onclick="bukaModal()"
                                class="mb-0.5 w-10 h-10 flex items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 hover:bg-emerald-100 active:scale-95 transition-all duration-150 shrink-0"
                                title="Tambah Kategori Baru">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </div>

                <x-form-textarea
                    label="Deskripsi"
                    name="description"
                    placeholder="Deskripsi singkat produk..."
                    :optional="true"
                />

                {{-- Upload Gambar --}}
                <x-form-section title="Gambar Produk">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-2">
                            Foto Produk <span class="text-gray-400 font-normal">(opsional)</span>
                        </label>
                        <div class="flex items-center gap-4">
                            <div id="preview-wrapper" class="hidden w-20 h-20 rounded-xl overflow-hidden border-2 border-emerald-100 shrink-0 shadow-sm">
                                <img id="preview-image" src="" alt="Preview" class="w-full h-full object-cover">
                            </div>
                            <label for="image" class="flex-1 max-w-md group cursor-pointer">
                                <div class="border-2 border-dashed border-gray-200 rounded-xl px-4 py-3.5 flex items-center gap-3 group-hover:border-emerald-300 group-hover:bg-emerald-50/40 transition-all duration-200">
                                    <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-emerald-100 flex items-center justify-center shrink-0 transition-colors">
                                        <i class="bi bi-cloud-arrow-up text-gray-400 group-hover:text-emerald-600 transition-colors"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-gray-600 group-hover:text-emerald-700 transition-colors">Klik untuk unggah foto</p>
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
                    </div>
                </x-form-section>

                <x-form-section title="Harga">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <x-form-input label="Harga Beli" name="purchase_price" type="number" placeholder="0" prefix="Rp" :required="true" />
                        <x-form-input label="Harga Jual" name="selling_price" type="number" placeholder="0" prefix="Rp" :required="true" />
                    </div>
                </x-form-section>

                <x-form-section title="Stok & Satuan">
                    <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                        <x-form-input label="Stok Awal" name="stock" type="number" value="0" :required="true" />
                        <x-form-input label="Min. Stok" name="min_stock" type="number" value="5" />
                        <x-form-input label="Satuan" name="unit" value="pcs" placeholder="pcs, kg, liter..." :required="true" />
                    </div>
                </x-form-section>

                <!-- Sticky action bar -->
                <div class="sticky bottom-0 -mx-5 sm:-mx-8 -mb-5 sm:-mb-8 px-5 sm:px-8 py-4 bg-white/90 backdrop-blur-sm border-t border-gray-100 flex flex-wrap items-center gap-3">
                    <x-button type="submit" icon="bi-check-circle" label="Simpan Produk" variant="primary" />
                    <x-button :href="route('products.index')" icon="bi-x-circle" label="Batal" variant="secondary" />
                </div>

            </form>
        </div>
    </div>

    <!-- Modal Tambah Kategori -->
    <div id="modal-kategori"
         class="hidden fixed inset-0 z-50 items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden animate-[fadeIn_0.15s_ease-out]">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-linear-to-r from-emerald-50/60 to-white">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 bg-linear-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-sm shadow-emerald-200">
                        <i class="bi bi-tag text-white text-sm"></i>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800">Tambah Kategori</h3>
                </div>
                <button onclick="tutupModal()"
                        class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-400 transition">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
            <form id="form-kategori" class="p-6 space-y-4">
                @csrf
                <x-form-input
                    label="Nama Kategori"
                    name="category_name"
                    placeholder="cth: Minuman, Makanan..."
                    :required="true"
                />
                <div class="flex gap-3 pt-1">
                    <x-button type="submit" icon="bi-check-circle" label="Simpan" variant="primary" class="flex-1 justify-center" />
                    <x-button type="button" icon="bi-x-circle" label="Batal" variant="secondary" class="flex-1 justify-center" onclick="tutupModal()" />
                </div>
            </form>
        </div>
    </div>

    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.96) translateY(4px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
    </style>

    @push('scripts')
    <script>
        function bukaModal() {
            const modal = document.getElementById('modal-kategori');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function tutupModal() {
            const modal = document.getElementById('modal-kategori');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.getElementById('modal-kategori').addEventListener('click', function(e) {
            if (e.target === this) tutupModal();
        });

        document.getElementById('form-kategori').addEventListener('submit', async function(e) {
            e.preventDefault();
            const input = this.querySelector('input[name="category_name"]');
            const name = input.value.trim();
            if (!name) return;
            try {
                const response = await fetch('{{ route('categories.quick-store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({ name }),
                });
                const data = await response.json();
                if (data.success) {
                    const select = document.querySelector('select[name="category_id"]');
                    const option = new Option(data.category.name, data.category.id, true, true);
                    select.add(option);
                    select.value = data.category.id;
                    input.value = '';
                    tutupModal();
                    generateSKU();
                }
            } catch (error) {
                console.error('Gagal:', error);
            }
        });

        // === SKU Otomatis ===
        let currentSkuPrefix = '';

        function singkat(teks) {
            if (!teks || !teks.trim()) return '';
            const kata = teks.trim().split(/\s+/);
            if (kata.length === 1) {
                return kata[0].substring(0, Math.min(3, kata[0].length)).toUpperCase();
            }
            return kata.slice(0, 3).map(k => k.length ? k[0].toUpperCase() : '').join('');
        }

        function generateSKU() {
            const categorySelect = document.querySelector('select[name="category_id"]');
            const nameInput = document.querySelector('input[name="name"]');
            const skuInput = document.querySelector('input[name="sku"]');
            if (!categorySelect || !nameInput || !skuInput) return;

            const categoryText = categorySelect.options[categorySelect.selectedIndex]?.text ?? '';
            const nameText = nameInput.value ?? '';

            const singkatKategori = (categoryText && !categoryText.startsWith('--'))
                ? singkat(categoryText) : '';
            const singkatNama = singkat(nameText);

            if (!singkatKategori && !singkatNama) {
                skuInput.value = '';
                currentSkuPrefix = '';
                return;
            }

            const parts = [];
            if (singkatKategori) parts.push(singkatKategori);
            if (singkatNama) parts.push(singkatNama);
            const newPrefix = parts.join('-');

            if (newPrefix !== currentSkuPrefix || !skuInput.value) {
                const angka = Math.floor(10000 + Math.random() * 90000);
                skuInput.value = `${newPrefix}-${angka}`;
                currentSkuPrefix = newPrefix;
            }
        }

        document.querySelector('select[name="category_id"]')?.addEventListener('change', generateSKU);
        document.querySelector('input[name="name"]')?.addEventListener('input', generateSKU);

        // === Preview Gambar ===
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
            } else {
                wrapper.classList.add('hidden');
                preview.src = '';
            }
        }
    </script>
@endpush

</x-layouts.layout>