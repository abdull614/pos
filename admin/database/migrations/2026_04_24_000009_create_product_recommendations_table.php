<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('product_recommendations', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto-increment primary key
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('priority')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps(); // created_at, updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_recommendations');
    }
};
