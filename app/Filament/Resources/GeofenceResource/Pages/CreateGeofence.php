<?php

declare(strict_types=1);

namespace App\Filament\Resources\GeofenceResource\Pages;

use App\Filament\Resources\GeofenceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGeofence extends CreateRecord
{
    protected static string $resource = GeofenceResource::class;
}
