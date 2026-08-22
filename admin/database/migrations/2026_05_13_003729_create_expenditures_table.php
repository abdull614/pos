<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('expenditures', function (Blueprint $table) {
            $table->id();
            $table->string('description');      // contoh: "Beli stok beras 50kg"
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->string('category')->nullable(); // stok, listrik, gaji, dll

            // created_by nullable agar tidak wajib selalu ada user
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // optional: relasi ke purchases jika ingin mengaitkan pengeluaran ke pembelian stok
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->nullOnDelete();

            $table->timestamps();

            // index untuk query laporan
            $table->index('expense_date');
            $table->index('category');
            $table->index('created_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('expenditures');
    }
};
