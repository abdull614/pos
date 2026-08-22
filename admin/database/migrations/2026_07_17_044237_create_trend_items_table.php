<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrendItemsTable extends Migration
{
    public function up()
    {
        Schema::create('trend_items', function (Blueprint $table) {
            $table->id();
            $table->string('external_name');
            $table->string('normalized_name')->nullable();
            $table->integer('score')->nullable();
            $table->string('source', 50)->nullable();
            $table->json('payload')->nullable();
            $table->foreignId('matched_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->integer('match_confidence')->nullable();
            $table->string('status')->default('new'); // new, matched, review
            $table->timestamps();

            $table->index('normalized_name');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('trend_items');
    }
}

