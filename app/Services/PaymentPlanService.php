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
        $blocks = collect($data['blocks']);

        // booking/handover/construction % as before
        $bookingPct = $blocks
            ->filter(fn($b) => str_contains(strtolower($b['description']), 'booking'))
            ->sum('percentage');
        $handoverPct = $data['handover_percentage'];
        $constructionPct = 100 - $bookingPct - $handoverPct;

        return DB::transaction(function () use ($data, $bookingPct, $handoverPct, $constructionPct) {
            return PaymentPlan::create([
                'name' => $data['name'],
                'dld_fee_percentage' => $data['dld_fee_percentage'],
                'admin_fee' => $data['admin_fee'],
                'EOI' => $data['EOI'],
                'blocks' => $data['blocks'],
                'booking_percentage' => $bookingPct,
                'handover_percentage' => $handoverPct,
                'construction_percentage' => $constructionPct,
                'is_default' => false,
            ]);
        });
    }

    public function generateInstallments(
        Unit        $unit,
        PaymentPlan $plan,
        float       $discountPercent = 0.0
    ): Collection
    {
        $price = round($unit->price * (1 - $discountPercent / 100), 2);
        $blocks = $plan->blocks ?? [];
        $insts = collect();
        $base = Carbon::now();
        $firstSingle = false;
        $completionDate = Carbon::parse($unit->building->ecd);

        foreach ($blocks as $block) {
            if ($block['type'] === 'single') {
                $dt = !empty($block['date'])
                    ? Carbon::parse($block['date'])
                    : $this->applyOffset($base, $block['offset']);
            } else {
                // for repeats, we just check the very first occurrence
                $dt = $this->applyOffset($base, $block['start_offset']);
            }

            if ($dt->gt($completionDate)) {
                throw new \InvalidArgumentException(
                    "Block “{$block['description']}” falls after completion date "
                    . $completionDate->toDateString()
                );
            }
        }

        foreach ($blocks as $block) {
            if ($block['type'] === 'single') {
                $dt = !empty($block['date'])
                    ? Carbon::parse($block['date'])
                    : $this->applyOffset($base, $block['offset']);

                $isBooking = !$firstSingle;
                $firstSingle = true;

                $insts->push($this->makeInstallment(
                    $plan,
                    $block,
                    $dt,
                    $isBooking,
                    $price
                ));

            } else {
                // only one repeat-block allowed: auto-calc count to deadline
                $dt = $this->applyOffset($base, $block['start_offset']);
                while ($dt->lte($completionDate)) {
                    $insts->push($this->makeInstallment(
                        $plan,
                        ['description' => $block['description'], 'percentage' => $block['percentage']],
                        $dt,
                        false,
                        $price
                    ));
                    $dt = $this->applyOffset($dt, $block['frequency']);
                }
            }
        }

        // leftover percentage
        $usedPct = $insts->sum('percentage');
        $leftover = 100 - $usedPct - $plan->handover_percentage;
        if ($leftover > 0) {
            $insts->push($this->makeInstallment(
                $plan,
                ['description' => 'Balance at completion', 'percentage' => $leftover],
                $completionDate,
                false,
                $price
            ));
        }

        // 3) Finally, auto-add the handover at completion date
        $insts->push($this->makeInstallment(
            $plan,
            ['description' => 'Handover Installment', 'percentage' => $plan->handover_percentage],
            $completionDate,
            false,
            $price
        ));

        return $insts
            ->sortBy('date')
            ->values();
    }

    /** helper to instantiate & persist one installment */
    protected function makeInstallment(
        PaymentPlan $plan,
        array       $block,
        Carbon      $dt,
        bool        $isBooking,
        float       $price
    )
    {
        $pct = $block['percentage'];
        if ($isBooking) {
            $amount = ($price * $pct / 100)
                + ($price * $plan->dld_fee_percentage / 100)
                + $plan->admin_fee
                - $plan->EOI;
        } else {
            $amount = $price * $pct / 100;
        }

        return $plan->installments()->make([
            'description' => $block['description'],
            'percentage' => $pct,
            'date' => $dt->toDateString(),
            'amount' => round($amount, 2),
        ]);
    }

    /** helper to add offset to a base date */
    protected function applyOffset(Carbon $base, array $offset): Carbon
    {
        $dt = $base->copy();
        if (!empty($offset['years'])) $dt->addYears($offset['years']);
        if (!empty($offset['months'])) $dt->addMonths($offset['months']);
        return $dt;
    }


}
