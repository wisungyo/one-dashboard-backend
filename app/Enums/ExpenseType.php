<?php

namespace App\Enums;

enum ExpenseType: string
{
    use Base;

    const ADD = 'ADD';

    const UPDATE = 'UPDATE';

    const REMOVE = 'REMOVE';
}
