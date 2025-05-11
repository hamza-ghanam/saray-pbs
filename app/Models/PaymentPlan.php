<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentPlan extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'unit_id',
        'name',
        'dld_fee_percentage',
        'admin_fee',
        'EOI',
        'booking_percentage',
        'handover_percentage',
        'construction_percentage',
        'first_construction_installment_date',
        'isDefault',
        'blocks',
    ];

    protected $casts = [
        'blocks' => 'array',
    ];

    /**
     * Get the unit that owns the payment plan.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the installments associated with this payment plan.
     */
    public function installments()
    {
        return $this->hasMany(Installment::class)
            ->orderBy('date');
    }

    /**
     * A PaymentPlan can be used by many Bookings.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'payment_plan_id');
    }
}
