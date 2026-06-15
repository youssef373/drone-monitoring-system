<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\TelemetryUpdated;
use App\Models\Drone;
use App\Models\TelemetryRecord;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessTelemetryJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(public readonly array $telemetryData) {}

    public function handle(): void
    {
        $drone = Drone::find($this->telemetryData['drone_id']);

        if (! $drone) {
            $this->fail(new \RuntimeException("Drone [{$this->telemetryData['drone_id']}] not found."));

            return;
        }

        $record = TelemetryRecord::create([
            'drone_id' => $drone->id,
            'latitude' => $this->telemetryData['latitude'],
            'longitude' => $this->telemetryData['longitude'],
            'altitude' => $this->telemetryData['altitude'],
            'battery_level' => $this->telemetryData['battery_level'],
            'signal_strength' => $this->telemetryData['signal_strength'] ?? null,
            'speed' => $this->telemetryData['speed'] ?? null,
            'heading' => $this->telemetryData['heading'] ?? null,
            'recorded_at' => $this->telemetryData['recorded_at'],
        ]);

        $drone->update([
            'current_lat' => $record->latitude,
            'current_lng' => $record->longitude,
            'current_altitude' => $record->altitude,
            'battery_level' => $record->battery_level,
            'last_telemetry_at' => now(),
        ]);

        if (class_exists(TelemetryUpdated::class)) {
            event(new TelemetryUpdated($drone, $record));
        }

        GenerateAlertJob::dispatch($drone->id, $this->telemetryData);
    }
}
