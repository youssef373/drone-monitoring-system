<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Geofence;
use Illuminate\Database\Eloquent\Factories\Factory;

class GeofenceFactory extends Factory
{
    protected $model = Geofence::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->city(),
            'center_lat' => $this->faker->latitude(40.0, 41.0),
            'center_lng' => $this->faker->longitude(-74.5, -73.5),
            'radius_meters' => $this->faker->randomFloat(2, 100, 10000),
            'boundary' => null,
        ];
    }

    public function withPolygon(): static
    {
        return $this->state(function (array $attributes) {
            $lat = $attributes['center_lat'];
            $lng = $attributes['center_lng'];
            $offset = 0.01;

            return [
                'radius_meters' => null,
                'boundary' => [
                    [$lat - $offset, $lng - $offset],
                    [$lat + $offset, $lng - $offset],
                    [$lat + $offset, $lng + $offset],
                    [$lat - $offset, $lng + $offset],
                ],
            ];
        });
    }
}
