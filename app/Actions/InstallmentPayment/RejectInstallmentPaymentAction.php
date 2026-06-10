<?php

namespace App\Actions\InstallmentPayment;

use App\Enums\InstallmentPaymentStatusEnum;
use App\Models\InstallmentPayment;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class RejectInstallmentPaymentAction
{
    public function execute(InstallmentPayment $payment, ?string $reason = null): InstallmentPayment
    {
        $payment->refresh();

        if ($payment->status !== InstallmentPaymentStatusEnum::PENDING_VERIFICATION) {
            throw new RuntimeException('Only pending payments can be rejected.');
        }

        $payment->update([
            'status'           => InstallmentPaymentStatusEnum::REJECTED,
            'rejection_reason' => $reason,
            'updated_by'       => Auth::id(),
        ]);

        // No recalculation needed — rejected payments don't affect paid_amount

        return $payment->fresh();
    }
}
