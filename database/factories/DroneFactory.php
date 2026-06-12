<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DroneStatus;
use App\Models\Drone;
use App\Models\Geofence;
use Illuminate\Database\Eloquent\Factories\Factory;

class DroneFactory extends Factory
{
    protected $model = Drone::class;

    public function definition(): array
    {
        $types = ['DJI Mavic 3', 'DJI Air 2S', 'DJI Mini 3', 'Parrot Anafi', 'Autel EVO Nano'];

        return [
            'name' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{3}'),
            'type' => $this->faker->randomElement($types),
            'status' => $this->faker->randomElement(DroneStatus::cases()),
            'current_lat' => $this->faker->latitude(40.0, 41.0),
            'current_lng' => $this->faker->longitude(-74.5, -73.5),
            'current_altitude' => $this->faker->randomFloat(2, 10, 200),
            'battery_level' => $this->faker->numberBetween(0, 100),
            'geofence_id' => Geofence::factory(),
            'last_telemetry_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DroneStatus::ACTIVE,
        ]);
    }

    public function emergency(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DroneStatus::EMERGENCY,
        ]);
    }

    public function lowBattery(): static
    {
        return $this->state(fn (array $attributes) => [
            'battery_level' => $this->faker->numberBetween(5, 20),
        ]);
    }
}
