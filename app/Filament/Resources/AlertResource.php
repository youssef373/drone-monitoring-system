<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Filament\Resources\AlertResource\Pages;
use App\Models\Alert;
use App\Models\Drone;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AlertResource extends Resource
{
    protected static ?string $model = Alert::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationLabel = 'Alerts';

    public static function form(Schema $form): Schema
    {
        return $form->components([
            Placeholder::make('drone_name')
                ->label('Drone')
                ->content(fn (Alert $record): string => $record->drone->name ?? '—'),

            Placeholder::make('type')
                ->label('Type')
                ->content(fn (Alert $record): string => $record->type->label()),

            Placeholder::make('severity')
                ->label('Severity')
                ->content(fn (Alert $record): string => $record->severity->label()),

            Placeholder::make('message')
                ->label('Message')
                ->content(fn (Alert $record): string => $record->message),

            DateTimePicker::make('resolved_at')
                ->label('Resolved At')
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('drone.name')
                    ->label('Drone')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn (AlertType $state): string => match ($state) {
                        AlertType::CRITICAL_BATTERY, AlertType::EMERGENCY => 'danger',
                        AlertType::LOW_BATTERY, AlertType::GEOFENCE_VIOLATION => 'warning',
                        AlertType::SIGNAL_LOSS => 'info',
                    })
                    ->formatStateUsing(fn (AlertType $state): string => $state->label()),

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
                    ->since()
                    ->sortable(),

                IconColumn::make('resolved_at')
                    ->label('Resolved')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(collect(AlertType::cases())->mapWithKeys(
                        fn (AlertType $t) => [$t->value => $t->label()]
                    )),

                SelectFilter::make('severity')
                    ->options(collect(AlertSeverity::cases())->mapWithKeys(
                        fn (AlertSeverity $s) => [$s->value => $s->label()]
                    )),

                TernaryFilter::make('resolved_at')
                    ->label('Resolved')
                    ->nullable()
                    ->trueLabel('Resolved')
                    ->falseLabel('Unresolved')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('resolved_at'),
                        false: fn (Builder $query) => $query->whereNull('resolved_at'),
                        blank: fn (Builder $query) => $query,
                    ),

                SelectFilter::make('drone_id')
                    ->label('Drone')
                    ->options(Drone::pluck('name', 'id')),
            ])
            ->actions([
                Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Alert $record): bool => ! $record->isResolved())
                    ->action(fn (Alert $record) => $record->resolve()),
            ])
            ->bulkActions([
                BulkAction::make('resolve_bulk')
                    ->label('Resolve Selected')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn (Collection $records) => $records->each->resolve())
                    ->deselectRecordsAfterCompletion(),
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
            'index' => Pages\ListAlerts::route('/'),
            'edit' => Pages\EditAlert::route('/{record}/edit'),
        ];
    }
}
