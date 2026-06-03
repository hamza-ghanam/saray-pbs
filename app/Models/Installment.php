<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\InstallmentStatusEnum;
use App\Enums\InstallmentPaymentStatusEnum;

class Installment extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'payment_plan_id',
        'description',
        'percentage',
        'date',
        'amount',
        'booking_id',
        'status',
        'paid_amount',
    ];

    protected $appends = ['remaining_amount'];

    protected $casts = [
        'payment_plan_id'   => 'integer',
        'booking_id'        => 'integer',
        'date'              => 'date',
        'percentage'        => 'decimal:2',
        'amount'            => 'decimal:2',
        'paid_amount'       => 'decimal:2',
        'status'            => InstallmentStatusEnum::class,
    ];

    protected function remainingAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => round($this->amount - $this->paid_amount, 2),
        );
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', InstallmentStatusEnum::PENDING);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [
            InstallmentStatusEnum::PENDING,
            InstallmentStatusEnum::PARTIALLY_PAID,
            InstallmentStatusEnum::OVERDUE,
        ]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('date', '<', now())
            ->whereIn('status', [
                InstallmentStatusEnum::PENDING,
                InstallmentStatusEnum::PARTIALLY_PAID,
            ]);
    }

    /**
     * Get the payment plan that owns the installment.
     */
    public function paymentPlan()
    {
        return $this->belongsTo(PaymentPlan::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function payments()
    {
        return $this->hasMany(InstallmentPayment::class);
    }

    // Helper methods
    public function isPaid(): bool
    {
        return $this->status === InstallmentStatusEnum::PAID;
    }

    public function isFullyPaid(): bool
    {
        return $this->paid_amount >= $this->amount;
    }

// After
    public function recalculateStatus(): void
    {
        $paid   = (float) $this->paid_amount;
        $total  = (float) $this->amount;
        $isPast = $this->date->isPast();

        if ($paid <= 0 && $isPast) {
            $this->status = InstallmentStatusEnum::OVERDUE;
        } elseif ($paid <= 0) {
            $this->status = InstallmentStatusEnum::PENDING;
        } elseif ($paid >= $total) {
            $this->status = InstallmentStatusEnum::PAID;
        } else {
            $this->status = InstallmentStatusEnum::PARTIALLY_PAID;
        }

        $this->save();
    }

    public function verifiedPayments()
    {
        return $this->hasMany(InstallmentPayment::class)
            ->where('status', InstallmentPaymentStatusEnum::VERIFIED);
    }
}
