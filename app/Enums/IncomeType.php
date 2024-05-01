<?php

namespace App\Enums;

enum IncomeType: string
{
    use Base;

    const ADD = 'ADD';

    const UPDATE = 'UPDATE';

    const REMOVE = 'REMOVE';
}
