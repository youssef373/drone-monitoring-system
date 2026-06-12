<?php

declare(strict_types=1);

namespace App\Enums;

enum AlertSeverity: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::INFO => 'Info',
            self::WARNING => 'Warning',
            self::CRITICAL => 'Critical',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INFO => 'bg-blue-100 text-blue-800',
            self::WARNING => 'bg-yellow-100 text-yellow-800',
            self::CRITICAL => 'bg-red-100 text-red-800',
        };
    }
}
