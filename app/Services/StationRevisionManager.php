<?php

namespace App\Services;

use App\Models\Station;
use App\Models\StationRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * @description Applies approved station revisions to the station record, or creates a new station for create-type revisions.
 * @author ITPTC / Datamatics
 * @since 2.1.0
 */
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
        $validChangeTypes = [
            StationRevision::CHANGE_TYPE_CREATE,
            StationRevision::CHANGE_TYPE_UPDATE,
            StationRevision::CHANGE_TYPE_DELETE,
        ];
        if (! in_array($changeType, $validChangeTypes, true)) {
            throw new \InvalidArgumentException("Invalid change type: {$changeType}");
        }

        if ($changeType === StationRevision::CHANGE_TYPE_CREATE && $stationId !== null) {
            throw new \InvalidArgumentException('station_id must be null for CREATE operations');
        }

        if (in_array($changeType, [StationRevision::CHANGE_TYPE_UPDATE, StationRevision::CHANGE_TYPE_DELETE], true) && $stationId === null) {
            throw new \InvalidArgumentException('station_id is required for UPDATE and DELETE operations');
        }

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
        if (! $reviewer->canApprove()) {
            throw new \RuntimeException('User does not have permission to approve revisions.');
        }

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
                // Delete flow: delete the station and its dependencies explicitly
                $station = Station::findOrFail($revision->station_id);
                $stationName = $station->name;

                DB::table('station_capabilities')->where('station_id', $station->id)->delete();
                DB::table('station_operational_statuses')->where('station_id', $station->id)->delete();
                DB::table('management_area_stations')->where('station_id', $station->id)->delete();
                DB::table('compliance_thresholds')->where('station_id', $station->id)->delete();
                DB::table('incident_stations')->where('station_id', $station->id)->delete();
                DB::table('measurements')->where('station_id', $station->id)->delete();

                DB::table('iima_eflow_requirements')->where('station_id', $station->id)->update(['station_id' => null]);
                DB::table('iima_eflow_key_points')->where('station_id', $station->id)->update(['station_id' => null]);
                DB::table('hazard_indicator_readings')->where('station_id', $station->id)->update(['station_id' => null]);

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
                actorId: $reviewer->id,
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
                    actorId: $reviewer->id,
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
        if (! $reviewer->canApprove()) {
            throw new \RuntimeException('User does not have permission to reject revisions.');
        }

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
                actorId: $reviewer->id,
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
