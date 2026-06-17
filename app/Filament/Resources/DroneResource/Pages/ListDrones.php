<?php

declare(strict_types=1);

namespace App\Filament\Resources\DroneResource\Pages;

use App\Filament\Resources\DroneResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDrones extends ListRecords
{
    protected static string $resource = DroneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
