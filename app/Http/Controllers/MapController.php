<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Drone;
use App\Models\Geofence;
use Illuminate\View\View;

class MapController extends Controller
{
    /**
     * Display the map with all drones.
     */
    public function index(): View
    {
        $drones = Drone::with('geofence')->get();
        $geofences = Geofence::all();

        return view('map.index', compact('drones', 'geofences'));
    }

    /**
     * Display the map focused on a single drone.
     */
    public function show(Drone $drone): View
    {
        $drone->load('geofence');

        $recentTelemetry = $drone->telemetryRecords()
            ->recent()
            ->limit(50)
            ->get(['latitude', 'longitude', 'altitude', 'battery_level', 'recorded_at']);

        $activeAlerts = $drone->alerts()
            ->whereNull('resolved_at')
            ->latest()
            ->get();

        return view('map.show', compact('drone', 'recentTelemetry', 'activeAlerts'));
    }
}
