<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminStoreSettingController extends Controller
{
    public function edit()
    {
        $setting = StoreSetting::first() ?? new StoreSetting();
        return view('admin.store_setting.edit', compact('setting'));
    }

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
            if ($setting->logo) {
                Storage::disk('public')->delete($setting->logo);
            }
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $setting->fill($validated)->save();

        return redirect()->route('profile')
                         ->with('success', 'Profil toko berhasil disimpan');
    }
}