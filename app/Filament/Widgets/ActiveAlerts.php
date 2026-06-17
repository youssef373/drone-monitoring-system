<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\AlertSeverity;
use App\Filament\Resources\AlertResource;
use App\Models\Alert;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ActiveAlerts extends BaseWidget
{
    protected static ?string $heading = 'Active Alerts';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Alert::with('drone')->whereNull('resolved_at')->latest())
            ->columns([
                TextColumn::make('drone.name')
                    ->label('Drone'),

                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label()),

                TextColumn::make('severity')
                    ->badge()
                    ->color(fn (AlertSeverity $state): string => match ($state) {
                        AlertSeverity::CRITICAL => 'danger',
                        AlertSeverity::WARNING => 'warning',
                        AlertSeverity::INFO => 'info',
                    })
                    ->formatStateUsing(fn (AlertSeverity $state): string => $state->label()),

                TextColumn::make('message')
                    ->limit(60),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->since(),
            ])
            ->paginated(false);
    }
}
