<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code',
    'name',
    'latitude',
    'longitude',
    'category',
    'water_source',
    'water_body_type',
    'is_active',
    'is_real_time',
    'summary',
    'telemetry_system',
    'gauge_code',
    'owner_org',
    'country',
    'river_basin',
])]
class Station extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
            'is_real_time' => 'boolean',
        ];
    }

    public function capabilities()
    {
        return $this->hasMany(StationCapability::class, 'station_id');
    }
}
