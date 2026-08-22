{{-- resources/views/admin/store_setting/edit.blade.php --}}

<x-layouts.layout title="Profil Toko" pageTitle="Profil Toko">

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl shadow p-6">

            @if(session('success'))
                <div class="mb-4 px-4 py-3 bg-emerald-100 text-emerald-700 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- Preview Logo --}}
                <div class="flex flex-col items-center mb-6">
                    <div class="w-28 h-28 rounded-full overflow-hidden border-4 border-emerald-200 mb-3 bg-gray-100 flex items-center justify-center">
                        @if($setting->logo)
                            <img id="logo-preview"
                                 src="{{ asset('storage/' . $setting->logo) }}"
                                 alt="Logo Toko"
                                 class="w-full h-full object-cover">
                        @else
                            <img id="logo-preview" src="" alt="" class="w-full h-full object-cover hidden">
                            <span id="logo-placeholder" class="text-gray-400 text-xs text-center">Belum ada logo</span>
                        @endif
                    </div>
                    <label for="logo" class="cursor-pointer text-sm text-emerald-600 hover:underline font-medium">
                        Ganti Logo
                    </label>
                    <input type="file" id="logo" name="logo" accept="image/*" class="hidden"
                           onchange="previewLogo(event)">
                    @error('logo')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Nama Toko --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Toko <span class="text-red-500">*</span></label>
                    <input type="text" name="store_name"
                           value="{{ old('store_name', $setting->store_name) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
                           placeholder="Contoh: Toko Sembako Barokah">
                    @error('store_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Alamat --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                    <textarea name="address" rows="3"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
                              placeholder="Jl. Contoh No. 123, Kelurahan, Kecamatan, Kota">{{ old('address', $setting->address) }}</textarea>
                    @error('address')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Nomor Telepon --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Telepon</label>
                    <input type="text" name="phone"
                           value="{{ old('phone', $setting->phone) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
                           placeholder="08xxxxxxxxxx">
                    @error('phone')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-semibold py-2 rounded-lg transition text-sm">
                    Simpan Perubahan
                </button>
            </form>
        </div>
    </div>

    <script>
        function previewLogo(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById('logo-preview');
                const placeholder = document.getElementById('logo-placeholder');
                img.src = e.target.result;
                img.classList.remove('hidden');
                if (placeholder) placeholder.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }
    </script>

</x-layouts.layout>