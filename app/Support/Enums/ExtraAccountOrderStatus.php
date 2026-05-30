<?php

namespace App\Support\Enums;

enum ExtraAccountOrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}
