<?php

namespace App\Enums;

enum InvoiceTypeEnum: string
{
    case BOOKING_CONFIRMATION = 'booking_confirmation';
    case PAYMENT_RECEIPT      = 'payment_receipt';
    case TAX_INVOICE          = 'tax_invoice';

    public function label(): string
    {
        return match($this) {
            self::BOOKING_CONFIRMATION => 'Booking Confirmation',
            self::PAYMENT_RECEIPT      => 'Payment Receipt',
            self::TAX_INVOICE          => 'Tax Invoice',
        };
    }
}
