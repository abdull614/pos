<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_receivables', function (Blueprint $table) {
            // Drop dulu foreign key constraint lama, baru ubah kolom.
            $table->dropForeign(['sales_transaction_id']);
        });

        Schema::table('customer_receivables', function (Blueprint $table) {
            $table->foreignId('sales_transaction_id')
                ->nullable()
                ->change();

            $table->foreign('sales_transaction_id')
                ->references('id')->on('sales_transactions')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('customer_receivables', function (Blueprint $table) {
            $table->dropForeign(['sales_transaction_id']);
        });

        Schema::table('customer_receivables', function (Blueprint $table) {
            $table->foreignId('sales_transaction_id')
                ->nullable(false)
                ->change();

            $table->foreign('sales_transaction_id')
                ->references('id')->on('sales_transactions')
                ->onDelete('restrict');
        });
    }
};