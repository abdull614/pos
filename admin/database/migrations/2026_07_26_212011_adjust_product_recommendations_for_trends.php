<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_recommendations', function (Blueprint $table) {
            // product_id jadi opsional — rekomendasi bisa berdiri sendiri
            // tanpa harus terhubung ke produk yang sudah ada.
            $table->foreignId('product_id')->nullable()->change();

            $table->string('keyword')->nullable()->after('product_id');
            $table->unsignedInteger('rank')->nullable()->after('priority');
            $table->float('trend_score')->nullable()->after('rank');
            $table->string('geo', 10)->default('ID')->after('trend_score');
            $table->string('source')->default('google_trends')->after('geo');
            $table->timestamp('fetched_at')->nullable()->after('source');

            $table->unique(['keyword', 'fetched_at']);
        });
    }

    public function down(): void
    {
        Schema::table('product_recommendations', function (Blueprint $table) {
            $table->dropUnique(['keyword', 'fetched_at']);
            $table->dropColumn(['keyword', 'rank', 'trend_score', 'geo', 'source', 'fetched_at']);
            $table->foreignId('product_id')->nullable(false)->change();
        });
    }
};