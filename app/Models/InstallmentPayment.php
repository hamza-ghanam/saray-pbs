<?php

namespace App\Models;

use App\Enums\InstallmentPaymentStatusEnum;
use App\Enums\InstallmentStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstallmentPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'installment_id',
        'booking_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'receipt_path',
        'status',
        'verified_by',
        'verified_at',
        'notes',
        'rejection_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount'       => 'decimal:2',
        'verified_at'  => 'datetime',
        'status'       => InstallmentPaymentStatusEnum::class,
    ];

    // Scopes
    public function scopePendingVerification($query)
    {
        return $query->where('status', InstallmentPaymentStatusEnum::PENDING_VERIFICATION);
    }

    public function scopeVerified($query)
    {
        return $query->where('status', InstallmentPaymentStatusEnum::VERIFIED);
    }

    // Relations
    public function installment()
    {
        return $this->belongsTo(Installment::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    // Helpers
    public function isVerified(): bool
    {
        return $this->status === InstallmentPaymentStatusEnum::VERIFIED;
    }

    public function isPendingVerification(): bool
    {
        return $this->status === InstallmentPaymentStatusEnum::PENDING_VERIFICATION;
    }
}
