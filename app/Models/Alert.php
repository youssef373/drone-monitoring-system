<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'drone_id',
        'type',
        'message',
        'severity',
        'resolved_at',
    ];

    protected $casts = [
        'type' => AlertType::class,
        'severity' => AlertSeverity::class,
        'resolved_at' => 'datetime',
    ];

    public function drone(): BelongsTo
    {
        return $this->belongsTo(Drone::class);
    }

    public function resolve(): void
    {
        $this->resolved_at = now();
        $this->save();
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('severity', AlertSeverity::CRITICAL);
    }

    public function scopeForType(Builder $query, AlertType $type): Builder
    {
        return $query->where('type', $type);
    }
}
