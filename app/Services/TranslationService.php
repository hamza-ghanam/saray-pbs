<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Stichoza\GoogleTranslate\GoogleTranslate;

class TranslationService
{
    // 30 days — building names/locations are static
    private const CACHE_TTL = 60 * 60 * 24 * 30;

    public function __construct(
        protected string $target = 'ar',
        protected string $source = 'en'
    ) {}

    public function translate(string $text): ?string
    {
        $cacheKey = $this->cacheKey($text);
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        try {
            $tr = new GoogleTranslate($this->target);
            $tr->setSource($this->source);
            $result = $tr->translate($text);
            Cache::put($cacheKey, $result, self::CACHE_TTL);
            return $result;
        } catch (\Throwable) {
            return null;
        }
    }

    public function translateMultiple(array $texts): array
    {
        $keys = array_keys($texts);

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

        try {
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
        } catch (\Throwable) {
            foreach (array_keys($missing) as $key) {
                $out[$key] = null;
            }
        }

        return $out;
    }

    private function cacheKey(string $text): string
    {
        return "translation:{$this->source}:{$this->target}:" . md5($text);
    }
}
