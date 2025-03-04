<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentPlan extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'unit_id',
        'name',
        'selling_price',
        'dld_fee',
        'admin_fee',
        'discount',
        'EOI',
        'booking_percentage',
        'handover_percentage',
        'construction_percentage',
        'first_construction_installment_date',
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
        return $this->hasMany(Installment::class);
    }
}
