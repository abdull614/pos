<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;

use App\Models\Product;
use App\Models\Category;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AdminProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $products = Product::with('category')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sku' => 'required|unique:products',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'integer|min:0',
            'unit' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'sku.required' => 'SKU wajib diisi',
            'sku.unique' => 'SKU sudah terdaftar',
            'name.required' => 'Nama produk wajib diisi',
            'category_id.required' => 'Kategori wajib dipilih',
            'image.image' => 'File harus berupa gambar',
            'image.max' => 'Ukuran gambar maksimal 2MB',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        Product::create($validated);

        return redirect('/admin/products')->with('success', 'Produk berhasil ditambahkan');
    }

    public function edit(Product $product)
    {
        $categories = Category::where('is_active', true)->get();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'sku' => "required|unique:products,sku,{$product->id}",
            'name' => 'required|string',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'integer|min:0',
            'unit' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_image' => 'nullable|boolean',
        ], [
            'sku.required' => 'SKU wajib diisi',
            'sku.unique' => 'SKU sudah terdaftar',
            'name.required' => 'Nama produk wajib diisi',
            'category_id.required' => 'Kategori wajib dipilih',
            'image.image' => 'File harus berupa gambar',
            'image.max' => 'Ukuran gambar maksimal 2MB',
        ]);

        // Hapus gambar lama jika user centang "Hapus gambar saat ini"
        if ($request->boolean('remove_image') && $product->image) {
            Storage::disk('public')->delete($product->image);
            $validated['image'] = null;
        }

        // Ganti gambar jika ada upload baru
        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        unset($validated['remove_image']);

        $product->update($validated);

        return redirect('/admin/products')->with('success', 'Produk berhasil diperbarui');
    }

    public function destroy(Product $product)
    {
        // Cek apakah produk sudah pernah dipakai di transaksi pembelian
        $hasPurchaseHistory = DB::table('purchase_items')
            ->where('product_id', $product->id)
            ->exists();

        // Cek juga transaksi penjualan, kalau tabelnya sudah ada
        $hasSaleHistory = Schema::hasTable('sale_items')
            ? DB::table('sale_items')->where('product_id', $product->id)->exists()
            : false;

        if ($hasPurchaseHistory || $hasSaleHistory) {
            return redirect('/admin/products')
                ->with('error', "Produk \"{$product->name}\" tidak bisa dihapus karena sudah memiliki riwayat transaksi. Nonaktifkan produk ini sebagai gantinya.");
        }

        // Aman dihapus karena belum pernah dipakai di transaksi apa pun
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect('/admin/products')->with('success', 'Produk berhasil dihapus');
    }

    public function toggleActive(Product $product)
    {
        $product->update(['is_active' => !$product->is_active]);

        // Tidak ada flash message di sini secara sengaja —
        // perubahan status sudah terlihat langsung dari warna badge
        // (hijau = aktif, merah = nonaktif) di halaman daftar produk,
        // jadi notifikasi tambahan di atas card tidak diperlukan.
        return redirect('/admin/products');
    }
}