<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Ubah ENUM agar menyertakan 'qris' dan 'transfer'
        DB::statement("ALTER TABLE sales_transactions MODIFY COLUMN payment_type ENUM('cash', 'debt', 'qris', 'transfer') NOT NULL DEFAULT 'cash'");
    }

    public function down()
    {
        // Kembalikan ke semula
        DB::statement("ALTER TABLE sales_transactions MODIFY COLUMN payment_type ENUM('cash', 'debt') NOT NULL DEFAULT 'cash'");
    }
};