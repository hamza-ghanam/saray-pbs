<?php

namespace App\Events;

use App\Models\PaymentPlan;
use App\Models\Unit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class SalesOfferBookingCreated
{
    use Dispatchable, SerializesModels;

    public Unit $unit;
    public PaymentPlan $paymentPlan;
    public Collection $installments;

    /**
     * @param  Unit               $unit
     * @param  PaymentPlan        $paymentPlan
     * @param  Collection         $installments
     */
    public function __construct(Unit $unit, PaymentPlan $paymentPlan, Collection $installments)
    {
        $this->unit         = $unit;
        $this->paymentPlan  = $paymentPlan;
        $this->installments = $installments;
    }
}
