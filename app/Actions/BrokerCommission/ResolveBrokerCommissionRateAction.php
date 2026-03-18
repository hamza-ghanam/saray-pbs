<?php

namespace App\Actions\BrokerCommission;

use App\Models\BrokerCommissionRate;
use Carbon\CarbonInterface;
use RuntimeException;

class ResolveBrokerCommissionRateAction
{
    public function execute(?CarbonInterface $at = null): BrokerCommissionRate
    {
        $at ??= now();

        $rate = BrokerCommissionRate::query()
            ->where('effective_from', '<=', $at)
            ->where(function ($query) use ($at) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $at);
            })
            ->orderByDesc('effective_from')
            ->first();

        if (!$rate) {
            throw new RuntimeException('No effective broker commission rate found for the given date.');
        }

        return $rate;
    }
}
