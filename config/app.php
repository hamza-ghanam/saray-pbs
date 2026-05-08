<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),
    'frontend_url' => env('FEND_URL', ''),
    'app_version' => env('APP_VERSION', '2.0'),

    'tenant_name' => env('TENANT_NAME', 'N/A'),
    'tenant_name_ar' => env('TENANT_NAME_AR', 'N/A'),
    'tenant_license' => env('TENANT_LICENSE', 'N/A'),
    'tenant_dld' => env('TENANT_DLD', 'N/A'),
    'tenant_email' => env('TENANT_EMAIL', 'N/A'),
    'tenant_po_box' => env('TENANT_PO_BOX', 'N/A'),
    'tenant_address' => env('TENANT_ADDRESS', 'N/A'),
    'tenant_address_ar' => env('TENANT_ADDRESS_AR', 'N/A'),
    'tenant_phone' => env('TENANT_PHONE', 'N/A'),

    'tenant_account_name' => env('TENANT_ACCOUNT_NAME', 'N/A'),
    'tenant_account_bank' => env('TENANT_ACCOUNT_BANK', 'N/A'),
    'tenant_account_branch' => env('TENANT_ACCOUNT_BRANCH', 'N/A'),
    'tenant_account_number' => env('TENANT_ACCOUNT_NUMBER', 'N/A'),
    'tenant_account_iban' => env('TENANT_ACCOUNT_IBAN', 'N/A'),
    'tenant_account_swift' => env('TENANT_ACCOUNT_SWIFT', 'N/A'),

    'tenant_escrow_account_name' => env('TENANT_ESCROW_ACCOUNT_NAME', 'N/A'),
    'tenant_escrow_account_bank' => env('TENANT_ESCROW_ACCOUNT_BANK', 'N/A'),
    'tenant_escrow_account_branch' => env('TENANT_ESCROW_ACCOUNT_BRANCH', 'N/A'),
    'tenant_escrow_account_number' => env('TENANT_ESCROW_ACCOUNT_NUMBER', 'N/A'),
    'tenant_escrow_account_iban' => env('TENANT_ESCROW_ACCOUNT_IBAN', 'N/A'),
    'tenant_escrow_account_bic' => env('TENANT_ESCROW_ACCOUNT_BIC', 'N/A'),


    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => 'Asia/Dubai',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],
];
