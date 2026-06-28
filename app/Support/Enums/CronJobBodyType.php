<?php

namespace App\Support\Enums;

enum CronJobBodyType: string
{
    case None = 'none';
    case Json = 'json';
    case Form = 'form';
    case Raw = 'raw';
}
