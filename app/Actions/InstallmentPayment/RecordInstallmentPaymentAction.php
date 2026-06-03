<?php

namespace App\Actions\InstallmentPayment;

use App\Enums\InstallmentPaymentStatusEnum;
use App\Enums\InstallmentStatusEnum;
use App\Models\Installment;
use App\Models\InstallmentPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class RecordInstallmentPaymentAction
{
    public function execute(
        Installment $installment,
        float $amount,
        string $paymentDate,
        ?string $paymentMethod = null,
        ?string $referenceNumber = null,
        ?string $receiptPath = null,
        ?string $notes = null,
    ): InstallmentPayment {
        return DB::transaction(function () use (
            $installment,
            $amount,
            $paymentDate,
            $paymentMethod,
            $referenceNumber,
            $receiptPath,
            $notes,
        ) {
            $installment->refresh();

            if ($installment->status === InstallmentStatusEnum::WAIVED) {
                throw new RuntimeException('Cannot record payment for a waived installment.');
            }

            if ($installment->status === InstallmentStatusEnum::PAID) {
                throw new RuntimeException('This installment is already fully paid.');
            }

            if ($amount <= 0) {
                throw new InvalidArgumentException('Payment amount must be greater than zero.');
            }

            $pendingAmount = $installment->payments()
                ->where('status', InstallmentPaymentStatusEnum::PENDING_VERIFICATION)
                ->sum('amount');

            $availableAmount = round($installment->remaining_amount - (float) $pendingAmount, 2);

            if ($amount > $availableAmount) {
                throw new InvalidArgumentException('Payment amount cannot exceed the remaining installment amount.');
            }

            return InstallmentPayment::create([
                'installment_id'  => $installment->id,
                'booking_id'      => $installment->booking_id,
                'payment_date'    => $paymentDate,
                'amount'          => round($amount, 2),
                'payment_method'  => $paymentMethod,
                'reference_number'=> $referenceNumber,
                'receipt_path'    => $receiptPath,
                'status'          => InstallmentPaymentStatusEnum::PENDING_VERIFICATION,
                'notes'           => $notes,
                'created_by'      => Auth::id(),
                'updated_by'      => Auth::id(),
            ]);

            // Note: installment.paid_amount is NOT updated here.
            // It is only updated upon verification (VerifyInstallmentPaymentAction).
        });
    }
}
