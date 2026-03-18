<?php

namespace App\Actions\BrokerCommission;

use App\Enums\BrokerCommissionStatusEnum;
use App\Models\Booking;
use App\Models\BrokerCommission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateBrokerCommissionFromBookedBookingAction
{
    public function __construct(
        protected ResolveBrokerCommissionRateAction $resolveBrokerCommissionRateAction
    ) {
    }

    public function execute(Booking $booking): BrokerCommission
    {
        return DB::transaction(function () use ($booking) {
            $booking->refresh();

            if (!$this->isBookedBooking($booking)) {
                throw new RuntimeException('Broker commission can only be created for booked bookings.');
            }

            if (is_null($booking->sale_source_id)) {
                throw new RuntimeException('This booking has no broker, so no broker commission can be created.');
            }

            $existingCommission = BrokerCommission::query()
                ->where('booking_id', $booking->id)
                ->first();

            if ($existingCommission) {
                return $existingCommission;
            }

            if (is_null($booking->price)) {
                throw new RuntimeException('Booking price is required to calculate broker commission.');
            }

            $eligibleAt = $booking->booked_at ?? now();
            $rate = $this->resolveBrokerCommissionRateAction->execute($eligibleAt);

            $baseAmount = (float) $booking->price;
            $commissionPercentage = (float) $rate->percentage;
            $commissionAmount = round(($baseAmount * $commissionPercentage) / 100, 2);

            return BrokerCommission::query()->create([
                'booking_id' => $booking->id,
                'broker_user_id' => $booking->sale_source_id,
                'commission_rate_id' => $rate->id,
                'commission_percentage' => $commissionPercentage,
                'base_amount' => $baseAmount,
                'commission_amount' => $commissionAmount,
                'paid_amount' => 0,
                'remaining_amount' => $commissionAmount,
                'status' => BrokerCommissionStatusEnum::DUE,
                'eligible_at' => $eligibleAt,
                'calculated_at' => now(),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        });
    }

    protected function isBookedBooking(Booking $booking): bool
    {
        return $booking->status === Booking::STATUS_BOOKED;
    }
}
