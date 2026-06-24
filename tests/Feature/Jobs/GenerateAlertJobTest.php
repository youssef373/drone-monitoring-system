<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Jobs\GenerateAlertJob;
use App\Models\Alert;
use App\Models\Drone;
use App\Models\Geofence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateAlertJobTest extends TestCase
{
    use RefreshDatabase;

    private function telemetryData(array $overrides = []): array
    {
        return array_merge([
            'drone_id' => 1,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'altitude' => 50.5,
            'battery_level' => 80,
            'signal_strength' => 90,
            'recorded_at' => '2026-06-11T10:30:00+00:00',
        ], $overrides);
    }

    public function test_creates_low_battery_alert(): void
    {
        $drone = Drone::factory()->create(['battery_level' => 20]);
        $data = $this->telemetryData(['drone_id' => $drone->id, 'battery_level' => 20]);

        (new GenerateAlertJob($drone->id, $data))->handle();

        $this->assertDatabaseHas('alerts', [
            'drone_id' => $drone->id,
            'type' => AlertType::LOW_BATTERY->value,
        ]);
    }

    public function test_creates_critical_battery_alert(): void
    {
        $drone = Drone::factory()->create(['battery_level' => 5]);
        $data = $this->telemetryData(['drone_id' => $drone->id, 'battery_level' => 5]);

        (new GenerateAlertJob($drone->id, $data))->handle();

        $this->assertDatabaseHas('alerts', [
            'drone_id' => $drone->id,
            'type' => AlertType::CRITICAL_BATTERY->value,
        ]);
    }

    public function test_creates_geofence_violation(): void
    {
        $geofence = Geofence::factory()->create([
            'center_lat' => 40.0,
            'center_lng' => -74.0,
            'radius_meters' => 100,
        ]);

        $drone = Drone::factory()->create(['geofence_id' => $geofence->id]);

        $data = $this->telemetryData([
            'drone_id' => $drone->id,
            'latitude' => 41.9999,
            'longitude' => -76.9999,
        ]);

        (new GenerateAlertJob($drone->id, $data))->handle();

        $this->assertDatabaseHas('alerts', [
            'drone_id' => $drone->id,
            'type' => AlertType::GEOFENCE_VIOLATION->value,
        ]);
    }

    public function test_creates_signal_loss_alert(): void
    {
        $drone = Drone::factory()->create();
        $data = $this->telemetryData(['drone_id' => $drone->id, 'signal_strength' => 0]);

        (new GenerateAlertJob($drone->id, $data))->handle();

        $this->assertDatabaseHas('alerts', [
            'drone_id' => $drone->id,
            'type' => AlertType::SIGNAL_LOSS->value,
        ]);
    }

    public function test_does_not_duplicate_unresolved_alerts(): void
    {
        $drone = Drone::factory()->create(['geofence_id' => null]);
        $data = $this->telemetryData(['drone_id' => $drone->id, 'battery_level' => 20]);

        (new GenerateAlertJob($drone->id, $data))->handle();
        (new GenerateAlertJob($drone->id, $data))->handle();

        $this->assertDatabaseCount('alerts', 1);
    }

    public function test_no_alert_when_conditions_normal(): void
    {
        $drone = Drone::factory()->create(['geofence_id' => null]);
        $data = $this->telemetryData(['drone_id' => $drone->id]);

        (new GenerateAlertJob($drone->id, $data))->handle();

        $this->assertDatabaseCount('alerts', 0);
    }

    public function test_resolves_geofence_violation_when_drone_returns_inside(): void
    {
        $geofence = Geofence::factory()->create([
            'center_lat' => 40.0,
            'center_lng' => -74.0,
            'radius_meters' => 1000,
        ]);

        $drone = Drone::factory()->create(['geofence_id' => $geofence->id]);

        Alert::create([
            'drone_id' => $drone->id,
            'type' => AlertType::GEOFENCE_VIOLATION,
            'severity' => AlertSeverity::CRITICAL,
            'message' => "Drone outside geofence [{$geofence->name}]",
        ]);

        $data = $this->telemetryData([
            'drone_id' => $drone->id,
            'latitude' => 40.0,
            'longitude' => -74.0,
        ]);

        (new GenerateAlertJob($drone->id, $data))->handle();

        $alert = Alert::where('drone_id', $drone->id)
            ->where('type', AlertType::GEOFENCE_VIOLATION)
            ->first();

        $this->assertNotNull($alert->resolved_at);
    }

    public function test_resolves_low_battery_alert_when_battery_recovers(): void
    {
        $drone = Drone::factory()->create(['geofence_id' => null]);

        Alert::create([
            'drone_id' => $drone->id,
            'type' => AlertType::LOW_BATTERY,
            'severity' => AlertSeverity::WARNING,
            'message' => 'Low battery level: 20%',
        ]);

        $data = $this->telemetryData(['drone_id' => $drone->id, 'battery_level' => 80]);

        (new GenerateAlertJob($drone->id, $data))->handle();

        $alert = Alert::where('drone_id', $drone->id)
            ->where('type', AlertType::LOW_BATTERY)
            ->first();

        $this->assertNotNull($alert->resolved_at);
    }

    public function test_resolves_signal_loss_alert_when_signal_returns(): void
    {
        $drone = Drone::factory()->create(['geofence_id' => null]);

        Alert::create([
            'drone_id' => $drone->id,
            'type' => AlertType::SIGNAL_LOSS,
            'severity' => AlertSeverity::WARNING,
            'message' => 'Signal lost (strength: 0)',
        ]);

        $data = $this->telemetryData(['drone_id' => $drone->id, 'signal_strength' => 50]);

        (new GenerateAlertJob($drone->id, $data))->handle();

        $alert = Alert::where('drone_id', $drone->id)
            ->where('type', AlertType::SIGNAL_LOSS)
            ->first();

        $this->assertNotNull($alert->resolved_at);
    }
}
