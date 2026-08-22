<x-layouts.layout title="Tambah Debitur" pageTitle="Tambah Debitur" no-scroll>

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 mb-3">
        <a href="{{ route('dashboard') }}" class="hover:text-gray-600 transition-colors">Dashboard</a>
        <i class="bi bi-chevron-right text-[10px]"></i>
        <a href="{{ route('debt-book.index') }}" class="hover:text-gray-600 transition-colors">Buku Utang</a>
        <i class="bi bi-chevron-right text-[10px]"></i>
        <span class="text-gray-700 font-medium">Tambah Debitur</span>
    </nav>

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('debt-book.index') }}"
           class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition shadow-sm shrink-0">
            <i class="bi bi-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-lg sm:text-xl font-extrabold text-gray-900">Tambah Debitur</h1>
            <p class="text-xs text-gray-400 mt-0.5">Catat pelanggan baru beserta utang awalnya</p>
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

    <form action="{{ route('debt-book.store') }}" method="POST" class="w-full">
        @csrf

        {{-- Data Pelanggan --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6 mb-5">
            <div class="flex items-center gap-2.5 mb-5">
                <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center">
                    <i class="bi bi-person-fill text-emerald-600 text-sm"></i>
                </div>
                <h2 class="text-sm font-bold text-gray-900">Data Pelanggan</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nama Debitur <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        placeholder="Contoh: Budi Santoso"
                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 @error('name') border-red-300 @enderror">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nomor Telepon / WhatsApp</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                        placeholder="Contoh: 081234567890"
                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 @error('phone') border-red-300 @enderror">
                    <p class="text-[11px] text-gray-400 mt-1">Dipakai untuk kirim pengingat via WhatsApp.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Alamat</label>
                    <input type="text" name="address" value="{{ old('address') }}"
                        placeholder="Contoh: Jl. Merdeka No. 10"
                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 @error('address') border-red-300 @enderror">
                </div>
            </div>
        </div>

        {{-- Utang Awal --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6 mb-5">
            <div class="flex items-center gap-2.5 mb-5">
                <div class="w-8 h-8 bg-red-50 rounded-lg flex items-center justify-center">
                    <i class="bi bi-cash-coin text-red-600 text-sm"></i>
                </div>
                <h2 class="text-sm font-bold text-gray-900">Utang Awal</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nominal Utang <span class="text-red-500">*</span></label>
                    <div class="relative">
                        {{--
                            FIX: sebelumnya span "Rp" (left-4, ~16px) dan
                            padding input (pl-10, 40px) cuma berjarak ~4-6px
                            — kelihatan mepet/nabrak dengan angka, apalagi
                            kalau lebar font sedikit lebih besar dari
                            perkiraan. Sekarang: span digeser ke left-3.5,
                            ditambah pointer-events-none supaya klik di area
                            teks "Rp" tetap fokus ke input, dan padding
                            input dilebarkan ke pl-12 supaya ada jarak napas
                            yang jelas ke angka.
                        --}}
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-400 pointer-events-none select-none">
                            Rp
                        </span>
                        <input type="number" name="amount" value="{{ old('amount') }}" required min="1" step="1"
                            placeholder="0"
                            class="w-full rounded-xl border border-gray-200 pl-14 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 @error('amount') border-red-300 @enderror">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Jatuh Tempo <span class="text-red-500">*</span></label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}" required
                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 @error('due_date') border-red-300 @enderror">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Catatan</label>
                    <textarea name="notes" rows="3" placeholder="Opsional, contoh: utang belanja sembako"
                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 @error('notes') border-red-300 @enderror">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('debt-book.index') }}"
               class="px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">
                Batal
            </a>
            <button type="submit"
                class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition">
                <i class="bi bi-check-circle-fill"></i> Simpan Debitur
            </button>
        </div>
    </form>

</x-layouts.layout>