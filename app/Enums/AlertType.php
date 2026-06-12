<?php

declare(strict_types=1);

namespace App\Enums;

enum AlertType: string
{
    case LOW_BATTERY = 'low_battery';
    case CRITICAL_BATTERY = 'critical_battery';
    case GEOFENCE_VIOLATION = 'geofence_violation';
    case SIGNAL_LOSS = 'signal_loss';
    case EMERGENCY = 'emergency';

    public function label(): string
    {
        return match ($this) {
            self::LOW_BATTERY => 'Low Battery',
            self::CRITICAL_BATTERY => 'Critical Battery',
            self::GEOFENCE_VIOLATION => 'Geofence Violation',
            self::SIGNAL_LOSS => 'Signal Loss',
            self::EMERGENCY => 'Emergency',
        };
    }
}
