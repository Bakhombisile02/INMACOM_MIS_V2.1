<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'reference',
    'hazard_code',
    'title',
    'incident_status',
    'reported_at',
    'submitted_by_id',
    'submitted_at',
    'review_status',
    'description',
    'severity_level',
    'area_id',
    'latitude',
    'longitude',
    'affected_radius_km',
    'occurred_at',
    'resolved_at',
    'reported_by_id',
    'reporter_name',
    'reporter_contact',
    'incident_commander_id',
    'reviewed_by_id',
    'reviewed_at',
    'review_notes',
    'is_self_override',
])]
class DisasterIncident extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'submitted_at' => 'datetime',
            'occurred_at' => 'datetime',
            'resolved_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'affected_radius_km' => 'float',
            'is_self_override' => 'boolean',
        ];
    }

    public function stations(): BelongsToMany
    {
        return $this->belongsToMany(Station::class, 'incident_stations', 'incident_id', 'station_id')
            ->withPivot('role', 'notes');
    }

    public function managementArea(): BelongsTo
    {
        return $this->belongsTo(ManagementArea::class, 'area_id');
    }
}
