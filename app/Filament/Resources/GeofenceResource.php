<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\GeofenceResource\Pages;
use App\Models\Geofence;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GeofenceResource extends Resource
{
    protected static ?string $model = Geofence::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Geofences';

    public static function form(Schema $form): Schema
    {
        return $form->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),

            TextInput::make('center_lat')
                ->label('Center Latitude')
                ->numeric()
                ->required(),

            TextInput::make('center_lng')
                ->label('Center Longitude')
                ->numeric()
                ->required(),

            TextInput::make('radius_meters')
                ->label('Radius (meters)')
                ->numeric()
                ->nullable(),

            Textarea::make('boundary')
                ->label('Boundary (JSON polygon)')
                ->nullable()
                ->rows(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('center_lat')
                    ->label('Latitude')
                    ->numeric(decimalPlaces: 6),

                TextColumn::make('center_lng')
                    ->label('Longitude')
                    ->numeric(decimalPlaces: 6),

                TextColumn::make('radius_meters')
                    ->label('Radius (m)')
                    ->numeric(decimalPlaces: 1)
                    ->default('Polygon'),

                TextColumn::make('drones_count')
                    ->label('Drones')
                    ->counts('drones')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('has_drones')
                    ->label('Has Drones')
                    ->query(fn (Builder $query) => $query->has('drones')),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGeofences::route('/'),
            'create' => Pages\CreateGeofence::route('/create'),
            'edit' => Pages\EditGeofence::route('/{record}/edit'),
        ];
    }
}
