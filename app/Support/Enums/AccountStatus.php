<?php

namespace App\Support\Enums;

enum AccountStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Released = 'released';
}
