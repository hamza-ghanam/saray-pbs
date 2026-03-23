<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\PaymentPlan;
use Illuminate\Console\Command;

class MigrateEoiToBookings extends Command
{
    protected $signature = 'eoi:migrate-to-bookings {--dry-run : Preview changes without updating data}';
    protected $description = 'Copy EOI from payment plans to bookings.eoi_amount, then nullify payment_plans.EOI';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun
            ? 'Starting EOI migration in dry-run mode...'
            : 'Starting EOI migration...'
        );

        $affectedPlanIds = [];
        $updatedCount = 0;
        $skippedCount = 0;

        $bookings = Booking::whereNotNull('payment_plan_id')
            ->with('paymentPlan')
            ->get();

        foreach ($bookings as $booking) {
            if (is_null($booking->payment_plan_id) || !$booking->paymentPlan) {
                $skippedCount++;
                continue;
            }

            if (is_null($booking->paymentPlan->EOI)) {
                $skippedCount++;
                continue;
            }

            if (!is_null($booking->eoi_amount)) {
                $skippedCount++;
                continue;
            }

            $booking->eoi_amount = $booking->paymentPlan->EOI;

            if (!$dryRun) {
                $booking->save();
            }

            $affectedPlanIds[] = $booking->payment_plan_id;
            $updatedCount++;
        }

        if (!$dryRun && !empty($affectedPlanIds)) {
            PaymentPlan::whereIn('id', array_values($affectedPlanIds))
                ->update([
                    'EOI' => 0,
                ]);
        }

        $this->info("Bookings updated: {$updatedCount}");
        $this->info("Bookings skipped: {$skippedCount}");
        $this->info("Affected plans: " . implode(',', $affectedPlanIds));

        if ($dryRun) {
            $this->warn('Dry-run mode: no data was changed.');
        } else {
            $this->info('EOI migration completed successfully.');
        }

        return self::SUCCESS;
    }
}
