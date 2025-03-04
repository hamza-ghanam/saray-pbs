<?php

namespace App\Services;

use App\Models\PaymentPlan;
use App\Models\Installment;
use App\Models\Unit;
use Carbon\Carbon;

class PaymentPlanService
{
    /**
     * Generate the three default payment plans (70/30, 50/50, 60/40)
     * for a given unit.
     *
     * @param Unit $unit
     * @return void
     */
    public function generateDefaultPlansForUnit(Unit $unit)
    {
        // Define the default plan percentages.
        // The percentages mean:
        // - booking_percentage: fixed at 20% for all plans.
        // - handover_percentage: paid at completion (from unit->completion_date).
        // - construction_percentage: remainder (100% - booking - handover).
        $defaultPlans = [
            '70/30' => [
                'booking_percentage' => 20,
                'handover_percentage' => 30,
                'construction_percentage' => 50,
            ],
            '50/50' => [
                'booking_percentage' => 20,
                'handover_percentage' => 50,
                'construction_percentage' => 30,
            ],
            '60/40' => [
                'booking_percentage' => 20,
                'handover_percentage' => 40,
                'construction_percentage' => 40,
            ],
        ];

        // Retrieve unit financial data. (Ensure these fields exist or adjust accordingly.)
        $price = $unit->price;
        $dldFee = $unit->dld_fee;
        $adminFee = $unit->admin_fee;
        $EOI = $unit->EOI ?? 100000; // Default EOI if not set.
        $completionDate = Carbon::parse($unit->completion_date);

        // Use the unit's provided first_construction_installment_date if available;
        // otherwise, default to 3 months from now.
        $firstConstructionDate = $unit->first_construction_installment_date
            ? Carbon::parse($unit->first_construction_installment_date)
            : Carbon::now()->addMonths(3);

        foreach ($defaultPlans as $planName => $data) {
            // Persist the PaymentPlan summary record.
            $paymentPlan = PaymentPlan::create([
                'unit_id' => $unit->id,
                'name' => $planName,
                'selling_price' => $price,
                'dld_fee' => $dldFee,
                'admin_fee' => $adminFee,
                'discount' => $unit->discount ?? 0,
                'EOI' => $EOI,
                'booking_percentage' => $data['booking_percentage'],
                'handover_percentage' => $data['handover_percentage'],
                'construction_percentage' => $data['construction_percentage'],
                'first_construction_installment_date' => $firstConstructionDate->toDateString(),
            ]);

            // 1. Booking installment:
            // Formula: (price * booking_percentage/100) + dld_fee + admin_fee - EOI
            $bookingAmount = ($price * ($data['booking_percentage'] / 100)) + $dldFee + $adminFee - $EOI;
            Installment::create([
                'payment_plan_id' => $paymentPlan->id,
                'description' => 'Booking Installment',
                'percentage' => $data['booking_percentage'],
                'date' => Carbon::now()->toDateString(),
                'amount' => round($bookingAmount, 2),
            ]);

            // 2. Construction installments:
            // Total construction amount = price * construction_percentage/100
            $constructionTotal = $price * ($data['construction_percentage'] / 100);

            // Calculate the number of months between first construction date and completion date.
            // Determine the number of months between the first construction installment date and completion date.
            $months = $firstConstructionDate->diffInMonths($completionDate) + 1;

            // Monthly installment amount:
            $monthlyAmount = $constructionTotal / $months;

            // Create monthly installments.
            for ($i = 0; $i < $months; $i++) {
                $installmentDate = $firstConstructionDate->copy()->addMonths($i);
                Installment::create([
                    'payment_plan_id' => $paymentPlan->id,
                    'description' => 'Construction Installment ' . ($i + 1),
                    'percentage' => round($data['construction_percentage'] / $months, 2),
                    'date' => $installmentDate->toDateString(),
                    'amount' => round($monthlyAmount, 2),
                ]);
            }

            // 3. Handover installment (paid on completion date):
            $handoverAmount = $price * ($data['handover_percentage'] / 100);
            Installment::create([
                'payment_plan_id' => $paymentPlan->id,
                'description' => 'Handover Installment',
                'percentage' => $data['handover_percentage'],
                'date' => $completionDate->toDateString(),
                'amount' => round($handoverAmount, 2),
            ]);
        }
    }
}
