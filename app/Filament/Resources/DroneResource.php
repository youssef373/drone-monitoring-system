<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\DroneStatus;
use App\Filament\Resources\DroneResource\Pages;
use App\Models\Drone;
use App\Models\Geofence;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DroneResource extends Resource
{
    protected static ?string $model = Drone::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationLabel = 'Drones';

    public static function form(Schema $form): Schema
    {
        return $form->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),

            TextInput::make('type')
                ->maxLength(255),

            Select::make('status')
                ->options(collect(DroneStatus::cases())->mapWithKeys(
                    fn (DroneStatus $s) => [$s->value => $s->label()]
                ))
                ->required(),

            Select::make('geofence_id')
                ->label('Geofence')
                ->options(Geofence::pluck('name', 'id'))
                ->searchable()
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (DroneStatus $state): string => match ($state) {
                        DroneStatus::ACTIVE => 'success',
                        DroneStatus::INACTIVE => 'gray',
                        DroneStatus::MAINTENANCE => 'warning',
                        DroneStatus::EMERGENCY => 'danger',
                    })
                    ->formatStateUsing(fn (DroneStatus $state): string => $state->label()),

                TextColumn::make('battery_level')
                    ->label('Battery %')
                    ->sortable()
                    ->suffix('%'),

                TextColumn::make('current_lat')
                    ->label('Latitude')
                    ->numeric(decimalPlaces: 6),

                TextColumn::make('current_lng')
                    ->label('Longitude')
                    ->numeric(decimalPlaces: 6),

                TextColumn::make('geofence.name')
                    ->label('Geofence')
                    ->default('—'),

                TextColumn::make('last_telemetry_at')
                    ->label('Last Telemetry')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(DroneStatus::cases())->mapWithKeys(
                        fn (DroneStatus $s) => [$s->value => $s->label()]
                    )),

                SelectFilter::make('geofence_id')
                    ->label('Geofence')
                    ->options(Geofence::pluck('name', 'id')),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDrones::route('/'),
            'create' => Pages\CreateDrone::route('/create'),
            'edit' => Pages\EditDrone::route('/{record}/edit'),
        ];
    }
}
