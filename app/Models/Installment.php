<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    ];

    protected $casts = [
        'payment_plan_id' => 'integer',
        'booking_id' => 'integer',
    ];

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
}
