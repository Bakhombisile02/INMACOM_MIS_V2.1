<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Queries\StationMeasurementQuery;

#[Fillable([
    'station_id',
    'measurement_type',
    'parameter_id',
    'fsc',
    'value',
    'unit',
    'date',
    'status',
    'submitted_by_id',
    'submitted_at',
    'reviewed_by_id',
    'reviewed_at',
    'review_notes',
    'is_self_override',
])]
class Measurement extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'value' => 'float',
            'fsc' => 'float',
            'date' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'is_self_override' => 'boolean',
        ];
    }

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id');
    }

    public function parameter()
    {
        return $this->belongsTo(WaterQualityParameter::class, 'parameter_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    /**
     * Start a fluent station measurement query.
     */
    public static function customQuery(): StationMeasurementQuery
    {
        return StationMeasurementQuery::query();
    }
}
