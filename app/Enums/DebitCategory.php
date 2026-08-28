<?php

namespace App\Enums;

enum DebitCategory: string
{
    case Expense = 'EXPENSE';
    case RawMaterials = 'RAW_MATERIALS';
}
