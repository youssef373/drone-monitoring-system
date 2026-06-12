<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Models\Alert;
use App\Models\Drone;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlertFactory extends Factory
{
    protected $model = Alert::class;

    public function definition(): array
    {
        $types = AlertType::cases();
        $type = $this->faker->randomElement($types);

        $messages = [
            AlertType::LOW_BATTERY->value => 'Battery level below 25%',
            AlertType::CRITICAL_BATTERY->value => 'Battery level critically low (< 10%)',
            AlertType::GEOFENCE_VIOLATION->value => 'Drone has left the designated geofence',
            AlertType::SIGNAL_LOSS->value => 'Communication signal lost',
            AlertType::EMERGENCY->value => 'Emergency situation detected',
        ];

        return [
            'drone_id' => Drone::factory(),
            'type' => $type,
            'message' => $messages[$type->value],
            'severity' => $this->faker->randomElement(AlertSeverity::cases()),
            'resolved_at' => $this->faker->boolean(50) ? now() : null,
        ];
    }

    public function unresolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'resolved_at' => null,
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => AlertSeverity::CRITICAL,
        ]);
    }
}
