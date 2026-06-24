<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Helper for simple geospatial calculations used in drone simulations.
 *
 * This class approximates movement on a small scale using a flat-Earth
 * projection and keeps simulated coordinates within a bounded box.
 */
final class GeoMath
{
    /** Approximate number of meters per degree of latitude. */
    private const METERS_PER_DEGREE_LAT = 111_320.0;

    /** Bounding box radius in degrees around the base coordinates. */
    private const BOUND_RADIUS = 0.009;

    /**
     * Calculate a new latitude/longitude after moving from a starting point.
     *
     * Uses a simplified flat-Earth projection. The distance traveled is
     * speed (m/s) multiplied by the interval (seconds). The longitude
     * delta is adjusted by the cosine of the latitude to account for
     * meridian convergence.
     *
     * @param  float  $lat  Starting latitude in degrees
     * @param  float  $lng  Starting longitude in degrees
     * @param  float  $heading  Direction of travel in degrees (0-360)
     * @param  float  $speed  Speed in meters per second
     * @param  int  $interval  Time traveled in seconds
     * @return array{0: float, 1: float} New [latitude, longitude]
     */
    public static function move(float $lat, float $lng, float $heading, float $speed, int $interval): array
    {
        $distance = $speed * $interval;
        $headingRad = deg2rad($heading);
        $latRad = deg2rad($lat);

        $deltaLat = ($distance * cos($headingRad)) / self::METERS_PER_DEGREE_LAT;
        $deltaLng = ($distance * sin($headingRad)) / (self::METERS_PER_DEGREE_LAT * cos($latRad));

        return [$lat + $deltaLat, $lng + $deltaLng];
    }

    /**
     * Clamp coordinates to a square boundary around a base point.
     *
     * Keeps simulated drones from drifting too far away during long runs.
     *
     * @param  float  $lat  Latitude to clamp
     * @param  float  $lng  Longitude to clamp
     * @param  float  $baseLat  Center latitude of the boundary box
     * @param  float  $baseLng  Center longitude of the boundary box
     * @return array{0: float, 1: float} Clamped [latitude, longitude]
     */
    public static function clampToBounds(float $lat, float $lng, float $baseLat, float $baseLng): array
    {
        $minLat = $baseLat - self::BOUND_RADIUS;
        $maxLat = $baseLat + self::BOUND_RADIUS;
        $minLng = $baseLng - self::BOUND_RADIUS;
        $maxLng = $baseLng + self::BOUND_RADIUS;

        return [
            max($minLat, min($maxLat, $lat)),
            max($minLng, min($maxLng, $lng)),
        ];
    }
}
