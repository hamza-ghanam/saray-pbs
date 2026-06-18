<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Stichoza\GoogleTranslate\Exceptions\LargeTextException;
use Stichoza\GoogleTranslate\Exceptions\RateLimitException;
use Stichoza\GoogleTranslate\Exceptions\TranslationRequestException;
use Stichoza\GoogleTranslate\GoogleTranslate;

class TranslationService
{
    // 30 days — building names/locations are static
    private const CACHE_TTL = 60 * 60 * 24 * 30;

    public function __construct(
        protected string $target = 'ar',
        protected string $source = 'en'
    ) {}

    public function translate(string $text): string
    {
        $key = $this->cacheKey($text);

        return Cache::remember($key, self::CACHE_TTL, function () use ($text) {
            $tr = new GoogleTranslate($this->target);
            $tr->setSource($this->source);
            return $tr->translate($text);
        });
    }

    /**
     * @throws LargeTextException
     * @throws RateLimitException
     * @throws TranslationRequestException
     */
    public function translateMultiple(array $texts): array
    {
        $keys = array_keys($texts);

        // Return cached values immediately; collect which ones need a real API call
        $out = [];
        $missing = [];

        foreach ($keys as $key) {
            $cacheKey = $this->cacheKey($texts[$key]);
            $cached = Cache::get($cacheKey);

            if ($cached !== null) {
                $out[$key] = $cached;
            } else {
                $missing[$key] = $texts[$key];
            }
        }

        if (empty($missing)) {
            return $out;
        }

        // Single API call for all uncached texts
        $tr = new GoogleTranslate($this->target);
        $tr->setSource($this->source);

        $delimiter = "|||__||__|||";
        $joined = implode($delimiter, array_values($missing));
        $translated = $tr->translate($joined);
        $parts = explode($delimiter, $translated);

        foreach (array_keys($missing) as $i => $key) {
            $value = $parts[$i] ?? null;
            $out[$key] = $value;
            Cache::put($this->cacheKey($missing[$key]), $value, self::CACHE_TTL);
        }

        return $out;
    }

    private function cacheKey(string $text): string
    {
        return "translation:{$this->source}:{$this->target}:" . md5($text);
    }
}
