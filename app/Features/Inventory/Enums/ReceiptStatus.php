<?php

namespace App\Features\Inventory\Enums;

enum ReceiptStatus: string
{
    case DRAFT = 'DRAFT';
    case POSTED = 'POSTED';
    case CANCELED = 'CANCELED';
}
