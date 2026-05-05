<?php
use App\Models\Unit;
use App\Models\User;
use App\Services\FCMService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Symfony\Component\HttpFoundation\Response;

// Artisan "inspire" command remains unchanged.
Artisan::command('inspire', function () {
    $this->comment(\Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote');

if (!function_exists('getCeoTokens')) {
    /**
     * Retrieve device tokens for all users with the "CEO" role.
     *
     * @return array
     */
    function getCeoTokens(): array {
        $ceoUsers = \App\Models\User::role('CEO')->with('deviceTokens')->get();
        return $ceoUsers->pluck('deviceTokens')
            ->flatten()
            ->pluck('token')
            ->toArray();
    }
}

if (!function_exists('getUnitCreatorTokens')) {
    /**
     * Retrieve device tokens for the user who created the given unit.
     *
     * @param \App\Models\Unit $unit
     * @return array
     */
    function getUnitCreatorTokens(\App\Models\Unit $unit): array {
        return $unit->user ? $unit->user->deviceTokens->pluck('token')->toArray() : [];
    }
}

//// TEST: Write dummy data into approvals table every minute
//Schedule::call(function () {
//    DB::table('approvals')->insert([
//        'ref_id'        => rand(1000, 9999),
//        'ref_type'      => collect(['Unit', 'Booking'])->random(),
//        'approved_by'   => 3,
//        'approval_type' => collect(['CSO', 'Accountant', 'CFO', 'CEO'])->random(),
//        'status'        => collect(['Pending', 'Approved', 'Rejected'])->random(),
//        'created_at'    => now(),
//        'updated_at'    => now(),
//    ]);
//})->everyMinute();

// Scheduled task for cancelling hold (hourly)
Schedule::call(function () {
    // Retrieve units that are on "Hold" for at least 24 hours.
    $units = Unit::where('status', Unit::STATUS_HOLD)
        ->where('status_changed_at', '<=', now()->subDay())
        ->get();

    $fcmService = app(FCMService::class);
    $ceoTokens = getCeoTokens();

    foreach ($units as $unit) {
        // Update the unit status to "Available" and reset hold_created_at.
        $unit->update([
            'status' => Unit::STATUS_AVAILABLE,
            'hold_created_at' => null,
        ]);

        $holding = $unit->holdings()
            ->whereNotIn('status', ['Cancelled', 'Rejected'])
            ->latest()
            ->first();

        if ($holding) {
            $holding->update(['status' => 'Cancelled']);
        }

        $creatorTokens = getUnitCreatorTokens($unit);
        $deviceTokens = array_merge($ceoTokens, $creatorTokens);

        $title = "Holding Cancelled";
        $body = "Unit ID: {$unit->id} holding has been cancelled after 24 hours.";
        $data = [
            'unit_id'    => (string)$unit->id,
            // Ensure that $unit->booking is available; adjust as needed.
            'booking_id' => $unit->booking ? (string)$unit->booking->id : null,
            'timestamp'  => now()->toIso8601String(),
        ];
        $fcmService->sendPushNotification($deviceTokens, $title, $body, $data);
    }
})->hourly();

// Scheduled task for releasing Pre-Hold units after 1 week (hourly)
Schedule::call(function () {
    $units = Unit::where('status', Unit::STATUS_PRE_HOLD)
        ->where('status_changed_at', '<=', now()->subWeek())
        ->get();

    $fcmService = app(FCMService::class);
    $ceoTokens = getCeoTokens();

    foreach ($units as $unit) {
        $unit->update([
            'status'            => Unit::STATUS_AVAILABLE,
            'status_changed_at' => now(),
        ]);

        $holding = $unit->holdings()
            ->whereNotIn('status', ['Cancelled', 'Rejected'])
            ->latest()
            ->first();

        if ($holding) {
            $holding->update(['status' => 'Cancelled']);
        }

        $creatorTokens = getUnitCreatorTokens($unit);
        $deviceTokens  = array_merge($ceoTokens, $creatorTokens);

        $fcmService->sendPushNotification(
            $deviceTokens,
            'Pre-Hold Released',
            "Unit ID: {$unit->id} has been released back to Available after 1 week in Pre-Hold.",
            [
                'unit_id'   => (string) $unit->id,
                'timestamp' => now()->toIso8601String(),
            ]
        );
    }
})->everyFiveMinutes()->name('pre-hold-units');

// Scheduled task for cancelling booking (daily)
Schedule::call(function () {
    // Retrieve units in Pre-Booked or Booked status for 14 days or more.
    $units = Unit::whereIn('status', ['Pre-Booked', 'Booked'])
        ->where('status_created_at', '<=', now()->subDays(14))
        ->get();

    $fcmService = app(\App\Services\FCMService::class);
    $ceoTokens = getCeoTokens();

    foreach ($units as $unit) {
        // Update the unit status to "Cancelled" and update the timestamp.
        $unit->update([
            'status' => Unit::STATUS_CANCELLED,
            'status_created_at' => now(),
        ]);

        // Find the most recent non-cancelled booking and mark it as cancelled.
        $booking = $unit->bookings()
            ->where('status', '!=', 'Cancelled')
            ->latest()
            ->first();

        if ($booking) {
            $booking->update(['status' => 'Cancelled']);
        }

        $creatorTokens = getUnitCreatorTokens($unit);
        $deviceTokens = array_merge($ceoTokens, $creatorTokens);

        $title = "Booking Cancelled";
        $body = "Unit ID: {$unit->id} has been cancelled after 14 days.";
        $data = [
            'unit_id'    => (string)$unit->id,
            'booking_id' => $unit->booking ? (string)$unit->booking->id : null,
            'timestamp'  => now()->toIso8601String(),
        ];
        $fcmService->sendPushNotification($deviceTokens, $title, $body, $data);
    }
})->daily();
