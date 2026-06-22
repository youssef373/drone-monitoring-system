<?php

namespace Database\Seeders;

use App\Models\Geofence;
use App\Models\Drone;
use App\Enums\DroneStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DroneSimulatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data (optional, but follows reseed.php logic)
        Drone::query()->delete();
        Geofence::query()->delete();

        $g = Geofence::create([
            'name' => 'NYC Operations Zone',
            'center_lat' => 40.7128,
            'center_lng' => -74.0060,
            'radius_meters' => 500,
        ]);

        for ($i = 1; $i <= 3; $i++) {
            Drone::create([
                'name' => "Simulator Drone {$i}",
                'type' => 'quadcopter',
                'status' => DroneStatus::ACTIVE,
                'geofence_id' => $g->id,
                'current_lat' => 40.7128,
                'current_lng' => -74.0060,
                'battery_level' => 100,
            ]);
        }

        $this->command->info("Geofence ID: {$g->id}");
        $this->command->info("Drones created: " . Drone::count());
    }
}
