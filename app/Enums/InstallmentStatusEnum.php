<?php

namespace App\Enums;

enum InstallmentStatusEnum: string
{
    case PENDING        = 'pending';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID           = 'paid';
    case OVERDUE        = 'overdue';
    case WAIVED         = 'waived';

    public function label(): string
    {
        return match($this) {
            self::PENDING        => 'Pending',
            self::PARTIALLY_PAID => 'Partially Paid',
            self::PAID           => 'Paid',
            self::OVERDUE        => 'Overdue',
            self::WAIVED         => 'Waived',
        };
    }

    public function isUnpaid(): bool
    {
        return in_array($this, [self::PENDING, self::PARTIALLY_PAID, self::OVERDUE]);
    }
}
