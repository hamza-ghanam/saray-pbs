<?php

if (!function_exists('number_to_words')) {
    function number_to_words($number, $locale = 'en')
    {
        $formatter = new \NumberFormatter($locale, \NumberFormatter::SPELLOUT);
        return $formatter->format($number);
    }
}
