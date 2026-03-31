<?php

namespace App\Enums;

enum BrokerCommissionStatusEnum: string
{
    case DUE = 'due';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
}
