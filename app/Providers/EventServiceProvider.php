<?php

namespace App\Providers;

use App\Events\UnitCreated;
use App\Listeners\GenerateInstallmentsForPaymentPlans;
use App\Listeners\GeneratePaymentPlansForUnit;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UnitCreated::class => [
            GeneratePaymentPlansForUnit::class,
            GenerateInstallmentsForPaymentPlans::class,
        ],
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
