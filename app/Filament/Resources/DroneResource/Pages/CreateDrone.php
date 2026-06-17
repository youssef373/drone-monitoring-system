<?php

declare(strict_types=1);

namespace App\Filament\Resources\DroneResource\Pages;

use App\Filament\Resources\DroneResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDrone extends CreateRecord
{
    protected static string $resource = DroneResource::class;
}
