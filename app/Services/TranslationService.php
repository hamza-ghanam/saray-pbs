<?php

namespace App\Services;

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

    public function translateMultiple(array $texts): array
    {
        $tr = new GoogleTranslate($this->target);
        $tr->setSource($this->source);

        $out = [];
        foreach ($texts as $k => $t) {
            $out[$k] = $tr->translate($t);
        }
        return $out;
    }
}
