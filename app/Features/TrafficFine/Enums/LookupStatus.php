<?php

namespace App\Features\TrafficFine\Enums;

enum LookupStatus: string
{
    case Success = 'success';
    case NoViolation = 'no_violation';
}
