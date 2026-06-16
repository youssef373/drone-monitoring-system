<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Drone;
use App\Models\TelemetryRecord;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TelemetryUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Drone $drone,
        public readonly TelemetryRecord $telemetry,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('drone.'.$this->drone->id);
    }

    public function broadcastAs(): string
    {
        return 'telemetry.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'drone_id' => $this->drone->id,
            'name' => $this->drone->name,
            'latitude' => $this->telemetry->latitude,
            'longitude' => $this->telemetry->longitude,
            'altitude' => $this->telemetry->altitude,
            'battery_level' => $this->telemetry->battery_level,
            'status' => $this->drone->status,
            'recorded_at' => $this->telemetry->recorded_at->toIso8601String(),
        ];
    }
}
