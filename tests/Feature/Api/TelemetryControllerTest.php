<?php

namespace Tests\Feature\Api;

use App\Models\Drone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TelemetryControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(int $droneId): array
    {
        return [
            'drone_id'        => $droneId,
            'latitude'        => 40.7128,
            'longitude'       => -74.0060,
            'altitude'        => 50.5,
            'battery_level'   => 85,
            'signal_strength' => 92,
            'speed'           => 12.5,
            'heading'         => 180.0,
            'recorded_at'     => '2026-06-11T10:30:00+00:00',
        ];
    }

    public function test_valid_telemetry_returns_202(): void
    {
        $drone = Drone::factory()->create();

        $response = $this->postJson('/api/telemetry', $this->validPayload($drone->id));

        $response->assertStatus(202)
            ->assertJsonStructure(['message', 'job_id'])
            ->assertJsonFragment(['message' => 'Telemetry accepted for processing']);
    }

    public function test_invalid_telemetry_returns_422(): void
    {
        $response = $this->postJson('/api/telemetry', [
            'drone_id'  => null,
            'latitude'  => 999,
            'longitude' => 'not-a-number',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['drone_id', 'latitude', 'longitude', 'altitude', 'battery_level', 'recorded_at']);
    }

    public function test_nonexistent_drone_returns_422(): void
    {
        $payload = $this->validPayload(99999);

        $response = $this->postJson('/api/telemetry', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['drone_id']);
    }

    public function test_dispatches_process_telemetry_job(): void
    {
        Queue::fake();

        $drone = Drone::factory()->create();

        $this->postJson('/api/telemetry', $this->validPayload($drone->id))
            ->assertStatus(202);

        // A queued closure is dispatched as placeholder until feature 004 wires in ProcessTelemetryJob.
        Queue::assertCount(1);
    }

    public function test_response_contains_uuid_job_id(): void
    {
        $drone = Drone::factory()->create();

        $response = $this->postJson('/api/telemetry', $this->validPayload($drone->id));

        $response->assertStatus(202);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $response->json('job_id')
        );
    }

    public function test_nullable_fields_are_optional(): void
    {
        $drone = Drone::factory()->create();

        $payload = [
            'drone_id'    => $drone->id,
            'latitude'    => 40.7128,
            'longitude'   => -74.0060,
            'altitude'    => 50.5,
            'battery_level' => 85,
            'recorded_at' => '2026-06-11T10:30:00+00:00',
        ];

        $this->postJson('/api/telemetry', $payload)->assertStatus(202);
    }
}
