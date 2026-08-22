<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Kolom `payment_method` di tabel `payment_history` adalah ENUM
     * MySQL (cash, bank_transfer, check, other) dengan default 'cash'
     * (lihat migration create_payment_history_table). Laravel migration
     * tidak punya helper langsung untuk mengubah isi enum, jadi kita
     * pakai raw SQL (ALTER TABLE ... MODIFY COLUMN) untuk menambahkan
     * nilai 'qris' tanpa menghapus data yang sudah ada maupun default
     * value aslinya.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `payment_history`
            MODIFY COLUMN `payment_method`
            ENUM('cash', 'bank_transfer', 'check', 'other', 'qris')
            NOT NULL DEFAULT 'cash'
        ");
    }

    /**
     * Rollback: kembalikan enum ke daftar semula.
     * Catatan: kalau ada baris dengan payment_method = 'qris' saat
     * rollback dijalankan, MySQL akan mengosongkan nilainya (invalid
     * enum -> '' ) karena strict mode nonaktif untuk ALTER, atau bisa
     * gagal kalau strict mode aktif. Pastikan tidak ada data 'qris'
     * sebelum rollback, atau migrasikan datanya dulu ke metode lain.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE `payment_history`
            MODIFY COLUMN `payment_method`
            ENUM('cash', 'bank_transfer', 'check', 'other')
            NOT NULL DEFAULT 'cash'
        ");
    }
};