<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTelemetryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class TelemetryController extends Controller
{
    public function store(StoreTelemetryRequest $request): JsonResponse
    {
        $jobId = Str::uuid()->toString();

        // ProcessTelemetryJob is implemented in feature 004.
        // Swap this closure dispatch once that feature is merged.
        dispatch(fn () => null);

        return response()->json([
            'message' => 'Telemetry accepted for processing',
            'job_id'  => $jobId,
        ], 202);
    }
}
