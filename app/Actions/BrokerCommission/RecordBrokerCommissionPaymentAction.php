<?php

namespace App\Actions\BrokerCommission;

use App\Enums\BrokerCommissionStatusEnum;
use App\Models\BrokerCommission;
use App\Models\BrokerCommissionPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class RecordBrokerCommissionPaymentAction
{
    public function execute(
        BrokerCommission $brokerCommission,
        float $amount,
        string $paymentDate,
        ?string $paymentMethod = null,
        ?string $referenceNumber = null,
        ?string $receiptPath = null,
        ?string $notes = null,
        ?int $paidBy = null
    ): BrokerCommissionPayment {
        return DB::transaction(function () use (
            $brokerCommission,
            $amount,
            $paymentDate,
            $paymentMethod,
            $referenceNumber,
            $receiptPath,
            $notes,
            $paidBy
        ) {
            $brokerCommission->refresh();

            if ($brokerCommission->status === BrokerCommissionStatusEnum::CANCELLED) {
                throw new RuntimeException('Cannot record payment for a cancelled broker commission.');
            }

            if ($brokerCommission->status === BrokerCommissionStatusEnum::PAID) {
                throw new RuntimeException('This broker commission is already fully paid.');
            }

            if ($amount <= 0) {
                throw new InvalidArgumentException('Payment amount must be greater than zero.');
            }

            $remainingAmount = (float) $brokerCommission->remaining_amount;

            if ($amount > $remainingAmount) {
                throw new InvalidArgumentException('Payment amount cannot exceed the remaining commission amount.');
            }

            $payment = BrokerCommissionPayment::query()->create([
                'broker_commission_id' => $brokerCommission->id,
                'payment_date' => $paymentDate,
                'amount' => round($amount, 2),
                'payment_method' => $paymentMethod,
                'reference_number' => $referenceNumber,
                'receipt_path' => $receiptPath,
                'notes' => $notes,
                'paid_by' => $paidBy ?? Auth::id(),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $newPaidAmount = round(((float) $brokerCommission->paid_amount) + $amount, 2);
            $newRemainingAmount = round(((float) $brokerCommission->commission_amount) - $newPaidAmount, 2);

            $newStatus = match (true) {
                $newRemainingAmount <= 0 => BrokerCommissionStatusEnum::PAID,
                $newPaidAmount > 0 => BrokerCommissionStatusEnum::PARTIALLY_PAID,
                default => BrokerCommissionStatusEnum::DUE,
            };

            $brokerCommission->update([
                'paid_amount' => $newPaidAmount,
                'remaining_amount' => max($newRemainingAmount, 0),
                'status' => $newStatus,
                'updated_by' => Auth::id(),
            ]);

            return $payment->fresh();
        });
    }
}
