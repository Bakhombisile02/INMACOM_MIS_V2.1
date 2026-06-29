<?php

namespace App\Services;

use App\Models\Station;
use App\Models\StationRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StationRevisionManager
{
    /**
     * Propose a new station creation or update.
     */
    public static function propose(
        User $submitter,
        ?string $stationId,
        string $changeType,
        array $proposedChanges
    ): StationRevision {
        return StationRevision::create([
            'station_id' => $stationId,
            'submitted_by_id' => $submitter->id,
            'status' => StationRevision::STATUS_PENDING,
            'change_type' => $changeType,
            'proposed_changes' => $proposedChanges,
        ]);
    }

    /**
     * Approve a pending station revision.
     *
     * @throws \RuntimeException
     * @throws ValidationException
     */
    public static function approve(
        StationRevision $revision,
        User $reviewer,
        ?string $reviewNotes = null
    ): void {
        DB::transaction(function () use ($revision, $reviewer, $reviewNotes) {
            // Re-fetch with lock to prevent concurrent modifications
            $revision = StationRevision::lockForUpdate()->findOrFail($revision->id);

            if ($revision->status !== StationRevision::STATUS_PENDING) {
                throw new \RuntimeException('Revision has already been reviewed.');
            }

            $isSelfOverride = $reviewer->id === $revision->submitted_by_id;
            $stationName = null;

            if ($revision->change_type === StationRevision::CHANGE_TYPE_CREATE) {
                // Create flow: proposed_changes holds the full station payload.
                if (empty($revision->proposed_changes)) {
                    throw new \RuntimeException('Cannot create station: proposed_changes is empty.');
                }

                $validated = static::validateData($revision->proposed_changes);

                $station = Station::create($validated);
                $revision->station_id = $station->id;
                $stationName = $station->name;
            } elseif ($revision->change_type === StationRevision::CHANGE_TYPE_DELETE) {
                // Delete flow: delete the station
                $station = Station::findOrFail($revision->station_id);
                $stationName = $station->name;
                $station->delete();
            } elseif ($revision->change_type === StationRevision::CHANGE_TYPE_UPDATE) {
                // Update flow: proposed_changes is a diff of from/to per field.
                $station = Station::findOrFail($revision->station_id);
                $stationName = $station->name;

                $updates = [];
                foreach ($revision->proposed_changes as $field => $change) {
                    if (is_array($change) && array_key_exists('to', $change)) {
                        $updates[$field] = $change['to'];
                    }
                }

                if (! empty($updates)) {
                    // Merge existing station data with the updates for validation
                    $mergedData = array_merge($station->toArray(), $updates);
                    static::validateData($mergedData, $station->id);

                    $station->update($updates);
                }
            } else {
                throw new \RuntimeException("Unknown change type: {$revision->change_type}");
            }

            $revision->fill([
                'status' => StationRevision::STATUS_APPROVED,
                'reviewed_by_id' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $reviewNotes,
                'is_self_override' => $isSelfOverride,
            ])->save();

            // Resolve station name for audit log
            $stationLabel = $stationName
                ?? ($revision->proposed_changes['name'] ?? (string) $revision->id);

            AuditService::record(
                actionType: AuditService::ACTION_STATION_REVISION_APPROVED,
                entityType: 'StationRevision',
                entityId: $revision->id,
                entityLabel: $stationLabel.' ('.$revision->change_type.')',
                previousState: ['status' => 'pending'],
                newState: ['status' => 'approved'],
                reason: $reviewNotes,
            );

            if ($isSelfOverride) {
                AuditService::record(
                    actionType: AuditService::ACTION_SELF_APPROVAL,
                    entityType: 'StationRevision',
                    entityId: $revision->id,
                    entityLabel: $stationLabel,
                    reason: 'Self-approved station revision',
                );
            }
        });
    }

    /**
     * Reject a pending station revision.
     *
     * @throws \RuntimeException
     */
    public static function reject(
        StationRevision $revision,
        User $reviewer,
        ?string $reviewNotes = null
    ): void {
        DB::transaction(function () use ($revision, $reviewer, $reviewNotes) {
            // Re-fetch with lock to prevent concurrent modifications
            $revision = StationRevision::lockForUpdate()->findOrFail($revision->id);

            if ($revision->status !== StationRevision::STATUS_PENDING) {
                throw new \RuntimeException('Revision has already been reviewed.');
            }

            $revision->update([
                'status' => StationRevision::STATUS_REJECTED,
                'reviewed_by_id' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $reviewNotes,
            ]);

            $stationLabel = $revision->station?->name
                ?? ($revision->proposed_changes['name'] ?? (string) $revision->id);

            AuditService::record(
                actionType: AuditService::ACTION_STATION_REVISION_REJECTED,
                entityType: 'StationRevision',
                entityId: $revision->id,
                entityLabel: $stationLabel.' ('.$revision->change_type.')',
                previousState: ['status' => 'pending'],
                newState: ['status' => 'rejected'],
                reason: $reviewNotes,
            );
        });
    }

    /**
     * Validate station attributes.
     *
     * @throws ValidationException
     */
    protected static function validateData(array $data, ?string $stationId = null): array
    {
        $rules = [
            'code' => ['required', 'string', 'max:50'],
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

        if ($stationId) {
            $rules['code'][] = Rule::unique('stations', 'code')->ignore($stationId);
        } else {
            $rules['code'][] = Rule::unique('stations', 'code');
        }

        return Validator::make($data, $rules)->validate();
    }
}
