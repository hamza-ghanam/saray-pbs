<?php

namespace App\Contracts\Documents;

interface SignableDocument
{
    public function getOriginalPdfPath(): string;

    public function getSignedPdfPath(): ?string;
    public function getDownloadFileName(string $variant = 'latest'): string;
}
