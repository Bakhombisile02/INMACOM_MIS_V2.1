<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'station_id',
    'submitted_by_id',
    'status',
    'change_type',
    'proposed_changes',
    'reviewed_by_id',
    'reviewed_at',
    'review_notes',
    'is_self_override',
])]
class StationRevision extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const CHANGE_TYPE_CREATE = 'create';

    public const CHANGE_TYPE_UPDATE = 'update';

    public const CHANGE_TYPE_DELETE = 'delete';

    protected function casts(): array
    {
        return [
            'proposed_changes' => 'array',
            'reviewed_at' => 'datetime',
            'is_self_override' => 'boolean',
        ];
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }
}
