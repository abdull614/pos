<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class AdminCategoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $categories = Category::when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->paginate(15)
            ->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:categories',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Nama kategori wajib diisi',
            'name.unique' => 'Nama kategori sudah terdaftar',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        Category::create($validated);

        return redirect('/admin/categories')->with('success', 'Kategori berhasil ditambahkan');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => "required|unique:categories,name,{$category->id}",
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Nama kategori wajib diisi',
            'name.unique' => 'Nama kategori sudah terdaftar',
        ]);

        // Checkbox tidak terkirim sama sekali kalau tidak dicentang,
        // tapi form punya hidden input is_active=0 sebagai fallback,
        // jadi $request->boolean() akan menangkapnya dengan benar.
        $validated['is_active'] = $request->boolean('is_active');

        $category->update($validated);

        return redirect('/admin/categories')->with('success', 'Kategori berhasil diperbarui');
    }

    public function toggleActive(Category $category)
    {
        $category->update(['is_active' => !$category->is_active]);

        // Sengaja tanpa flash message — perubahan status sudah
        // terlihat langsung dari warna badge (hijau = aktif, merah = non-aktif).
        return redirect('/admin/categories');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect('/admin/categories')->with('success', 'Kategori berhasil dihapus');
    }
}