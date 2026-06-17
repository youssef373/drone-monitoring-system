<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\AlertSeverity;
use App\Models\Alert;
use App\Models\Drone;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalDrones = Drone::count();
        $criticalAlerts = Alert::whereNull('resolved_at')
            ->where('severity', AlertSeverity::CRITICAL)
            ->count();
        $avgBattery = Drone::avg('battery_level');

        return [
            Stat::make('Total Drones', $totalDrones)
                ->icon('heroicon-o-paper-airplane')
                ->color('primary'),

            Stat::make('Critical Alerts', $criticalAlerts)
                ->icon('heroicon-o-bell-alert')
                ->color($criticalAlerts > 0 ? 'danger' : 'success'),

            Stat::make('Avg Battery', round((float) ($avgBattery ?? 0), 1).'%')
                ->icon('heroicon-o-battery-100')
                ->color((float) ($avgBattery ?? 100) < 25 ? 'warning' : 'success'),
        ];
    }
}
