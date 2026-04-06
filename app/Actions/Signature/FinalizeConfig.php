<?php

namespace App\Actions\Signature;

use App\Enums\DocumentType;

final class FinalizeConfig
{
    public ?\Closure $persist = null;

    public function __construct(
        public readonly DocumentType $type,
        public readonly string $view,
        public readonly string $signedDir,       // e.g. spa_forms/signed
        public readonly string $filePrefix,      // e.g. SPA_SIGNED_FINAL_
        public readonly string $statusSigned = 'Signed',
    ) {}
}
