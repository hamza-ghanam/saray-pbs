<?php

namespace App\Services;

use App\Models\PaymentPlan;
use App\Models\Installment;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PaymentPlanService
{
    // Default Plan Params
    protected string $planName = '60/40';
    protected int $oneMonthPercentage = 10;
    protected int $bookingPercentage     = 10;
    protected int $constructionPercentage= 30;
    protected int $milestonePercentage   = 10;
    protected int $handoverPercentage    = 40;

    /**
     * Generate the three default payment plans (70/30, 50/50, 60/40)
     * for a given unit.
     *
     * @param Unit $unit
     * @return void
     */

    public function generateDefaultPlansForUnit(Unit $unit)
    {
        // ——— Your New Custom Plan ———
        // 10% booking, 10% at 1 month, 1% × 30 months, 10% at 1-year milestone, 40% handover

        // ——— Financial & Date setup ———
        $price          = $unit->price;
        $dldFee         = $price * ($unit->dld_fee_percentage / 100);
        $adminFee       = $unit->admin_fee;
        $EOI            = $unit->EOI ?? 0;

        // ——— Create the PaymentPlan summary record ———
        PaymentPlan::create([
            'unit_id'                         => $unit->id,
            'name'                            => $this->planName,
            'selling_price'                   => $price,
            'dld_fee_percentage'              => $unit->dld_fee_percentage,
            'dld_fee'                         => $dldFee,
            'admin_fee'                       => $adminFee,
            'discount'                        => $unit->discount ?? 0,
            'EOI'                             => $EOI,
            // aggregate deposit is 10 % + 10 % = 20 %
            'booking_percentage'              => $this->bookingPercentage + $this->oneMonthPercentage,
            'construction_percentage'         => $this->constructionPercentage,
            'handover_percentage'             => $this->handoverPercentage,
            'first_construction_installment_date' => null,
            'isDefault'                       => true,
        ]);

        /** Insallments are generated on sales offer / booking only*/
    }

    /**
     * Generate the installments of a payment plan
     * for a given unit.
     *
     * @param Unit $unit
     * @param PaymentPlan $paymentPlan
     * @return Collection
     */

    public function generateInstallmentsForPaymentPlan(Unit $unit, PaymentPlan $paymentPlan): Collection
    {
        $createdInstallments = collect();

        $price               = $paymentPlan->selling_price;
        $bookingPercentage   = $paymentPlan->booking_percentage - $this->oneMonthPercentage;
        $dldFee              = $paymentPlan->dld_fee;
        $adminFee            = $paymentPlan->admin_fee;
        $EOI                 = $paymentPlan->EOI;

        $today               = Carbon::now();
        $bookingDate         = $today;
        $oneMonthDate        = $today->copy()->addMonth();
        // construction starts *after* the 1-month payment:
        $firstConstructionDate = $today->copy()->addMonths(2);

        // 1) Booking deposit (Day 0)
        $bookingAmount = ($price * ($bookingPercentage / 100))
            + $dldFee
            + $adminFee
            - $EOI;

        $createdInstallments->push(Installment::make([
            'payment_plan_id' => $paymentPlan->id,
            'description'     => 'Booking Installment',
            'percentage'      => $bookingPercentage,
            'date'            => $bookingDate->toDateString(),
            'amount'          => round($bookingAmount, 2),
        ]));

        // 2) 1-month post-booking payment
        $oneMonthAmount = $price * ($this->oneMonthPercentage / 100);
        $createdInstallments->push(Installment::make([
            'payment_plan_id' => $paymentPlan->id,
            'description'     => '1-Month Post-Booking Installment',
            'percentage'      => $this->oneMonthPercentage,
            'date'            => $oneMonthDate->toDateString(),
            'amount'          => round($oneMonthAmount, 2),
        ]));

        // 3) Construction installments: 30 × 1%
        $constructionTotal = $price * ($this->constructionPercentage / 100);
        $monthlyAmount     = $constructionTotal / 30;
        for ($i = 0; $i < $this->constructionPercentage; $i++) {
            $installmentDate = $firstConstructionDate->copy()->addMonths($i);
            $createdInstallments->push(Installment::make([
                'payment_plan_id' => $paymentPlan->id,
                'description'     => 'Construction Installment ' . ($i + 1),
                'percentage'      => round($this->constructionPercentage / 30, 2), // = 1%
                'date'            => $installmentDate->toDateString(),
                'amount'          => round($monthlyAmount, 2),
            ]));
        }

        // 4) 1-year milestone payment (during construction)
        $milestoneDate   = $firstConstructionDate->copy()->addYear();
        $milestoneAmount = $price * ($this->milestonePercentage / 100);
        $createdInstallments->push(Installment::make([
            'payment_plan_id' => $paymentPlan->id,
            'description'     => '1-Year Milestone Payment',
            'percentage'      => $this->milestonePercentage,
            'date'            => $milestoneDate->toDateString(),
            'amount'          => round($milestoneAmount, 2),
        ]));

        // 5) Final handover balance: one month after the milestone
        $handoverDate   = $firstConstructionDate->copy()->addMonths($this->constructionPercentage);
        $handoverAmount = $price * ($this->handoverPercentage / 100);
        $createdInstallments->push(Installment::make([
            'payment_plan_id' => $paymentPlan->id,
            'description'     => 'Handover Installment',
            'percentage'      => $this->handoverPercentage,
            'date'            => $handoverDate->toDateString(),
            'amount'          => round($handoverAmount, 2),
        ]));

        return $createdInstallments
            ->sortBy('date')
            ->values();
    }
}
