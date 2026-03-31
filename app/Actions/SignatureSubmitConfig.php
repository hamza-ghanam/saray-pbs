<?php

namespace App\Actions;

use App\Enums\DocumentType;

final class SignatureSubmitConfig
{
    public function __construct(
        public DocumentType $type,
        public string $expectedDocumentClass,   // ReservationForm::class / SPA::class
        public string $signatureDir,            // 'signatures/rf' / 'signatures/spa'
        public string $invalidDocMessage,
    ) {}
}
