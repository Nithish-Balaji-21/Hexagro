<?php

namespace App\Enums;

enum EntityType: string
{
    case Shareholder = 'SHAREHOLDER';
    case NonShareholderFunder = 'NON_SHAREHOLDER_FUNDER';
    case BankAccount = 'BANK_ACCOUNT';
}
