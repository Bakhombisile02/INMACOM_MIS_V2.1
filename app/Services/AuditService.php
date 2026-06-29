<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class AuditService
{
    // Action type constants (DDR Module 8 aligned)
    public const ACTION_SELF_APPROVAL = 'self_approval';

    public const ACTION_MEASUREMENT_APPROVED = 'measurement_approved';

    public const ACTION_MEASUREMENT_REJECTED = 'measurement_rejected';

    public const ACTION_MEASUREMENT_IMPORTED = 'measurement_imported';

    public const ACTION_STATION_REVISION_APPROVED = 'station_revision_approved';

    public const ACTION_STATION_REVISION_REJECTED = 'station_revision_rejected';

    public const ACTION_ROLE_CHANGE = 'role_change';

    public const ACTION_USER_DELETED = 'user_deleted';

    public const ACTION_RECORD_DEACTIVATION = 'record_deactivation';

    public const ACTION_DOCUMENT_UPLOADED = 'document_uploaded';

    public const ACTION_DOCUMENT_DELETED = 'document_deleted';

    /**
     * Record an audit event. Never throws — failures are logged silently.
     *
     * @param  array<string,mixed>|null  $previousState
     * @param  array<string,mixed>|null  $newState
     */
    public static function record(
        string $actionType,
        string $entityType,
        string $entityId,
        string $entityLabel = '',
        ?string $actorId = null,
        ?array $previousState = null,
        ?array $newState = null,
        ?string $reason = null,
        ?string $actorIp = null,
    ): void {
        try {
            $resolvedActorId = $actorId ?? auth()->id();
            if (! $resolvedActorId) {
                return;
            }

            AuditLog::create([
                'actor_id' => $resolvedActorId,
                'action_type' => $actionType,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'entity_label' => $entityLabel,
                'previous_state' => $previousState,
                'new_state' => $newState,
                'reason' => $reason,
                'actor_ip' => $actorIp ?? request()->ip(),
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('AuditService::record failed', ['error' => $e->getMessage(), 'action_type' => $actionType]);
        }
    }
}
