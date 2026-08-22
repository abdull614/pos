<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoreSettingController extends Controller
{
    // GET /api/v1/store-setting
    public function show()
    {
        $setting = StoreSetting::first();

        if (!$setting) {
            return response()->json([
                'store_name' => 'Toko Sembako',
                'address'    => null,
                'phone'      => null,
                'logo'       => null,
            ]);
        }

        if ($setting->logo) {
            $setting->logo = asset('storage/' . $setting->logo);
        }

        return response()->json($setting);
    }

    // PUT /api/v1/store-setting  (Admin only)
    public function update(Request $request)
    {
        $validated = $request->validate([
            'store_name' => 'required|string|max:255',
            'address'    => 'nullable|string',
            'phone'      => 'nullable|string|max:20',
            'logo'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'store_name.required' => 'Nama toko wajib diisi',
            'logo.image'          => 'Logo harus berupa gambar',
            'logo.max'            => 'Ukuran logo maksimal 2MB',
        ]);

        $setting = StoreSetting::first() ?? new StoreSetting();

        if ($request->hasFile('logo')) {
            // Hapus logo lama
            if ($setting->logo) {
                Storage::disk('public')->delete($setting->logo);
            }
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $setting->fill($validated)->save();

        if ($setting->logo) {
            $setting->logo = asset('storage/' . $setting->logo);
        }

        return response()->json([
            'message' => 'Profil toko berhasil diperbarui',
            'data'    => $setting,
        ]);
    }
}