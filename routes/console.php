<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Schedule;
use App\Models\Unit;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    // Find units in "Hold" that have been on hold >= 24 hours
    Unit::where('status', 'Hold')
        ->where('status_changed_at', '<=', now()->subDay())
        ->update([
            'status' => 'Available',
            'hold_created_at' => null,
        ]);
})->hourly();

Schedule::call(function () {
    Unit::whereIn('status', ['Pre-Booked', 'Booked'])
        ->where('status_created_at', '<=', now()->subDays(14))
        ->update([
            'status' => 'Cancelled',
            'status_created_at' => now(),
        ]);
})->daily();
