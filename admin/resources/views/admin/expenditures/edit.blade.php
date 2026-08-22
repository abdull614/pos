<x-layouts.layout title="Edit Pengeluaran" pageTitle="Pengeluaran">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 mb-3">
        <a href="{{ route('dashboard') }}" class="hover:text-gray-600 transition-colors">Dashboard</a>
        <i class="bi bi-chevron-right text-[10px]"></i>
        <a href="{{ route('expenditures.index') }}" class="hover:text-gray-600 transition-colors">Pengeluaran</a>
        <i class="bi bi-chevron-right text-[10px]"></i>
        <span class="text-gray-700 font-medium truncate max-w-40">{{ $expenditure->description }}</span>
    </nav>

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('expenditures.show', $expenditure) }}"
           class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition shadow-sm shrink-0">
            <i class="bi bi-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-lg sm:text-xl font-extrabold text-gray-900">Edit Pengeluaran</h1>
            <p class="text-xs text-gray-400 mt-0.5">Perbarui informasi pengeluaran</p>
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

    <form action="{{ route('expenditures.update', $expenditure) }}" method="POST" enctype="multipart/form-data"
          x-data="imagePreview()" class="max-w-3xl">
        @csrf @method('PUT')

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
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Deskripsi <span class="text-red-500">*</span></label>
                    <input type="text" name="description" value="{{ old('description', $expenditure->description) }}" required
                        placeholder="Contoh: Beli galon air minum"
                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 @error('description') border-red-300 @enderror">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Jumlah <span class="text-red-500">*</span></label>
                    <div class="flex items-stretch rounded-xl border border-gray-200 overflow-hidden focus-within:ring-2 focus-within:ring-emerald-500/40 focus-within:border-emerald-500 @error('amount') border-red-300 @enderror">
                        <span class="flex items-center px-3.5 bg-gray-50 text-sm text-gray-500 border-r border-gray-200 select-none">Rp</span>
                        <input type="number" name="amount" value="{{ old('amount', (int) $expenditure->amount) }}" required min="0" step="100"
                            placeholder="0"
                            class="w-full px-4 py-2.5 text-sm focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Tanggal <span class="text-red-500">*</span></label>
                    <input type="date" name="expense_date" value="{{ old('expense_date', $expenditure->expense_date?->format('Y-m-d')) }}" required
                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 @error('expense_date') border-red-300 @enderror">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Kategori</label>
                    @php $currentCategory = old('category', $expenditure->category); @endphp
                    <select name="category"
                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 @error('category') border-red-300 @enderror">
                        <option value="">-- Pilih Kategori --</option>
                        <option value="stok" {{ $currentCategory === 'stok' ? 'selected' : '' }}>Pembelian Stok</option>
                        <option value="listrik" {{ $currentCategory === 'listrik' ? 'selected' : '' }}>Listrik</option>
                        <option value="gaji" {{ $currentCategory === 'gaji' ? 'selected' : '' }}>Gaji Karyawan</option>
                        <option value="sewa" {{ $currentCategory === 'sewa' ? 'selected' : '' }}>Sewa Tempat</option>
                        <option value="transportasi" {{ $currentCategory === 'transportasi' ? 'selected' : '' }}>Transportasi</option>
                        <option value="perawatan" {{ $currentCategory === 'perawatan' ? 'selected' : '' }}>Perawatan / Perbaikan</option>
                        <option value="lainnya" {{ $currentCategory === 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                        @if($currentCategory && !in_array($currentCategory, ['stok','listrik','gaji','sewa','transportasi','perawatan','lainnya']))
                            <option value="{{ $currentCategory }}" selected>{{ $currentCategory }}</option>
                        @endif
                    </select>
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

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Transaksi Pembelian</label>
                <select name="purchase_id"
                    class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 @error('purchase_id') border-red-300 @enderror">
                    <option value="">-- Tidak terkait pembelian --</option>
                    @foreach ($purchases as $purchase)
                        <option value="{{ $purchase->id }}" {{ old('purchase_id', $expenditure->purchase_id) == $purchase->id ? 'selected' : '' }}>
                            {{ $purchase->invoice ?? ('#' . $purchase->id) }} — {{ $purchase->supplier ?? 'Tanpa supplier' }} (Rp {{ number_format($purchase->total, 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Bukti / Nota --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6 mb-5">
            <div class="flex items-center gap-2.5 mb-1.5">
                <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center">
                    <i class="bi bi-receipt text-amber-600 text-sm"></i>
                </div>
                <h2 class="text-sm font-bold text-gray-900">Bukti / Nota</h2>
            </div>
            <p class="text-xs text-gray-400 mb-5 ml-10.5">
                Nota tersimpan pada transaksi pembelian yang dikaitkan. Upload file baru untuk menggantinya.
            </p>

            {{-- Nota yang sudah tersimpan saat ini --}}
            @if($expenditure->purchase && $expenditure->purchase->receipt_image)
                <div class="mb-4">
                    <p class="text-xs font-semibold text-gray-500 mb-2">Nota saat ini:</p>
                    @if(str_ends_with($expenditure->purchase->receipt_image, '.pdf'))
                        <a href="{{ Storage::url($expenditure->purchase->receipt_image) }}" target="_blank"
                           class="inline-flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-emerald-600 hover:bg-emerald-50 hover:border-emerald-200 transition font-semibold text-sm">
                            <i class="bi bi-file-earmark-pdf-fill text-base"></i>
                            Lihat Nota (PDF)
                        </a>
                    @else
                        <a href="{{ Storage::url($expenditure->purchase->receipt_image) }}" target="_blank" class="inline-block group">
                            <img src="{{ Storage::url($expenditure->purchase->receipt_image) }}" alt="Nota saat ini"
                                 class="w-32 h-32 object-cover rounded-xl border border-gray-200 shadow-sm group-hover:shadow-md group-hover:scale-[1.02] transition">
                        </a>
                    @endif
                </div>
            @endif

            <label class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-gray-200 rounded-xl px-4 py-6 cursor-pointer hover:border-emerald-400 hover:bg-emerald-50/30 transition @error('receipt_image') border-red-300 @enderror">
                <i class="bi bi-cloud-arrow-up text-2xl text-gray-400"></i>
                <span class="text-xs text-gray-500" x-text="fileName ?? 'Klik untuk upload foto nota baru (JPG, PNG, atau PDF, maks 5MB) — akan menggantikan nota di atas'"></span>
                <input type="file" name="receipt_image" accept=".jpg,.jpeg,.png,.pdf" class="hidden" @change="preview($event)">
            </label>
            @error('receipt_image')
                <p class="text-xs text-red-500 mt-1.5">{{ $message }}</p>
            @enderror

            {{-- Preview file baru yang dipilih (belum disimpan) --}}
            <template x-if="previewUrl">
                <div class="mt-4">
                    <p class="text-xs font-semibold text-gray-500 mb-2">Preview file baru:</p>
                    <img :src="previewUrl" alt="Preview nota baru" class="w-32 h-32 object-cover rounded-xl border border-gray-200 shadow-sm">
                </div>
            </template>
        </div>

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3">
            <button type="submit"
                class="inline-flex items-center gap-2 justify-center bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition flex-1">
                <i class="bi bi-check-circle-fill"></i> Simpan Perubahan
            </button>
            <a href="{{ route('expenditures.show', $expenditure) }}"
               class="inline-flex items-center gap-2 justify-center px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-600 border border-gray-200 hover:bg-gray-50 transition">
                <i class="bi bi-x-circle"></i> Batal
            </a>
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