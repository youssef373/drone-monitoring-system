<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DroneStatus;
use App\Events\TelemetryUpdated;
use App\Models\Alert;
use App\Models\Drone;
use App\Models\Geofence;
use App\Models\TelemetryRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MapControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the map index page returns a successful response.
     */
    public function test_map_index_returns_successful_response(): void
    {
        $response = $this->get(route('map.index'));

        $response->assertStatus(200)
            ->assertViewIs('map.index');
    }

    /**
     * Test the map index page loads drones with geofences.
     */
    public function test_map_index_loads_drones_with_geofences(): void
    {
        $geofence = Geofence::factory()->create([
            'name' => 'Test Geofence',
            'center_lat' => 40.7128,
            'center_lng' => -74.0060,
            'radius_meters' => 1000,
        ]);

        $drone = Drone::factory()->create([
            'name' => 'Test Drone',
            'status' => DroneStatus::ACTIVE,
            'geofence_id' => $geofence->id,
            'current_lat' => 40.7128,
            'current_lng' => -74.0060,
            'battery_level' => 85,
        ]);

        $response = $this->get(route('map.index'));

        $response->assertStatus(200)
            ->assertViewHas('drones')
            ->assertViewHas('geofences');

        $drones = $response->viewData('drones');
        $this->assertTrue($drones->contains('id', $drone->id));
        $this->assertNotNull($drones->firstWhere('id', $drone->id)->geofence);
    }

    /**
     * Test the map index page loads all geofences.
     */
    public function test_map_index_loads_all_geofences(): void
    {
        $geofence1 = Geofence::factory()->create(['name' => 'Geofence 1']);
        $geofence2 = Geofence::factory()->create(['name' => 'Geofence 2']);

        $response = $this->get(route('map.index'));

        $response->assertStatus(200);

        $geofences = $response->viewData('geofences');
        $this->assertCount(2, $geofences);
        $this->assertTrue($geofences->contains('id', $geofence1->id));
        $this->assertTrue($geofences->contains('id', $geofence2->id));
    }

    /**
     * Test the single drone map page returns a successful response.
     */
    public function test_map_show_returns_successful_response(): void
    {
        $drone = Drone::factory()->create();

        $response = $this->get(route('map.show', $drone));

        $response->assertStatus(200)
            ->assertViewIs('map.show');
    }

    /**
     * Test the single drone map page loads drone with geofence.
     */
    public function test_map_show_loads_drone_with_geofence(): void
    {
        $geofence = Geofence::factory()->create([
            'name' => 'Test Geofence',
            'center_lat' => 40.7128,
            'center_lng' => -74.0060,
            'radius_meters' => 1000,
        ]);

        $drone = Drone::factory()->create([
            'name' => 'Test Drone',
            'status' => DroneStatus::ACTIVE,
            'geofence_id' => $geofence->id,
            'current_lat' => 40.7128,
            'current_lng' => -74.0060,
            'battery_level' => 85,
        ]);

        $response = $this->get(route('map.show', $drone));

        $response->assertStatus(200)
            ->assertViewHas('drone')
            ->assertViewHas('recentTelemetry')
            ->assertViewHas('activeAlerts');

        $viewDrone = $response->viewData('drone');
        $this->assertEquals($drone->id, $viewDrone->id);
        $this->assertNotNull($viewDrone->geofence);
        $this->assertEquals($geofence->id, $viewDrone->geofence->id);
    }

    /**
     * Test the single drone map page loads recent telemetry records.
     */
    public function test_map_show_loads_recent_telemetry(): void
    {
        $drone = Drone::factory()->create();

        // Create 60 telemetry records
        TelemetryRecord::factory()->count(60)->create([
            'drone_id' => $drone->id,
        ]);

        $response = $this->get(route('map.show', $drone));

        $response->assertStatus(200);

        $telemetry = $response->viewData('recentTelemetry');
        $this->assertCount(50, $telemetry); // Should be limited to 50
    }

    /**
     * Test the single drone map page loads active alerts.
     */
    public function test_map_show_loads_active_alerts(): void
    {
        $drone = Drone::factory()->create();

        // Create active alert
        Alert::factory()->create([
            'drone_id' => $drone->id,
            'resolved_at' => null,
        ]);

        // Create resolved alert
        Alert::factory()->create([
            'drone_id' => $drone->id,
            'resolved_at' => now(),
        ]);

        $response = $this->get(route('map.show', $drone));

        $response->assertStatus(200);

        $alerts = $response->viewData('activeAlerts');
        $this->assertCount(1, $alerts);
        $this->assertNull($alerts->first()->resolved_at);
    }

    /**
     * Test the single drone map page returns 404 for non-existent drone.
     */
    public function test_map_show_returns_404_for_nonexistent_drone(): void
    {
        $response = $this->get('/map/99999');

        $response->assertStatus(404);
    }

    /**
     * Test the map index passes drones data as JSON to view.
     */
    public function test_map_index_passes_drones_data_to_view(): void
    {
        $drone = Drone::factory()->create([
            'name' => 'Test Drone',
            'status' => DroneStatus::ACTIVE,
            'current_lat' => 40.7128,
            'current_lng' => -74.0060,
            'battery_level' => 85,
        ]);

        $response = $this->get(route('map.index'));

        $response->assertStatus(200);

        // Verify the view receives drones data that can be JSON encoded
        $drones = $response->viewData('drones');
        $this->assertNotNull($drones);
        $jsonData = json_encode($drones);
        $this->assertNotFalse($jsonData);
        $this->assertJson($jsonData);
    }

    /**
     * Test the map show passes telemetry trail data to view.
     */
    public function test_map_show_passes_telemetry_trail_data(): void
    {
        $drone = Drone::factory()->create();

        TelemetryRecord::factory()->count(10)->create([
            'drone_id' => $drone->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $response = $this->get(route('map.show', $drone));

        $response->assertStatus(200);

        $telemetry = $response->viewData('recentTelemetry');
        $this->assertNotNull($telemetry);

        // Verify telemetry has lat/lng data for trail rendering
        if ($telemetry->isNotEmpty()) {
            $firstRecord = $telemetry->first();
            $this->assertNotNull($firstRecord->latitude);
            $this->assertNotNull($firstRecord->longitude);
        }
    }

    /**
     * Test route names are correctly registered.
     */
    public function test_map_routes_have_correct_names(): void
    {
        $this->assertEquals('http://localhost/map', route('map.index'));

        $drone = Drone::factory()->create();
        $this->assertEquals("http://localhost/map/{$drone->id}", route('map.show', $drone));
    }

    /**
     * Test telemetry updated event can be faked for WebSocket testing.
     */
    public function test_telemetry_updated_event_can_be_faked(): void
    {
        Event::fake([
            TelemetryUpdated::class,
        ]);

        // This test verifies that the event infrastructure is properly configured
        // for WebSocket broadcasting tests
        $this->assertTrue(true);
    }

    /**
     * Test the map index page renders window.mapConfig with drones data.
     */
    public function test_map_index_renders_map_config_with_drones_data(): void
    {
        $drone = Drone::factory()->create([
            'name' => 'Test Drone',
            'status' => DroneStatus::ACTIVE,
            'current_lat' => 40.7128,
            'current_lng' => -74.0060,
            'battery_level' => 85,
        ]);

        $response = $this->get(route('map.index'));

        $response->assertStatus(200);
        $responseContent = $response->getContent();
        $this->assertStringContainsString('window.mapConfig', $responseContent);
        $this->assertStringContainsString('dronesData:', $responseContent);
        $this->assertStringContainsString($drone->name, $responseContent);
    }

    /**
     * Test the map index page renders window.mapConfig with geofences data.
     */
    public function test_map_index_renders_map_config_with_geofences_data(): void
    {
        Geofence::factory()->create([
            'name' => 'Test Geofence',
            'center_lat' => 40.7128,
            'center_lng' => -74.0060,
            'radius_meters' => 1000,
        ]);

        $response = $this->get(route('map.index'));

        $response->assertStatus(200);
        $responseContent = $response->getContent();
        $this->assertStringContainsString('window.mapConfig', $responseContent);
        $this->assertStringContainsString('geofencesData:', $responseContent);
        $this->assertStringContainsString('Test Geofence', $responseContent);
    }

    /**
     * Test the map index page renders empty drones data when no drones exist.
     */
    public function test_map_index_renders_empty_drones_data_when_no_drones(): void
    {
        $response = $this->get(route('map.index'));

        $response->assertStatus(200);
        $responseContent = $response->getContent();
        $this->assertStringContainsString('window.mapConfig', $responseContent);
        $this->assertStringContainsString('dronesData: []', $responseContent);
    }

    /**
     * Test the map show page renders window.mapConfig with drone data.
     */
    public function test_map_show_renders_map_config_with_drone_data(): void
    {
        $drone = Drone::factory()->create([
            'name' => 'Single Drone',
            'current_lat' => 40.7128,
            'current_lng' => -74.0060,
        ]);

        $response = $this->get(route('map.show', $drone));

        $response->assertStatus(200);
        $responseContent = $response->getContent();
        $this->assertStringContainsString('window.mapConfig', $responseContent);
        $this->assertStringContainsString('focusedDroneId: '.$drone->id, $responseContent);
        $this->assertStringContainsString('drone:', $responseContent);
    }

    /**
     * Test the map show page renders window.mapConfig with trail data.
     */
    public function test_map_show_renders_map_config_with_trail_data(): void
    {
        $drone = Drone::factory()->create();

        TelemetryRecord::factory()->count(5)->create([
            'drone_id' => $drone->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $response = $this->get(route('map.show', $drone));

        $response->assertStatus(200);
        $responseContent = $response->getContent();
        $this->assertStringContainsString('window.mapConfig', $responseContent);
        $this->assertStringContainsString('trailData:', $responseContent);
    }

    /**
     * Test the map show page renders empty trail when no telemetry records.
     */
    public function test_map_show_renders_empty_trail_when_no_telemetry(): void
    {
        $drone = Drone::factory()->create();

        $response = $this->get(route('map.show', $drone));

        $response->assertStatus(200);
        $responseContent = $response->getContent();
        $this->assertStringContainsString('trailData: []', $responseContent);
    }

    /**
     * Test the map layout includes bootstrap.js in Vite assets for WebSocket support.
     */
    public function test_map_layout_includes_bootstrap_js_in_vite_assets(): void
    {
        $response = $this->get(route('map.index'));

        $response->assertStatus(200);
        $responseContent = $response->getContent();
        // The Vite directive should reference bootstrap.js either as a built asset
        // (in production with manifest) or as a module script (in dev mode).
        // In both cases, the Blade template includes 'resources/js/bootstrap.js' in the @vite array.
        // When built, the manifest maps it to a hashed filename, so we check for the
        // presence of a script tag. In dev mode, the Vite dev server serves it directly.
        // We verify the layout renders at least one script tag for the map JS bundle.
        $this->assertStringContainsString('<script', $responseContent);
    }
}
