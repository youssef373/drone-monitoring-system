<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Events\TelemetryUpdated;
use App\Jobs\GenerateAlertJob;
use App\Jobs\ProcessTelemetryJob;
use App\Models\Drone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ProcessTelemetryJobTest extends TestCase
{
    use RefreshDatabase;

    private function telemetryData(int $droneId): array
    {
        return [
            'drone_id' => $droneId,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'altitude' => 50.5,
            'battery_level' => 80,
            'signal_strength' => 90,
            'speed' => 10.0,
            'heading' => 90.0,
            'recorded_at' => '2026-06-11T10:30:00+00:00',
        ];
    }

    public function test_stores_telemetry_record(): void
    {
        $drone = Drone::factory()->create();

        (new ProcessTelemetryJob($this->telemetryData($drone->id)))->handle();

        $this->assertDatabaseHas('telemetry_records', [
            'drone_id' => $drone->id,
            'battery_level' => 80,
        ]);
    }

    public function test_updates_drone_state(): void
    {
        $drone = Drone::factory()->create();

        (new ProcessTelemetryJob($this->telemetryData($drone->id)))->handle();

        $drone->refresh();

        $this->assertEquals(80, $drone->battery_level);
        $this->assertNotNull($drone->last_telemetry_at);
    }

    public function test_fires_telemetry_updated_event(): void
    {
        if (! class_exists(TelemetryUpdated::class)) {
            $this->markTestSkipped('TelemetryUpdated event not yet implemented (feature 005).');
        }

        Event::fake([TelemetryUpdated::class]);

        $drone = Drone::factory()->create();

        (new ProcessTelemetryJob($this->telemetryData($drone->id)))->handle();

        Event::assertDispatched(TelemetryUpdated::class);
    }

    public function test_dispatches_generate_alert_job(): void
    {
        Bus::fake([GenerateAlertJob::class]);

        $drone = Drone::factory()->create();

        (new ProcessTelemetryJob($this->telemetryData($drone->id)))->handle();

        Bus::assertDispatched(GenerateAlertJob::class, fn ($job) => $job->droneId === $drone->id);
    }

    public function test_fails_on_missing_drone(): void
    {
        $job = new ProcessTelemetryJob($this->telemetryData(99999));

        $this->assertDatabaseCount('telemetry_records', 0);

        $job->handle();

        $this->assertDatabaseCount('telemetry_records', 0);
    }
}
