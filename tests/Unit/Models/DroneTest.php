<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Drone;
use App\Models\Geofence;
use App\Models\TelemetryRecord;
use App\Models\Alert;
use App\Enums\DroneStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DroneTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_geofence(): void
    {
        $geofence = Geofence::factory()->create();
        $drone = Drone::factory()->create(['geofence_id' => $geofence->id]);

        $this->assertInstanceOf(Geofence::class, $drone->geofence);
        $this->assertEquals($geofence->id, $drone->geofence->id);
    }

    public function test_has_many_telemetry_records(): void
    {
        $drone = Drone::factory()->create();
        TelemetryRecord::factory()->count(3)->create(['drone_id' => $drone->id]);

        $this->assertCount(3, $drone->telemetryRecords);
        $this->assertInstanceOf(TelemetryRecord::class, $drone->telemetryRecords->first());
    }

    public function test_has_many_alerts(): void
    {
        $drone = Drone::factory()->create();
        Alert::factory()->count(2)->create(['drone_id' => $drone->id]);

        $this->assertCount(2, $drone->alerts);
        $this->assertInstanceOf(Alert::class, $drone->alerts->first());
    }

    public function test_update_location_updates_drone(): void
    {
        $drone = Drone::factory()->create([
            'current_lat' => 40.0,
            'current_lng' => -74.0,
            'current_altitude' => 50,
        ]);

        $drone->updateLocation(40.7128, -74.0060, 100);

        $drone->refresh();
        $this->assertEquals(40.7128, (float) $drone->current_lat);
        $this->assertEquals(-74.0060, (float) $drone->current_lng);
        $this->assertEquals(100, $drone->current_altitude);
    }

    public function test_is_in_emergency_returns_correct_value(): void
    {
        $normalDrone = Drone::factory()->create(['status' => DroneStatus::ACTIVE]);
        $emergencyDrone = Drone::factory()->create(['status' => DroneStatus::EMERGENCY]);

        $this->assertFalse($normalDrone->isInEmergency());
        $this->assertTrue($emergencyDrone->isInEmergency());
    }

    public function test_active_scope_returns_only_active(): void
    {
        Drone::factory()->count(2)->create(['status' => DroneStatus::ACTIVE]);
        Drone::factory()->create(['status' => DroneStatus::INACTIVE]);

        $activeDrones = Drone::active()->get();

        $this->assertCount(2, $activeDrones);
        $activeDrones->each(function ($drone) {
            $this->assertEquals(DroneStatus::ACTIVE, $drone->status);
        });
    }

    public function test_low_battery_scope_returns_correct_drones(): void
    {
        Drone::factory()->create(['battery_level' => 30]);
        Drone::factory()->create(['battery_level' => 20]);
        Drone::factory()->create(['battery_level' => 10]);

        $lowBatteryDrones = Drone::lowBattery()->get();

        $this->assertCount(2, $lowBatteryDrones);
    }
}
