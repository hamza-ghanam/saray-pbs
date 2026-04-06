<?php

namespace App\Actions\Signature;

use App\Enums\DocumentType;

final class CustomerSignatureUploadConfig
{
    public function __construct(
        public string $permission,
        public string $bookingRelation,
        public string $expectedDocumentClass,
        public DocumentType $type,
        public string $signatureDir,
        public string $filePrefix,
        public string $documentNotFoundMessage,
        public string $invalidStatusMessage,
    ) {}
}
