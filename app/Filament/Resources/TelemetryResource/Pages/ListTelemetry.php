<?php

declare(strict_types=1);

namespace App\Filament\Resources\TelemetryResource\Pages;

use App\Filament\Resources\TelemetryResource;
use Filament\Resources\Pages\ListRecords;

class ListTelemetry extends ListRecords
{
    protected static string $resource = TelemetryResource::class;
}
