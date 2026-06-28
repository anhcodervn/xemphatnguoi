<?php

namespace App\Support\Enums;

enum CronAlertChannelType: string
{
    case Discord = 'discord';
    case Telegram = 'telegram';
    case Webhook = 'webhook';
    case Email = 'email';
}
