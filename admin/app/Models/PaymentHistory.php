<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentHistory extends Model
{
    protected $table = 'payment_history';

    protected $fillable = [
        'customer_receivable_id',
        'customer_id',
        'amount',
        'payment_method',
        'reference',
        'notes',
        'proof_of_payment',
        'recorded_by',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /**
     * Otomatis ikut disertakan setiap kali model ini di-serialize ke
     * JSON/array (dipakai baik oleh response API untuk Flutter, maupun
     * saat diakses langsung di Blade admin). Jadi cukup akses
     * $payment->proof_of_payment_url dari mana saja tanpa perlu ubah
     * controller yang sudah ada.
     */
    protected $appends = ['proof_of_payment_url'];

    /**
     * Ubah path relatif (yang tersimpan di kolom proof_of_payment, hasil
     * dari $file->store('payment_proofs', 'public')) jadi URL publik
     * lengkap yang bisa langsung dipakai <img src="..."> atau Image.network().
     * Null kalau memang belum ada bukti pembayaran.
     */
    public function getProofOfPaymentUrlAttribute(): ?string
    {
        return $this->proof_of_payment
            ? asset('storage/' . $this->proof_of_payment)
            : null;
    }

    public function customerReceivable(): BelongsTo
    {
        return $this->belongsTo(CustomerReceivable::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}