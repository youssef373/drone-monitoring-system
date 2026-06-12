<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DroneStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Drone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'status',
        'current_lat',
        'current_lng',
        'current_altitude',
        'battery_level',
        'geofence_id',
        'last_telemetry_at',
    ];

    protected $casts = [
        'status' => DroneStatus::class,
        'battery_level' => 'integer',
        'current_lat' => 'decimal:8',
        'current_lng' => 'decimal:8',
        'current_altitude' => 'float',
        'last_telemetry_at' => 'datetime',
    ];

    public function geofence(): BelongsTo
    {
        return $this->belongsTo(Geofence::class);
    }

    public function telemetryRecords(): HasMany
    {
        return $this->hasMany(TelemetryRecord::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function updateLocation(float $lat, float $lng, ?float $altitude = null): void
    {
        $this->current_lat = $lat;
        $this->current_lng = $lng;

        if ($altitude !== null) {
            $this->current_altitude = $altitude;
        }

        $this->save();
    }

    public function isInEmergency(): bool
    {
        return $this->status === DroneStatus::EMERGENCY;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', DroneStatus::ACTIVE);
    }

    public function scopeLowBattery(Builder $query): Builder
    {
        return $query->where('battery_level', '<', 25);
    }
}
