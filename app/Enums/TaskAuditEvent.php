<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskAuditEvent: string
{
    case Completed = 'completed';
}
