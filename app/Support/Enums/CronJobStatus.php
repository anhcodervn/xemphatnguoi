<?php

namespace App\Support\Enums;

enum CronJobStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Disabled = 'disabled';
}
