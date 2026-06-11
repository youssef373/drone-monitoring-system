---
name: drone-monitoring-system
description: Specialized guidance for drone monitoring systems including telemetry handling, geospatial data, real-time updates, mission planning, geofencing, and regulatory compliance considerations.
license: MIT
metadata:
  author: Project
  version: "1.0.0"
  domain: backend
  triggers: drone, UAV, telemetry, flight, mission, waypoint, geofence, altitude, battery, GPS, map, real-time, aviation, FAA, DJI
  role: specialist
  scope: implementation
  output-format: code
---

# Drone Monitoring System Specialist

Domain-specific guidance for building UAV monitoring and fleet management systems.

## Core Entities

### Drone (Aircraft)
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Drone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'serial_number',
        'model',
        'status',           // idle, flying, charging, maintenance
        'battery_percentage',
        'current_latitude',
        'current_longitude',
        'current_altitude', // meters
        'pilot_id',
        'last_seen_at',
    ];

    protected $casts = [
        'battery_percentage' => 'integer',
        'current_latitude' => 'decimal:8',
        'current_longitude' => 'decimal:8',
        'current_altitude' => 'decimal:2',
        'last_seen_at' => 'datetime',
        'status' => DroneStatus::class, // backed enum
    ];

    public function pilot(): BelongsTo
    {
        return $this->belongsTo(Pilot::class);
    }

    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class);
    }

    public function telemetry(): HasMany
    {
        return $this->hasMany(Telemetry::class)->latest();
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class)->latest();
    }

    // Scope: active drones needing monitoring
    public function scopeActive($query)
    {
        return $query->whereIn('status', [DroneStatus::FLYING, DroneStatus::IDLE]);
    }

    // Scope: low battery alert
    public function scopeLowBattery($query, int $threshold = 20)
    {
        return $query->where('battery_percentage', '<=', $threshold);
    }
}
```

### Mission (Flight Plan)
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Mission extends Model
{
    protected $fillable = [
        'drone_id',
        'pilot_id',
        'name',
        'status',           // planned, active, completed, aborted
        'started_at',
        'completed_at',
        'total_distance',   // meters
        'max_altitude',     // meters
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_distance' => 'decimal:2',
        'max_altitude' => 'decimal:2',
        'status' => MissionStatus::class,
    ];

    public function drone(): BelongsTo
    {
        return $this->belongsTo(Drone::class);
    }

    public function pilot(): BelongsTo
    {
        return $this->belongsTo(Pilot::class);
    }

    public function waypoints(): HasMany
    {
        return $this->hasMany(Waypoint::class)->orderBy('sequence');
    }

    public function telemetry(): HasMany
    {
        return $this->hasMany(Telemetry::class);
    }
}
```

### Telemetry (Real-time Data)
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Telemetry extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'drone_id',
        'mission_id',
        'latitude',
        'longitude',
        'altitude',
        'speed',            // m/s
        'heading',          // degrees 0-360
        'battery_percentage',
        'signal_strength',  // dBm
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'altitude' => 'decimal:2',
        'speed' => 'decimal:2',
        'heading' => 'integer',
        'battery_percentage' => 'integer',
        'signal_strength' => 'integer',
        'recorded_at' => 'datetime',
    ];

    public function drone(): BelongsTo
    {
        return $this->belongsTo(Drone::class);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }
}
```

### Geofence (Restricted Zones)
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Geofence extends Model
{
    protected $fillable = [
        'name',
        'type',             // restricted, caution, authorized
        'coordinates',      // GeoJSON polygon
        'max_altitude',     // feet or meters
        'effective_from',
        'effective_until',
        'is_active',
    ];

    protected $casts = [
        'coordinates' => 'array',
        'max_altitude' => 'decimal:2',
        'effective_from' => 'datetime',
        'effective_until' => 'datetime',
        'is_active' => 'boolean',
        'type' => GeofenceType::class,
    ];
}
```

## Key Considerations

### Data Retention
- Telemetry generates high volume — consider archiving old data
- Use time-series optimized storage for telemetry
- Aggregate daily statistics for historical reports

### Real-time Updates
- Use Laravel Broadcasting (WebSockets) for live telemetry
- Queue telemetry ingestion for high-frequency data
- Implement rate limiting on telemetry endpoints

### Geospatial Queries
- Store coordinates with appropriate precision (decimal 8)
- Use spatial indexes if using MySQL 8+ or PostgreSQL with PostGIS
- Validate coordinate bounds (-90 to 90 lat, -180 to 180 lon)

### Alerts & Safety
- Alert on: low battery, geofence breach, signal loss, altitude limits
- Implement alert severity levels (info, warning, critical)
- Log all safety events with timestamps

### Regulatory Compliance
- Store pilot certifications and expiration dates
- Track flight logs for regulatory reporting
- Respect airspace restrictions (check geofences before mission start)

## Enums

```php
<?php

namespace App\Enums;

enum DroneStatus: string
{
    case IDLE = 'idle';
    case FLYING = 'flying';
    case CHARGING = 'charging';
    case MAINTENANCE = 'maintenance';
    case OFFLINE = 'offline';
}

enum MissionStatus: string
{
    case PLANNED = 'planned';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case ABORTED = 'aborted';
}

enum GeofenceType: string
{
    case RESTRICTED = 'restricted';  // No-fly zone
    case CAUTION = 'caution';        // Warning zone
    case AUTHORIZED = 'authorized'; // Designated flight area
}

enum AlertSeverity: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case CRITICAL = 'critical';
}
```

## Telemetry Ingestion Endpoint

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreTelemetryRequest;
use App\Models\Telemetry;
use App\Events\TelemetryReceived;
use Illuminate\Http\JsonResponse;

final class TelemetryController
{
    public function store(StoreTelemetryRequest $request): JsonResponse
    {
        $telemetry = Telemetry::create($request->validated());

        // Broadcast to dashboard
        broadcast(new TelemetryReceived($telemetry))->toOthers();

        // Update drone current position
        $telemetry->drone->update([
            'current_latitude' => $telemetry->latitude,
            'current_longitude' => $telemetry->longitude,
            'current_altitude' => $telemetry->altitude,
            'battery_percentage' => $telemetry->battery_percentage,
            'last_seen_at' => now(),
        ]);

        return response()->json(['status' => 'recorded'], 201);
    }
}
```

## Validation Rules

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreTelemetryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('drone'));
    }

    public function rules(): array
    {
        return [
            'drone_id' => ['required', 'exists:drones,id'],
            'mission_id' => ['nullable', 'exists:missions,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'altitude' => ['required', 'numeric', 'min:0'],
            'speed' => ['required', 'numeric', 'min:0'],
            'heading' => ['required', 'integer', 'between:0,360'],
            'battery_percentage' => ['required', 'integer', 'between:0,100'],
            'signal_strength' => ['required', 'integer', 'max:0'], // dBm is negative
            'recorded_at' => ['required', 'date'],
        ];
    }
}
```
