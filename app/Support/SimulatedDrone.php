<?php

declare(strict_types=1);

namespace App\Support;

final class SimulatedDrone
{
    public function __construct(
        public int $droneId,
        public float $lat,
        public float $lng,
        public float $battery,
        public float $heading,
        public float $speed,
        public string $status = 'active',
    ) {}
}
