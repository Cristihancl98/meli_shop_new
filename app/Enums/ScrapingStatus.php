<?php

namespace App\Enums;

enum ScrapingStatus: string
{
    case None    = 'none';
    case Pending = 'pending';
    case Done    = 'done';
    case Failed  = 'failed';
}
