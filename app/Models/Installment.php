<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
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
    ];

    /**
     * Get the payment plan that owns the installment.
     */
    public function paymentPlan()
    {
        return $this->belongsTo(PaymentPlan::class);
    }
}
