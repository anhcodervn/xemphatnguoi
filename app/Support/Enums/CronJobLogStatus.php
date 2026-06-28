<?php

namespace App\Support\Enums;

enum CronJobLogStatus: string
{
    case Success = 'success';
    case Failed = 'failed';
    case Timeout = 'timeout';
    case Error = 'error';
    case Blocked = 'blocked';
}
