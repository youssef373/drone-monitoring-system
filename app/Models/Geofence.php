<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Geofence extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'boundary',
        'center_lat',
        'center_lng',
        'radius_meters',
    ];

    protected $casts = [
        'boundary' => 'array',
        'center_lat' => 'decimal:8',
        'center_lng' => 'decimal:8',
        'radius_meters' => 'float',
    ];

    public function drones(): HasMany
    {
        return $this->hasMany(Drone::class);
    }

    public function isCircle(): bool
    {
        return $this->radius_meters !== null;
    }

    public function containsPoint(float $lat, float $lng): bool
    {
        if (!$this->isCircle()) {
            return $this->containsPointInPolygon($lat, $lng);
        }

        return $this->containsPointInCircle($lat, $lng);
    }

    /**
     * Check if a point is within the circular geofence using the Haversine formula.
     *
     * Calculates the great-circle distance between the geofence center and the point,
     * accounting for Earth's curvature. Returns true if distance is within radius.
     *
     * @see https://en.wikipedia.org/wiki/Haversine_formula
     *
     * @param float $lat Point latitude in decimal degrees
     * @param float $lng Point longitude in decimal degrees
     *
     * @return bool True if point is inside the circle, false otherwise
     */
    private function containsPointInCircle(float $lat, float $lng): bool
    {
        $earthRadius = 6371000;

        $latDiff = deg2rad($lat - (float) $this->center_lat);
        $lngDiff = deg2rad($lng - (float) $this->center_lng);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos(deg2rad((float) $this->center_lat)) * cos(deg2rad($lat)) *
            sin($lngDiff / 2) * sin($lngDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance <= $this->radius_meters;
    }

    /**
     * Check if a point is within the polygon geofence using the ray-casting algorithm.
     *
     * Casts a horizontal ray from the point to the right and counts how many polygon
     * edges it crosses. Odd count = inside, even count = outside.
     *
     * @see https://en.wikipedia.org/wiki/Point_in_polygon#Ray_casting_algorithm
     *
     * @param float $lat Point latitude in decimal degrees
     * @param float $lng Point longitude in decimal degrees
     *
     * @return bool True if point is inside the polygon, false otherwise
     */
    private function containsPointInPolygon(float $lat, float $lng): bool
    {
        if (empty($this->boundary)) {
            return false;
        }

        $polygon = $this->boundary;
        $inside = false;
        $points = count($polygon);

        for ($i = 0, $j = $points - 1; $i < $points; $j = $i++) {
            $xi = $polygon[$i][0];
            $yi = $polygon[$i][1];
            $xj = $polygon[$j][0];
            $yj = $polygon[$j][1];

            if ((($yi > $lat) != ($yj > $lat)) &&
                ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi)) {
                $inside = !$inside;
            }
        }

        return $inside;
    }
}
