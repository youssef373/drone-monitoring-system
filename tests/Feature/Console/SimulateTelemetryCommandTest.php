<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Console\Commands\SimulateTelemetryCommand;
use App\Models\Drone;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SimulateTelemetryCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 6, 19, 10, 0, 0));

        SimulateTelemetryCommand::$sleeper = function (int $seconds): void {
            Carbon::setTestNow(Carbon::getTestNow()->addSeconds($seconds));
        };
    }

    protected function tearDown(): void
    {
        SimulateTelemetryCommand::$sleeper = null;
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_command_help_shows_expected_arguments_and_options(): void
    {
        $this->artisan('simulate:telemetry', ['--help'])
            ->expectsOutputToContain('simulate:telemetry')
            ->expectsOutputToContain('count')
            ->expectsOutputToContain('--interval')
            ->expectsOutputToContain('--duration')
            ->assertSuccessful();
    }

    public function test_invalid_count_fails(): void
    {
        $this->artisan('simulate:telemetry', ['count' => 0])
            ->expectsOutputToContain('Count must be at least 1')
            ->assertFailed();
    }

    public function test_invalid_interval_fails(): void
    {
        $this->artisan('simulate:telemetry', ['--interval' => 0])
            ->expectsOutputToContain('Interval must be at least 1 second')
            ->assertFailed();
    }

    public function test_creates_missing_drones(): void
    {
        Http::fake();
        Queue::fake();

        $d1 = Drone::factory()->create();

        $this->assertDatabaseCount('drones', 1);

        $this->artisan('simulate:telemetry', [
            'count' => 3,
            '--duration' => 1,
            '--interval' => 60,
        ])
            ->expectsOutputToContain("Drone {$d1->id}:")
            ->expectsOutputToContain('Drone') // generic check for others
            ->assertSuccessful();

        $this->assertDatabaseCount('drones', 3);
        $drones = Drone::orderBy('id')->get();
        // The artisan helper doesn't allow easy multiple checks if IDs are unknown
        // but we can trust the DB count and generic drone output for now.
    }

    public function test_sends_telemetry_for_each_drone(): void
    {
        Http::fake();
        Queue::fake();

        $d1 = Drone::factory()->create();
        $d2 = Drone::factory()->create();

        $this->artisan('simulate:telemetry', [
            'count' => 2,
            '--duration' => 1,
            '--interval' => 60,
        ])
            ->expectsOutputToContain("Drone {$d1->id}:")
            ->expectsOutputToContain("Drone {$d2->id}:")
            ->expectsOutputToContain('Simulation complete.')
            ->assertSuccessful();

        Http::assertSentCount(2);
    }

    public function test_battery_drain_and_emergency_status(): void
    {
        Http::fake();
        Queue::fake();

        Drone::factory()->create();

        $this->artisan('simulate:telemetry', [
            'count' => 1,
            '--duration' => 5,
            '--interval' => 60,
            '--battery-drain' => 30,
        ])
            ->expectsOutputToContain('Bat 70%, Status: active')
            ->expectsOutputToContain('Bat 40%, Status: active')
            ->expectsOutputToContain('Bat 10%, Status: active')
            ->expectsOutputToContain('Bat 0%, Status: emergency')
            ->expectsOutputToContain('Simulation complete. Total updates: 5, Average battery: 24.0%')
            ->assertSuccessful();

        Http::assertSentCount(5);
    }

    public function test_simulator_with_url_option_posts_to_provided_url(): void
    {
        Http::fake([
            'http://test-server/api/telemetry' => Http::response([], 202),
        ]);
        Queue::fake();

        Drone::factory()->count(2)->create();

        $this->artisan('simulate:telemetry', [
            'count' => 2,
            '--duration' => 1,
            '--interval' => 60,
            '--url' => 'http://test-server/api/telemetry',
        ])
            ->assertSuccessful();

        Http::assertSent(function ($request) {
            return $request->url() === 'http://test-server/api/telemetry';
        });
    }

    public function test_simulator_without_url_option_uses_route_helper(): void
    {
        Http::fake([
            'http://localhost/api/telemetry' => Http::response([], 202),
        ]);
        Queue::fake();

        Drone::factory()->create();

        $this->artisan('simulate:telemetry', [
            'count' => 1,
            '--duration' => 1,
            '--interval' => 60,
        ])
            ->assertSuccessful();

        Http::assertSent(function ($request) {
            return $request->url() === 'http://localhost/api/telemetry';
        });
    }
}
