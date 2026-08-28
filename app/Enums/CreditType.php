<?php

namespace App\Enums;

enum CreditType: string
{
    case Sales = 'SALES';
    case VendorReturn = 'VENDOR_RETURN';
    case EmployeeReturn = 'EMPLOYEE_RETURN';
    case OtherCredit = 'OTHER_CREDIT';
}
