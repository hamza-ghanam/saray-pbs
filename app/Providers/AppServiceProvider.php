<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Transport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('brevo', function (array $config) {
            $dsn = $config['dsn']
                ?? config('mail.mailers.brevo.dsn')
                ?? env('MAILER_DSN');
            if (empty($dsn) && env('BREVO_API_KEY')) {
                $dsn = 'brevo+api://' . env('BREVO_API_KEY') . '@default';
            }
            if (empty($dsn)) {
                throw new \InvalidArgumentException('MAIL_DSN is empty. Set MAIL_DSN=brevo+api://<API_KEY>@default in .env');
            }

            return Transport::fromDsn($dsn);
        });
    }
}
