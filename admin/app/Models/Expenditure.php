<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Expenditure extends Model
{
    protected $fillable = [
        'description',
        'amount',
        'expense_date',
        'category',
        'created_by',
        'purchase_id',
    ];

    // FIX: format eksplisit 'date:Y-m-d' (bukan sekadar 'date').
    // Cast 'date' biasa membuat Carbon men-serialize expense_date ke ISO
    // 8601 penuh DALAM UTC saat di-response()->json() — misalnya tanggal
    // yang tersimpan "26 Juli 2026" (di timezone app Asia/Jakarta, UTC+7)
    // ikut terserialize sebagai "2026-07-25T17:00:00.000000Z", karena
    // Carbon menganggap jam 00:00 lokal lalu dikonversi ke UTC (mundur 7
    // jam ke hari sebelumnya). Flutter lalu mengambil bagian tanggal dari
    // string itu apa adanya dan menampilkan tanggal mundur satu hari.
    //
    // Dengan 'date:Y-m-d', field ini SELALU dikirim sebagai tanggal polos
    // "2026-07-26" tanpa jam/timezone sama sekali — tidak ada informasi
    // waktu yang bisa "digeser" saat konversi UTC, jadi tidak ada ruang
    // salah parse di sisi client manapun.
    protected $casts = [
        'expense_date' => 'date:Y-m-d',
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    // Jika Anda ingin helper URL untuk receipt (jika sempat menaruh di expenditure)
    public function getReceiptUrlAttribute()
    {
        return $this->receipt_image ? Storage::disk('public')->url($this->receipt_image) : null;
    }
}