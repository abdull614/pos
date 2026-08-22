<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('customer_receivables', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_receivables', 'due_date')) {
                $table->date('due_date')->nullable();
            }
            if (!Schema::hasColumn('customer_receivables', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('customer_receivables', function (Blueprint $table) {
            $table->dropColumn(['due_date', 'notes']);
        });
    }
};