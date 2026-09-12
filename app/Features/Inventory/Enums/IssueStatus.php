<?php

namespace App\Features\Inventory\Enums;

enum IssueStatus: string
{
    case DRAFT = 'DRAFT';
    case POSTED = 'POSTED';
    case CANCELED = 'CANCELED';
}
