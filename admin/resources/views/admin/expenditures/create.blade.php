<x-layouts.layout title="Tambah Pengeluaran" pageTitle="Pengeluaran">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 mb-3">
        <a href="{{ route('dashboard') }}" class="hover:text-gray-600 transition-colors">Dashboard</a>
        <i class="bi bi-chevron-right text-[10px]"></i>
        <a href="{{ route('expenditures.index') }}" class="hover:text-gray-600 transition-colors">Pengeluaran</a>
        <i class="bi bi-chevron-right text-[10px]"></i>
        <span class="text-gray-700 font-medium">Tambah Pengeluaran</span>
    </nav>

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('expenditures.index') }}"
           class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition shadow-sm shrink-0">
            <i class="bi bi-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-lg sm:text-xl font-extrabold text-gray-900">Tambah Pengeluaran</h1>
            <p class="text-xs text-gray-400 mt-0.5">Catat pengeluaran baru toko Anda</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-100 text-red-700 text-sm rounded-xl px-4 py-3 mb-5">
            <p class="font-semibold mb-1"><i class="bi bi-exclamation-circle-fill"></i> Periksa kembali isian berikut:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        // Opsi kategori pengeluaran (statis). Dipetakan ke bentuk
        // [value, name] agar cocok dengan prop optionValue/optionLabel
        // default pada <x-form-select>.
        $categoryOptions = [
            ['value' => 'stok', 'name' => 'Pembelian Stok'],
            ['value' => 'listrik', 'name' => 'Listrik'],
            ['value' => 'gaji', 'name' => 'Gaji Karyawan'],
            ['value' => 'sewa', 'name' => 'Sewa Tempat'],
            ['value' => 'transportasi', 'name' => 'Transportasi'],
            ['value' => 'perawatan', 'name' => 'Perawatan / Perbaikan'],
            ['value' => 'lainnya', 'name' => 'Lainnya'],
        ];

        // Opsi transaksi pembelian. Label digabung dulu di sini (invoice,
        // supplier, total) supaya <x-form-select> tetap bisa dipakai apa
        // adanya lewat satu field optionLabel, tanpa perlu component
        // mendukung format label majemuk.
        $purchaseOptions = $purchases->map(function ($purchase) {
            return [
                'id' => $purchase->id,
                'label' => ($purchase->invoice ?? ('#' . $purchase->id))
                    . ' — ' . ($purchase->supplier ?? 'Tanpa supplier')
                    . ' (Rp ' . number_format($purchase->total, 0, ',', '.') . ')',
            ];
        });
    @endphp

    <form action="{{ route('expenditures.store') }}" method="POST" enctype="multipart/form-data" x-data="imagePreview()" class="max-w-3xl">
        @csrf

        {{-- Detail Pengeluaran --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6 mb-5">
            <div class="flex items-center gap-2.5 mb-5">
                <div class="w-8 h-8 bg-red-50 rounded-lg flex items-center justify-center">
                    <i class="bi bi-wallet2 text-red-600 text-sm"></i>
                </div>
                <h2 class="text-sm font-bold text-gray-900">Detail Pengeluaran</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <x-form-input
                        label="Deskripsi"
                        name="description"
                        placeholder="Contoh: Beli galon air minum"
                        :required="true"
                    />
                </div>

                <x-form-input
                    label="Jumlah"
                    name="amount"
                    type="number"
                    prefix="Rp"
                    placeholder="0"
                    :required="true"
                    min="0"
                    step="100"
                />

                <x-form-input
                    label="Tanggal"
                    name="expense_date"
                    type="date"
                    :value="date('Y-m-d')"
                    :required="true"
                />

                <div class="sm:col-span-2">
                    <x-form-select
                        label="Kategori"
                        name="category"
                        :options="$categoryOptions"
                        optionValue="value"
                        optionLabel="name"
                        placeholder="-- Pilih Kategori --"
                    />
                </div>
            </div>
        </div>

        {{-- Kaitkan dengan Pembelian --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6 mb-5">
            <div class="flex items-center gap-2.5 mb-1.5">
                <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                    <i class="bi bi-box-seam text-blue-600 text-sm"></i>
                </div>
                <h2 class="text-sm font-bold text-gray-900">Kaitkan dengan Pembelian</h2>
            </div>
            <p class="text-xs text-gray-400 mb-5 ml-10.5">Opsional — pilih transaksi pembelian yang berkaitan dengan pengeluaran ini.</p>

            <x-form-select
                label="Transaksi Pembelian"
                name="purchase_id"
                :options="$purchaseOptions"
                optionValue="id"
                optionLabel="label"
                placeholder="-- Tidak terkait pembelian --"
            />
        </div>

        {{-- Bukti / Nota --}}
        {{-- Tetap custom (bukan component): butuh dropzone + preview
             gambar/PDF lewat Alpine (imagePreview()), yang di luar cakupan
             <x-form-input> biasa. --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6 mb-5">
            <div class="flex items-center gap-2.5 mb-1.5">
                <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center">
                    <i class="bi bi-receipt text-amber-600 text-sm"></i>
                </div>
                <h2 class="text-sm font-bold text-gray-900">Bukti / Nota</h2>
            </div>
            <p class="text-xs text-gray-400 mb-5 ml-10.5">Opsional — pilih transaksi pembelian di atas dulu, nota akan tersimpan pada transaksi tersebut.</p>

            <label class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-gray-200 rounded-xl px-4 py-6 cursor-pointer hover:border-emerald-400 hover:bg-emerald-50/30 transition @error('receipt_image') border-red-300 @enderror">
                <i class="bi bi-cloud-arrow-up text-2xl text-gray-400"></i>
                <span class="text-xs text-gray-500" x-text="fileName ?? 'Klik untuk upload foto nota (JPG, PNG, atau PDF, maks 5MB)'"></span>
                <input type="file" name="receipt_image" accept=".jpg,.jpeg,.png,.pdf" class="hidden" @change="preview($event)">
            </label>
            @error('receipt_image')
                <p class="text-xs text-red-500 mt-1.5">{{ $message }}</p>
            @enderror

            {{-- Preview file yang dipilih --}}
            <template x-if="previewUrl">
                <div class="mt-4">
                    <p class="text-xs font-semibold text-gray-500 mb-2">Preview:</p>
                    <img :src="previewUrl" alt="Preview nota" class="w-32 h-32 object-cover rounded-xl border border-gray-200 shadow-sm">
                </div>
            </template>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <x-button
                :href="route('expenditures.index')"
                icon="bi-x-circle"
                label="Batal"
                variant="secondary"
            />
            <x-button
                type="submit"
                icon="bi-check-circle-fill"
                label="Simpan Pengeluaran"
                variant="primary"
            />
        </div>
    </form>

    <script>
    function imagePreview() {
        return {
            previewUrl: null,
            fileName: null,
            preview(event) {
                const file = event.target.files[0];
                if (!file) { this.previewUrl = null; this.fileName = null; return; }
                this.fileName = file.name;
                if (file.type === 'application/pdf') {
                    this.previewUrl = '/images/pdf-placeholder.png';
                    return;
                }
                const reader = new FileReader();
                reader.onload = e => this.previewUrl = e.target.result;
                reader.readAsDataURL(file);
            }
        }
    }
    </script>
</x-layouts.layout>