<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Purchase extends Model
{
    protected $fillable = [
        'invoice',
        'supplier',
        'supplier_id',
        'receipt_image',
        'tanggal',
        'note',
        'total',
        'created_by',
        'status',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'total' => 'decimal:2',
    ];

    // FIX: sebelumnya receipt_url (accessor di bawah) hanya di-append
    // secara manual di ExpenditureController@purchasesLookup. Akibatnya,
    // di endpoint lain yang me-load relasi 'purchase' — index(), show(),
    // store(), update() pada ExpenditureController — field receipt_url
    // TIDAK PERNAH ikut ter-serialize ke JSON, walau kolom receipt_image
    // di database sudah terisi. Ini yang bikin nota lama tidak muncul di
    // form edit untuk expenditure yang purchase_id-nya sudah terkait.
    //
    // Dengan didaftarkan di $appends, receipt_url otomatis ikut setiap
    // kali model Purchase (atau relasinya) diserialisasi ke JSON, di
    // mana pun itu dipanggil — tidak perlu append() manual lagi.
    protected $appends = ['receipt_url'];

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // FIX: sebelumnya pakai Storage::disk('public')->url(), yang untuk
    // disk 'public' SELALU memakai config 'url' => env('APP_URL').'/storage'
    // di config/filesystems.php — artinya host di URL ini statis mengikuti
    // APP_URL di .env, TIDAK peduli dari device/jaringan mana request API
    // sebenarnya datang. Kalau APP_URL=http://localhost, URL ini hanya
    // bisa diakses dari mesin server itu sendiri.
    //
    // asset() sebaliknya bersifat dinamis: secara default ia memakai host
    // dari request yang sedang berjalan (request()->getSchemeAndHttpHost()),
    // kecuali ASSET_URL di-set eksplisit di .env. Ini yang membuat gambar
    // produk selalu tampil di device manapun — kemungkinan besar accessor
    // image_url di model Product memakai asset(), bukan Storage::url().
    // Menyamakan pola di sini supaya nota berperilaku sama.
    public function getReceiptUrlAttribute()
    {
        return $this->receipt_image ? asset('storage/' . $this->receipt_image) : null;
    }
}