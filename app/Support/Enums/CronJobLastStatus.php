<?php

namespace App\Support\Enums;

enum CronJobLastStatus: string
{
    case Success = 'success';
    case Failed = 'failed';
    case Timeout = 'timeout';
    case Error = 'error';
    case Blocked = 'blocked';
}
