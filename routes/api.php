<?php

use App\Http\Controllers\Api\TelemetryController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:telemetry')->group(function (): void {
    Route::post('/telemetry', [TelemetryController::class, 'store'])->name('api.telemetry.store');
});
