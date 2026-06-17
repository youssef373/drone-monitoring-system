<?php

declare(strict_types=1);

namespace App\Filament\Resources\DroneResource\Pages;

use App\Filament\Resources\DroneResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDrone extends EditRecord
{
    protected static string $resource = DroneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
