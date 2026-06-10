<?php

namespace App\Enums;

enum InvoiceStatusEnum: string
{
    case DRAFT     = 'draft';
    case ISSUED    = 'issued';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::DRAFT     => 'Draft',
            self::ISSUED    => 'Issued',
            self::CANCELLED => 'Cancelled',
        };
    }
}
