<?php

namespace App\Services;

use Stichoza\GoogleTranslate\Exceptions\LargeTextException;
use Stichoza\GoogleTranslate\Exceptions\RateLimitException;
use Stichoza\GoogleTranslate\Exceptions\TranslationRequestException;
use Stichoza\GoogleTranslate\GoogleTranslate;

class TranslationService
{
    public function __construct(
        protected string $target = 'ar',
        protected string $source = 'en'
    ) {}

    public function translate(string $text): string
    {
        $tr = new GoogleTranslate($this->target);
        $tr->setSource($this->source);

        return $tr->translate($text);
    }

    /**
     * @throws LargeTextException
     * @throws RateLimitException
     * @throws TranslationRequestException
     */
    public function translateMultiple(array $texts): array
    {
        $tr = new GoogleTranslate($this->target);
        $tr->setSource($this->source);

        // Use a unique delimiter unlikely to appear in normal text
        $delimiter = "|||__||__|||";

        // Join all texts into one string
        $joined = implode($delimiter, $texts);

        // Single API call
        $translated = $tr->translate($joined);

        // Split back into array
        $parts = explode($delimiter, $translated);

        // Re-map to original keys
        $out = [];
        $keys = array_keys($texts);

        foreach ($keys as $i => $key) {
            $out[$key] = $parts[$i] ?? null;
        }

        return $out;
    }
}
