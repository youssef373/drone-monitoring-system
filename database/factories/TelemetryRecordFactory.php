<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Drone;
use App\Models\TelemetryRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class TelemetryRecordFactory extends Factory
{
    protected $model = TelemetryRecord::class;

    public function definition(): array
    {
        return [
            'drone_id' => Drone::factory(),
            'latitude' => $this->faker->latitude(40.0, 41.0),
            'longitude' => $this->faker->longitude(-74.5, -73.5),
            'altitude' => $this->faker->randomFloat(2, 10, 200),
            'battery_level' => $this->faker->numberBetween(0, 100),
            'signal_strength' => $this->faker->numberBetween(60, 100),
            'speed' => $this->faker->randomFloat(2, 0, 30),
            'heading' => $this->faker->randomFloat(2, 0, 360),
            'recorded_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ];
    }
}
