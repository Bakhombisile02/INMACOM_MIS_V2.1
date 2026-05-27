<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Role check handled in controller
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:stations,code'],
            'name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'category' => ['required', 'string', 'max:100'],
            'water_source' => ['required', 'string', 'max:100'],
            'water_body_type' => ['required', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
            'is_real_time' => ['required', 'boolean'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'telemetry_system' => ['nullable', 'string', 'max:255'],
            'gauge_code' => ['nullable', 'string', 'max:100'],
            'owner_org' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'river_basin' => ['nullable', 'string', 'max:255'],
        ];
    }
}
