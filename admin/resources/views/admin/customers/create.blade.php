<x-layouts.layout title="Tambah Pelanggan" pageTitle="Pelanggan">

    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('customers.index') }}"
           class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 transition shadow-sm">
            <i class="bi bi-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-lg font-bold text-gray-900">Tambah Pelanggan</h1>
            <p class="text-xs text-gray-400 mt-0.5">Isi detail pelanggan baru</p>
        </div>
    </div>

    <div class="max-w-2xl">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

            <!-- Card Header -->
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2.5">
                <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center">
                    <i class="bi bi-person-plus text-emerald-600 text-sm"></i>
                </div>
                <h2 class="text-sm font-bold text-gray-800">Informasi Pelanggan</h2>
            </div>

            <form action="{{ route('customers.store') }}" method="POST" class="p-6 space-y-5">
                @csrf

                <!-- Nama & Telepon -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-form-input
                        label="Nama Pelanggan"
                        name="name"
                        placeholder="Nama lengkap pelanggan"
                        :required="true"
                    />
                    <x-form-input
                        label="No. Telepon"
                        name="phone"
                        placeholder="cth: 08123456789"
                    />
                </div>

                <!-- Email -->
                <x-form-input
                    label="Email"
                    name="email"
                    type="email"
                    placeholder="email@contoh.com"
                />

                <!-- Alamat -->
                <x-form-textarea
                    label="Alamat"
                    name="address"
                    placeholder="Alamat lengkap pelanggan..."
                    :optional="true"
                />

                <!-- Tipe Pelanggan -->
                <x-form-select
                    label="Tipe Pelanggan"
                    name="customer_type"
                    :options="[
                        ['id' => 'regular', 'name' => 'Regular'],
                        ['id' => 'reseller', 'name' => 'Reseller'],
                    ]"
                    placeholder="-- Pilih Tipe --"
                    :required="true"
                />

                <!-- Actions -->
                <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
                    <x-button type="submit" icon="bi-check-circle" label="Simpan Pelanggan" variant="primary" />
                    <x-button :href="route('customers.index')" icon="bi-x-circle" label="Batal" variant="secondary" />
                </div>

            </form>
        </div>
    </div>

</x-layouts.layout>