<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Events\TelemetryUpdated;
use App\Jobs\ProcessTelemetryJob;
use App\Models\Drone;
use App\Models\TelemetryRecord;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TelemetryUpdatedTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_broadcasts_on_correct_channel(): void
    {
        $drone = Drone::factory()->create();
        $record = TelemetryRecord::factory()->create(['drone_id' => $drone->id]);

        $event = new TelemetryUpdated($drone, $record);
        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-drone.'.$drone->id, $channel->name);
    }

    public function test_event_payload_contains_correct_data(): void
    {
        $drone = Drone::factory()->create(['battery_level' => 75]);
        $record = TelemetryRecord::factory()->create([
            'drone_id' => $drone->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'altitude' => 50.5,
            'battery_level' => 75,
        ]);

        $event = new TelemetryUpdated($drone, $record);
        $payload = $event->broadcastWith();

        $this->assertEquals($drone->id, $payload['drone_id']);
        $this->assertEquals($drone->name, $payload['name']);
        $this->assertEquals(40.7128, $payload['latitude']);
        $this->assertEquals(-74.0060, $payload['longitude']);
        $this->assertEquals(75, $payload['battery_level']);
        $this->assertArrayHasKey('recorded_at', $payload);
    }

    public function test_event_has_correct_broadcast_name(): void
    {
        $drone = Drone::factory()->create();
        $record = TelemetryRecord::factory()->create(['drone_id' => $drone->id]);

        $event = new TelemetryUpdated($drone, $record);

        $this->assertEquals('telemetry.updated', $event->broadcastAs());
    }

    public function test_event_fired_when_telemetry_processed(): void
    {
        Event::fake([TelemetryUpdated::class]);

        $drone = Drone::factory()->create();

        (new ProcessTelemetryJob([
            'drone_id' => $drone->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'altitude' => 50.5,
            'battery_level' => 80,
            'signal_strength' => 90,
            'recorded_at' => '2026-06-11T10:30:00+00:00',
        ]))->handle();

        Event::assertDispatched(TelemetryUpdated::class, function ($event) use ($drone) {
            return $event->drone->id === $drone->id;
        });
    }
}
