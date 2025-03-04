<?php

namespace App\Listeners;

use App\Events\UnitCreated;
use App\Services\PaymentPlanService;

class GeneratePaymentPlansForUnit
{
    protected $paymentPlanService;

    /**
     * Create the event listener instance.
     *
     * @param PaymentPlanService $paymentPlanService
     * @return void
     */
    public function __construct(PaymentPlanService $paymentPlanService)
    {
        $this->paymentPlanService = $paymentPlanService;
    }

    /**
     * Handle the event.
     *
     * @param UnitCreated $event
     * @return void
     */
    public function handle(UnitCreated $event)
    {
        // Generate the three default payment plans for the unit.
        $this->paymentPlanService->generateDefaultPlansForUnit($event->unit);
    }
}
