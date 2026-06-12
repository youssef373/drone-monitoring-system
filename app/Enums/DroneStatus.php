<?php

declare(strict_types=1);

namespace App\Enums;

enum DroneStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case MAINTENANCE = 'maintenance';
    case EMERGENCY = 'emergency';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::MAINTENANCE => 'Maintenance',
            self::EMERGENCY => 'Emergency',
        };
    }
}
