<?php

namespace App\Enums;

enum InstallmentPaymentStatusEnum: string
{
    case PENDING_VERIFICATION = 'pending_verification';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING_VERIFICATION => 'Pending Verification',
            self::VERIFIED => 'Verified',
            self::REJECTED => 'Rejected',
            self::REFUNDED => 'Refunded',
        };
    }
}
