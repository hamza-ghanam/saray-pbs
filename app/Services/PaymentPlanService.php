<?php

namespace App\Services;

use App\Models\PaymentPlan;
use App\Models\Installment;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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
            ->filter(fn($b) => str_contains(strtolower(str_replace(' ', '', $b['description'])), 'downpayment'))
            ->sum('percentage');
        $handoverPct = $data['handover_percentage'];

        return DB::transaction(function () use ($data, $bookingPct, $handoverPct) {
            return PaymentPlan::create([
                'name' => $data['name'],
                'dld_fee_percentage' => $data['dld_fee_percentage'],
                'admin_fee' => $data['admin_fee'],
                'blocks' => $data['blocks'],
                'booking_percentage' => $bookingPct,
                'handover_percentage' => $handoverPct,
                'post_handover_enabled' => (bool) ($data['post_handover_enabled'] ?? false),
                'post_handover_months' => (int) ($data['post_handover_months'] ?? 0),
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
        $handoverPct = (float) $plan->handover_percentage;
        $postHandoverEnabled = (bool) ($plan->post_handover_enabled ?? false);
        $postHandoverMonths = (int) ($plan->post_handover_months ?? 0);

        foreach ($blocks as $block) {
            if ($block['type'] === 'single') {
                $dt = !empty($block['date'])
                    ? Carbon::parse($block['date'])
                    : $this->applyOffset($base, $block['offset'] ?? []);
            } else {
                $dt = $this->applyOffset($base, $block['start_offset'] ?? []);
            }

            if ($dt->gt($completionDate)) {
                throw new \InvalidArgumentException(
                    "Block \"{$block['description']}\" falls after completion date "
                    . $completionDate->toDateString()
                );
            }
        }

        $singlesPct = collect($blocks)
            ->where('type', 'single')
            ->sum('percentage');

        if ($singlesPct + $handoverPct - 100 > 1e-9) {
            throw new \InvalidArgumentException(
                "Singles total percentage ({$singlesPct}%) plus handover percentage ({$handoverPct}%) exceeds 100%."
            );
        }

        if ($postHandoverEnabled && $postHandoverMonths < 1) {
            throw new InvalidArgumentException(
                'Post-handover months must be at least 1 when post-handover is enabled.'
            );
        }

        $usedPct = 0.0;

        foreach ($blocks as $block) {
            if ($block['type'] !== 'single') {
                continue;
            }

            $processed = strtolower(str_replace(' ', '', $block['description']));
            if ($processed === 'downpayment') {
                $dt = Carbon::now();
            } else {
                $dt = !empty($block['date'])
                    ? Carbon::parse($block['date'])
                    : $this->applyOffset($base, $block['offset'] ?? []);
            }

            $isBooking = !$firstSingle;
            $firstSingle = true;

            $inst = $this->makeInstallment(
                $plan,
                $block,
                $dt,
                $isBooking,
                $price
            );

            $insts->push($inst);
            $usedPct += (float) $inst->percentage;
        }

        foreach ($blocks as $block) {
            if ($block['type'] !== 'repeat') {
                continue;
            }

            $dt = $this->applyOffset($base, $block['start_offset'] ?? []);
            $blockPct = (float) $block['percentage'];
            $repeatIndex = 1;

            while ($dt->lt($completionDate)) {
                $remainingPct = 100 - $handoverPct - $usedPct;

                if ($remainingPct <= 1e-9) {
                    break;
                }

                $thisPct = min($blockPct, $remainingPct);

                if ($thisPct < 1e-6) {
                    break;
                }

                $inst = $this->makeInstallment(
                    $plan,
                    [
                        'description' => $block['description'] . ' ' . $repeatIndex,
                        'percentage' => $thisPct,
                    ],
                    $dt,
                    false,
                    $price
                );
                $insts->push($inst);
                $usedPct += (float) $inst->percentage;
                $repeatIndex++;

                if ($thisPct + 1e-9 < $blockPct) {
                    break;
                }

                $dt = $this->applyOffset($dt, $block['frequency'] ?? []);
            }
        }

        $remainingPct = 100 - $usedPct - $handoverPct;
        if ($remainingPct < -1e-9) {
            throw new \InvalidArgumentException(
                "Generated installments exceed 100% after reserving handover percentage ({$handoverPct}%)."
            );
        }

        if (!$postHandoverEnabled && $remainingPct > 1e-9) {
            $insts->push($this->makeInstallment(
                $plan,
                ['description' => 'Balance at completion', 'percentage' => $remainingPct],
                $completionDate,
                false,
                $price
            ));
            $usedPct += $remainingPct;
            $remainingPct = 0.0;
        }

        $insts->push($this->makeInstallment(
            $plan,
            ['description' => 'Handover Installment', 'percentage' => $handoverPct],
            $completionDate,
            false,
            $price
        ));

        if ($postHandoverEnabled && $remainingPct > 1e-9) {
            $insts = $insts->merge(
                $this->generatePostHandoverInstallments(
                    $plan,
                    $price,
                    $completionDate,
                    $remainingPct,
                    $postHandoverMonths
                )->all()
            );
        }

        return $insts
            ->sortBy('date')
            ->values();
    }

    protected function generatePostHandoverInstallments(
        PaymentPlan $plan,
        float $price,
        Carbon $completionDate,
        float $remainingPct,
        int $months
    ): Collection
    {
        $installments = collect();
        $distributedPct = 0.0;

        for ($i = 1; $i <= $months; $i++) {
            $dt = $completionDate->copy()->addMonthsNoOverflow($i);

            if ($i < $months) {
                $pct = round($remainingPct / $months, 8);
                $distributedPct += $pct;
            } else {
                $pct = round($remainingPct - $distributedPct, 8);
            }

            if ($pct <= 1e-9) {
                continue;
            }

            $installments->push($this->makeInstallment(
                $plan,
                [
                    'description' => 'Post-Handover Installment ' . $i,
                    'percentage' => $pct,
                ],
                $dt,
                false,
                $price
            ));
        }

        return $installments;
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
        $pct = (float) $block['percentage'];
        if ($isBooking) {
            $amount = ($price * $pct / 100)
                + ($price * $plan->dld_fee_percentage / 100)
                + $plan->admin_fee;
        } else {
            $amount = $price * $pct / 100;
        }

        return $plan->installments()->make([
            'description' => $block['description'],
            'percentage' => round($pct, 8),
            'date' => $dt->toDateString(),
            'amount' => round($amount, 2),
        ]);
    }

    /** helper to add offset to a base date */
    protected function applyOffset(Carbon $base, array $offset): Carbon
    {
        $dt = $base->copy();

        if (!empty($offset['years'])) {
            $dt->addYearsNoOverflow($offset['years']);
        }

        if (!empty($offset['months'])) {
            $dt->addMonthsNoOverflow($offset['months']);
        }

        return $dt;
    }


}
