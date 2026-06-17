<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TelemetryResource\Pages;
use App\Models\Drone;
use App\Models\TelemetryRecord;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TelemetryResource extends Resource
{
    protected static ?string $model = TelemetryRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Telemetry';

    protected static ?string $modelLabel = 'Telemetry Record';

    public static function form(Schema $form): Schema
    {
        return $form->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('drone.name')
                    ->label('Drone')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('latitude')
                    ->numeric(decimalPlaces: 6),

                TextColumn::make('longitude')
                    ->numeric(decimalPlaces: 6),

                TextColumn::make('altitude')
                    ->label('Altitude (m)')
                    ->numeric(decimalPlaces: 1),

                TextColumn::make('battery_level')
                    ->label('Battery %')
                    ->suffix('%')
                    ->sortable(),

                TextColumn::make('signal_strength')
                    ->label('Signal %')
                    ->suffix('%'),

                TextColumn::make('recorded_at')
                    ->label('Recorded At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('drone_id')
                    ->label('Drone')
                    ->options(Drone::pluck('name', 'id')),

                Filter::make('recorded_at')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, string $date) => $q->whereDate('recorded_at', '>=', $date))
                            ->when($data['until'], fn (Builder $q, string $date) => $q->whereDate('recorded_at', '<=', $date));
                    }),
            ])
            ->defaultSort('recorded_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTelemetry::route('/'),
        ];
    }
}
