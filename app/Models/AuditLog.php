<?php

namespace App\Models;

use App\Queries\AuditLogQuery;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'actor_id',
    'action_type',
    'entity_type',
    'entity_id',
    'entity_label',
    'previous_state',
    'new_state',
    'reason',
    'actor_ip',
    'occurred_at',
])]
class AuditLog extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'audit_logs';

    protected function casts(): array
    {
        return [
            'previous_state' => 'array',
            'new_state' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Start a fluent audit log query.
     */
    public static function customQuery(): AuditLogQuery
    {
        return AuditLogQuery::query();
    }
}
