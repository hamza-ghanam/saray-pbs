<?php

use App\Models\GeneralSetting;

if (!function_exists('number_to_words')) {
    function number_to_words($number, $locale = 'en')
    {
        $formatter = new \NumberFormatter($locale, \NumberFormatter::SPELLOUT);
        return $formatter->format($number);
    }
}

if (!function_exists('setting')) {
    function setting(string $key, $default = null): mixed
    {
        return app(\App\Services\SettingService::class)->get($key, $default);
    }
}
