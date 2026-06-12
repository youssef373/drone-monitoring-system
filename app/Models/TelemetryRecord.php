<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class TelemetryRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'drone_id',
        'latitude',
        'longitude',
        'altitude',
        'battery_level',
        'signal_strength',
        'speed',
        'heading',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'altitude' => 'float',
        'battery_level' => 'integer',
        'signal_strength' => 'integer',
        'speed' => 'float',
        'heading' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function drone(): BelongsTo
    {
        return $this->belongsTo(Drone::class);
    }

    public function scopeForDrone(Builder $query, int $droneId): Builder
    {
        return $query->where('drone_id', $droneId);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('recorded_at', 'desc');
    }
}
