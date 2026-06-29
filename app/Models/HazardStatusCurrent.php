<?php

namespace App\Models;

use App\Queries\HazardStatusQuery;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'hazard_code',
    'area_id',
    'level_code',
    'calculated_at',
    'score',
    'next_review_at',
    'calculated_by_id',
    'calculation_notes',
])]
class HazardStatusCurrent extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hazard_status_current';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'calculated_at' => 'datetime',
            'next_review_at' => 'datetime',
        ];
    }

    public function area()
    {
        return $this->belongsTo(ManagementArea::class, 'area_id');
    }

    public function calculatedBy()
    {
        return $this->belongsTo(User::class, 'calculated_by_id');
    }

    /**
     * Start a fluent hazard status query.
     */
    public static function customQuery(): HazardStatusQuery
    {
        return HazardStatusQuery::query();
    }
}
