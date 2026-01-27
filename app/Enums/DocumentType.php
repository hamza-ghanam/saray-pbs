<?php

namespace App\Enums;

enum DocumentType: string
{
    case RF = 'RF';
    case SPA = 'SPA';
    case BROKER_AGREEMENT = 'BROKER_AGREEMENT';
}