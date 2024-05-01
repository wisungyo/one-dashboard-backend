<?php

namespace App\Enums;

enum TransactionType: string
{
    use Base;

    case IN = 'IN';
    case OUT = 'OUT';
}
