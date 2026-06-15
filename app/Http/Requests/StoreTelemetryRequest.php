<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTelemetryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'drone_id' => ['required', 'integer', 'exists:drones,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'altitude' => ['required', 'numeric', 'min:0'],
            'battery_level' => ['required', 'integer', 'between:0,100'],
            'signal_strength' => ['nullable', 'integer', 'between:0,100'],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'heading' => ['nullable', 'numeric', 'between:0,360'],
            'recorded_at' => ['required', 'date_format:Y-m-d\TH:i:sP'],
        ];
    }
}
