<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Models\Alert;
use App\Models\Drone;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateAlertJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly int $droneId,
        public readonly array $telemetryData,
    ) {}

    public function handle(): void
    {
        $drone = Drone::with('geofence')->find($this->droneId);

        if (! $drone) {
            return;
        }

        $battery = $this->telemetryData['battery_level'];
        $signal = $this->telemetryData['signal_strength'] ?? null;
        $lat = (float) $this->telemetryData['latitude'];
        $lng = (float) $this->telemetryData['longitude'];

        if ($battery < 10) {
            $this->createAlertIfNone($drone->id, AlertType::CRITICAL_BATTERY, AlertSeverity::CRITICAL,
                "Critical battery level: {$battery}%");
        } elseif ($battery < 25) {
            $this->createAlertIfNone($drone->id, AlertType::LOW_BATTERY, AlertSeverity::WARNING,
                "Low battery level: {$battery}%");
        }

        if ($signal === 0) {
            $this->createAlertIfNone($drone->id, AlertType::SIGNAL_LOSS, AlertSeverity::WARNING,
                'Signal lost (strength: 0)');
        }

        if ($drone->geofence && ! $drone->geofence->containsPoint($lat, $lng)) {
            $this->createAlertIfNone($drone->id, AlertType::GEOFENCE_VIOLATION, AlertSeverity::CRITICAL,
                "Drone outside geofence [{$drone->geofence->name}]");
        }
    }

    private function createAlertIfNone(int $droneId, AlertType $type, AlertSeverity $severity, string $message): void
    {
        $exists = Alert::where('drone_id', $droneId)
            ->where('type', $type)
            ->whereNull('resolved_at')
            ->exists();

        if (! $exists) {
            Alert::create([
                'drone_id' => $droneId,
                'type' => $type,
                'severity' => $severity,
                'message' => $message,
            ]);
        }
    }
}
