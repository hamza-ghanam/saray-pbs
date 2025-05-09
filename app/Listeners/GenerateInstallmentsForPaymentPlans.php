<?php

namespace App\Listeners;

use App\Events\SalesOfferBookingCreated;
use App\Events\UnitCreated;
use App\Services\PaymentPlanService;
use Illuminate\Support\Facades\Log;

class GenerateInstallmentsForPaymentPlans
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
    public function handle(SalesOfferBookingCreated $event)
    {
        // Generate the three default payment plans for the unit.
        $installments = $this->paymentPlanService->generateInstallmentsForPaymentPlan($event->unit, $event->payment_plan);

        // Example: log the IDs of the installments
        Log::info('Default installments generated', [
            'payment_plan_id' => $event->payment_plan->id,
            'installment_ids' => $installments->pluck('id')->all(),
        ]);
    }
}
