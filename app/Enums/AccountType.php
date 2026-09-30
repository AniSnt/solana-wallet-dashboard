<?php

namespace App\Enums;

enum AccountType: string
{
    case Individual = 'individual'; // PF (pessoa física)
    case Company = 'company';       // PJ (pessoa jurídica)
}
