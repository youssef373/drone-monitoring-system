<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\DroneStatus;
use App\Models\Drone;
use App\Support\GeoMath;
use App\Support\SimulatedDrone;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Random\RandomException;
use Throwable;

/**
 * Simulate telemetry data for one or more drones.
 *
 * This command creates or reuses existing drones, then loops to update
 * position, battery, speed, and heading before POSTing telemetry records
 * to the API endpoint.
 *
 * @see SimulatedDrone
 */

/*
 * php artisan simulate:telemetry 3 --url=http://nginx/api/telemetry --interval=3 --duration=0
 */
class SimulateTelemetryCommand extends Command
{
    protected $signature = 'simulate:telemetry
        {count=1 : Number of drones to simulate}
        {--interval=5 : Seconds between telemetry updates}
        {--base-lat=40.7128 : Base latitude}
        {--base-lng=-74.0060 : Base longitude}
        {--duration=60 : Duration in minutes (0 = infinite)}
        {--battery-drain=0.1 : Battery drain per update (%)}
        {--url= : Full URL to POST telemetry to (overrides route helper, useful inside Docker)}';

    protected $description = 'Simulate drone telemetry data';

    /** Whether the simulation loop should continue running. */
    private bool $running = true;

    /**
     * Optional closure to replace the built-in sleep() call.
     *
     * Useful for tests to avoid real-time delays.
     */
    public static ?Closure $sleeper = null;

    /**
     * Execute the console command.
     *
     * @return int The exit status code
     */
    public function handle(): int
    {
        $count = (int) $this->argument('count');
        if ($count < 1) {
            $this->error('Count must be at least 1');

            return self::FAILURE;
        }

        $interval = (int) $this->option('interval');
        if ($interval < 1) {
            $this->error('Interval must be at least 1 second');

            return self::FAILURE;
        }

        $baseLat = (float) $this->option('base-lat');
        $baseLng = (float) $this->option('base-lng');
        $duration = (int) $this->option('duration');
        $batteryDrain = (float) $this->option('battery-drain');

        $drones = $this->getOrCreateDrones($count, $baseLat, $baseLng);

        $this->registerShutdownHandler();

        $endTime = $duration > 0 ? now()->addMinutes($duration) : null;
        $totalUpdates = 0;
        $totalBattery = 0.0;

        while ($this->running) {
            if ($endTime !== null && now()->greaterThanOrEqualTo($endTime)) {
                break;
            }

            foreach ($drones as $drone) {
                $this->updateDroneState($drone, $baseLat, $baseLng, $batteryDrain);
                $this->sendTelemetry($drone);
                $this->outputLine($drone);
                $totalUpdates++;
                $totalBattery += $drone->battery;
            }

            $this->sleepInterval($interval);
        }

        $this->outputSummary($totalUpdates, $totalBattery);

        return self::SUCCESS;
    }

    /**
     * Fetch or create the requested number of drones.
     *
     * Reuses existing drones in ID order, then creates new simulated drones
     * if needed.
     *
     * @param  int  $count  Number of drones to simulate
     * @param  float  $baseLat  Base latitude for the simulation area
     * @param  float  $baseLng  Base longitude for the simulation area
     * @return array<int, SimulatedDrone>
     */
    private function getOrCreateDrones(int $count, float $baseLat, float $baseLng): array
    {
        $existingIds = Drone::orderBy('id')->pluck('id')->all();
        $missing = max(0, $count - count($existingIds));

        for ($i = 1; $i <= $missing; $i++) {
            $drone = Drone::create([
                'name' => "Simulator Drone {$i}",
                'type' => 'quadcopter',
                'status' => DroneStatus::ACTIVE,
            ]);
            $existingIds[] = $drone->id;
        }

        $selectedIds = array_slice($existingIds, 0, $count);

        return array_map(
            fn (int $id) => new SimulatedDrone(
                droneId: $id,
                lat: $baseLat,
                lng: $baseLng,
                battery: 100.0,
                heading: (float) random_int(0, 360),
                speed: $this->randomSpeed(),
            ),
            $selectedIds
        );
    }

    /**
     * Register a signal handler to stop the loop gracefully.
     *
     * Traps SIGINT (Ctrl+C) so the command can exit cleanly.
     */
    private function registerShutdownHandler(): void
    {
        $signal = defined('SIGINT') ? SIGINT : 2;

        $this->trap($signal, function (int $signal): void {
            $this->running = false;
        });
    }

    /**
     * Update the simulated state of a single drone.
     *
     * Adjusts heading and speed, moves the drone, drains battery, and
     * sets an emergency status when the battery drops below 10%.
     *
     * @param  SimulatedDrone  $drone  The drone state object
     * @param  float  $baseLat  Base latitude for boundary clamping
     * @param  float  $baseLng  Base longitude for boundary clamping
     * @param  float  $batteryDrain  Battery percentage drained per update
     *
     * @throws RandomException
     */
    private function updateDroneState(SimulatedDrone $drone, float $baseLat, float $baseLng, float $batteryDrain): void
    {
        $drone->heading = $this->randomHeading($drone->heading);
        $drone->speed = $this->randomSpeed();
        [$newLat, $newLng] = GeoMath::move($drone->lat, $drone->lng, $drone->heading, $drone->speed, (int) $this->option('interval'));
        [$drone->lat, $drone->lng] = GeoMath::clampToBounds($newLat, $newLng, $baseLat, $baseLng);
        $drone->battery = max(0.0, $drone->battery - $batteryDrain);
        $drone->status = $drone->battery < 10.0 ? 'emergency' : 'active';
    }

    /**
     * Send the current drone state to the telemetry API.
     *
     * Logs a warning if the API responds with a non-2xx status or if the
     * request fails entirely.
     *
     * @param  SimulatedDrone  $drone  The drone to send telemetry for
     *
     * @throws RandomException
     */
    private function sendTelemetry(SimulatedDrone $drone): void
    {
        $payload = [
            'drone_id' => $drone->droneId,
            'latitude' => round($drone->lat, 6),
            'longitude' => round($drone->lng, 6),
            'altitude' => random_int(30, 100),
            'battery_level' => (int) round($drone->battery),
            'signal_strength' => random_int(60, 100),
            'speed' => round($drone->speed, 2),
            'heading' => round($drone->heading, 2),
            'recorded_at' => now()->toIso8601String(),
        ];

        try {
            $url = $this->option('url') ?: route('api.telemetry.store');
            $response = Http::post($url, $payload);

            if (! $response->successful()) {
                Log::warning('Telemetry POST returned non-2xx status', [
                    'status' => $response->status(),
                    'drone_id' => $drone->droneId,
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Telemetry POST failed', [
                'drone_id' => $drone->droneId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Output a formatted line for the current drone state.
     *
     * @param  SimulatedDrone  $drone  The drone to display
     */
    private function outputLine(SimulatedDrone $drone): void
    {
        $this->info(sprintf(
            '[%s] Drone %d: Lat %.4f, Lng %.4f, Bat %d%%, Status: %s',
            now()->format('Y-m-d H:i:s'),
            $drone->droneId,
            $drone->lat,
            $drone->lng,
            (int) round($drone->battery),
            $drone->status,
        ));
    }

    /**
     * Output a summary after the simulation loop ends.
     *
     * @param  int  $totalUpdates  Total number of telemetry updates sent
     * @param  float  $totalBattery  Sum of all battery levels across updates
     */
    private function outputSummary(int $totalUpdates, float $totalBattery): void
    {
        $averageBattery = $totalUpdates > 0 ? $totalBattery / $totalUpdates : 0.0;

        $this->info(sprintf(
            'Simulation complete. Total updates: %d, Average battery: %.1f%%',
            $totalUpdates,
            $averageBattery
        ));
    }

    /**
     * Generate a new heading near the current heading.
     *
     * @param  float  $currentHeading  The current heading in degrees
     * @return float The new heading in degrees (0-360)
     *
     * @throws RandomException
     */
    private function randomHeading(float $currentHeading): float
    {
        return fmod($currentHeading + (float) random_int(-30, 30) + 360.0, 360.0);
    }

    /**
     * Generate a random speed for the drone.
     *
     * @return float The speed in meters per second
     *
     * @throws RandomException
     */
    private function randomSpeed(): float
    {
        return (float) random_int(5, 15);
    }

    /**
     * Pause execution for the configured interval.
     *
     * Uses the injected sleeper closure during testing; otherwise sleeps
     * for the given number of seconds.
     *
     * @param  int  $seconds  Number of seconds to pause
     */
    private function sleepInterval(int $seconds): void
    {
        if (static::$sleeper !== null) {
            (static::$sleeper)($seconds);

            return;
        }

        sleep($seconds);
    }
}
