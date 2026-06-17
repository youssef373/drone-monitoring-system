<?php

declare(strict_types=1);

namespace App\Filament\Resources\GeofenceResource\Pages;

use App\Filament\Resources\GeofenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGeofences extends ListRecords
{
    protected static string $resource = GeofenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
