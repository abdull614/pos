<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Makanan', 'description' => 'Produk makanan dan snack'],
            ['name' => 'Minuman', 'description' => 'Produk minuman'],
            ['name' => 'Peralatan', 'description' => 'Peralatan rumah tangga'],
            ['name' => 'Elektronik', 'description' => 'Barang elektronik'],
            ['name' => 'Pakaian', 'description' => 'Produk pakaian dan aksesori'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
