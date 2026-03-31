<?php

namespace App\Actions;

final class SignatureSendConfig
{
    public function __construct(
        public string $permission,
        public string $requiredBookingStatus,
        public string $documentModelClass,  // ReservationForm::class or SPA::class
        public string $documentTypeValue,   // DocumentType::RF->value ...
        public string $missingDocMessage,
        public string $missingPdfMessage,
        public string $successMessage,
        public string $forbiddenMessage = 'Forbidden',
    ) {}
}
