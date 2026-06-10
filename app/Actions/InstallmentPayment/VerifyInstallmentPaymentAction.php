<?php

namespace App\Actions\InstallmentPayment;

use App\Enums\InstallmentPaymentStatusEnum;
use App\Models\InstallmentPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VerifyInstallmentPaymentAction
{
    public function execute(InstallmentPayment $payment): InstallmentPayment
    {
        return DB::transaction(function () use ($payment) {
            $payment->refresh();

            if ($payment->status !== InstallmentPaymentStatusEnum::PENDING_VERIFICATION) {
                throw new RuntimeException('Only pending payments can be verified.');
            }

            $payment->update([
                'status'      => InstallmentPaymentStatusEnum::VERIFIED,
                'verified_by' => Auth::id(),
                'verified_at' => now(),
                'updated_by'  => Auth::id(),
            ]);

            // Recalculate paid_amount from all verified payments to ensure accuracy
            $installment = $payment->installment;

            $totalVerified = $installment->payments()
                ->where('status', InstallmentPaymentStatusEnum::VERIFIED)
                ->sum('amount');

            $installment->paid_amount = round((float) $totalVerified, 2);
            $installment->recalculateStatus();

            return $payment->fresh();
        });
    }
}
