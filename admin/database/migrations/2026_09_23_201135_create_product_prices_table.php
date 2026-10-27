<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('unit'); // contoh: pcs, renceng, gram, kg
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('selling_price', 12, 2);
            $table->timestamps();

            $table->unique(['product_id', 'unit']); // satu produk tidak boleh punya unit duplikat
        });
    }

    public function down(): void {
        Schema::dropIfExists('product_prices');
    }
};
