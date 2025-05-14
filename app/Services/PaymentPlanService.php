<?php

namespace App\Services;

use App\Models\PaymentPlan;
use App\Models\Installment;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentPlanService
{
    /**
     * Persist the plan header (including blocks JSON). Main and only!
    */
    public function createFromDefinition(array $data): PaymentPlan
    {
        // 1) Derive summary percentages from blocks
        $blocks = collect($data['blocks']);

        // booking = sum of all single blocks whose description mentions “booking”
        $bookingPct = $blocks
            ->filter(fn($b) => $b['type']==='single' && str_contains(strtolower($b['description']), 'booking'))
            ->sum('percentage');

        // handover = sum of all single blocks whose description mentions “handover”
        $handoverPct = $blocks
            ->filter(fn($b) => $b['type']==='single' && str_contains(strtolower($b['description']), 'handover'))
            ->sum('percentage');

        // construction = the rest
        $constructionPct = 100 - $bookingPct - $handoverPct;

        return DB::transaction(function () use ($handoverPct, $constructionPct, $bookingPct, $data) {
            return PaymentPlan::create([
                'name'                            => $data['name'],
                'dld_fee_percentage'              => $data['dld_fee_percentage'],
                'admin_fee'                       => $data['admin_fee'],
                'EOI'                             => $data['EOI'],
                'blocks'                          => $data['blocks'],
                'booking_percentage'              => $bookingPct,
                'construction_percentage'         => $constructionPct,
                'handover_percentage'             => $handoverPct,
                'is_default'                      => false,
            ]);
        });
    }

    public function generateInstallments(
        Unit $unit,
        PaymentPlan $plan,
        float $discountPercent = 0.0
    ): Collection {
        $basePrice      = $unit->price;
        $price          = round($basePrice * (1 - $discountPercent / 100), 2);

        $blocks = $plan->blocks ?? [];

        $insts = collect();
        $base  = Carbon::now();
        $firstProcessed = false;

        foreach ($blocks as $block) {
            if ($block['type'] === 'single') {
                $dt = !empty($block['date'])
                    ? Carbon::parse($block['date'])
                    : $this->applyOffset($base, $block['offset']);

                // determine if this is the booking deposit (first single block)
                $isBooking = !$firstProcessed;
                $firstProcessed = true;

                $insts->push($this->makeInstallment($plan, $block, $dt, $isBooking, $price));
            } else {
                $dt = $this->applyOffset($base, $block['start_offset']);
                for ($i = 0; $i < $block['count']; $i++) {
                    $desc = "{$block['description']} #".($i+1);
                    $insts->push($this->makeInstallment(
                        $plan,
                        ['description'=>$desc,'percentage'=>$block['percentage']],
                        $dt,
                        false,
                        $price
                    ));
                    $dt = $this->applyOffset($dt, $block['frequency']);
                }
            }
        }

        return $insts->sortBy('date')->values();
    }

    /** helper to instantiate & persist one installment */
    protected function makeInstallment(
        PaymentPlan $plan,
        array $block,
        Carbon $dt,
        bool $isBooking,
        float $price
    ): Installment {
        $percentage  = $block['percentage'];

        if ($isBooking) {
            // full booking‐deposit formula
            $amount = ($price * ($percentage / 100))
                + $plan->dld_fee
                + $plan->admin_fee
                - $plan->EOI;
        } else {
            // plain percentage of price
            $amount = $price * ($percentage / 100);
        }

        return $plan->installments()->make([
            'description' => $block['description'],
            'percentage'  => $percentage,
            'date'        => $dt->toDateString(),
            'amount'      => round($amount, 2),
        ]);
    }

    /** helper to add offset to a base date */
    protected function applyOffset(Carbon $base, array $offset): Carbon
    {
        if (!empty($offset['years']))  {
            $base = $base->copy()->addYears($offset['years']);
        }

        if (!empty($offset['months'])) {
            $base = $base->copy()->addMonths($offset['months']);
        }
        return $base;
    }


}
