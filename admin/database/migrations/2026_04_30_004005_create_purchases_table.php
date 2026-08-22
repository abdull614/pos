<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('invoice')->nullable();
            $table->string('supplier')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('receipt_image')->nullable();
            $table->date('tanggal')->nullable();
            $table->text('note')->nullable();
            $table->decimal('total', 15, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->nullable();
            $table->timestamps();
            // $table->softDeletes(); // uncomment jika ingin soft delete
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
