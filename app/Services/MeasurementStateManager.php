<?php

namespace App\Services;

use App\Models\Measurement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @description Manages measurement lifecycle state transitions (pending → approved / rejected) with reviewer tracking.
 * @author ITPTC / Datamatics
 * @since 2.1.0
 */
class MeasurementStateManager
{
    /**
     * Store a new measurement.
     *
     * @throws ValidationException
     */
    public static function store(User $submitter, array $data): Measurement
    {
        $validated = Validator::make($data, [
            'station_id' => ['required', 'string', 'exists:stations,id'],
            'measurement_type' => ['required', 'string', 'in:flow,dam_level,water_quality,rainfall,groundwater_level'],
            'parameter_id' => ['nullable', 'string', 'exists:water_quality_parameters,id'],
            'value' => ['required', 'numeric'],
            'unit' => ['required', 'string'],
            'date' => ['required', 'date'],
            'fsc' => ['nullable', 'numeric'],
        ])->validate();

        $status = 'pending';
        $isSelfOverride = false;

        if ($submitter->canApprove()) {
            $status = 'approved';
            $isSelfOverride = true;
        }

        return DB::transaction(function () use ($validated, $submitter, $status, $isSelfOverride) {
            $id = (string) Str::uuid();

            $measurement = Measurement::create([
                'id' => $id,
                'station_id' => $validated['station_id'],
                'measurement_type' => $validated['measurement_type'],
                'parameter_id' => $validated['parameter_id'] ?? null,
                'value' => $validated['value'],
                'unit' => $validated['unit'],
                'date' => Carbon::parse($validated['date']),
                'fsc' => $validated['fsc'] ?? null,
                'status' => $status,
                'submitted_by_id' => $submitter->id,
                'submitted_at' => now(),
                'reviewed_by_id' => $isSelfOverride ? $submitter->id : null,
                'reviewed_at' => $isSelfOverride ? now() : null,
                'is_self_override' => $isSelfOverride,
            ]);

            if ($isSelfOverride) {
                $stationLabel = $measurement->station?->name
                    ?? $measurement->station?->code
                    ?? $measurement->station_id;
                $label = $stationLabel.' ('.$measurement->measurement_type.')';

                AuditService::record(
                    actionType: AuditService::ACTION_SELF_APPROVAL,
                    entityType: 'Measurement',
                    entityId: $measurement->id,
                    entityLabel: $label,
                    reason: 'Self-approved measurement on submission',
                );
            }

            return $measurement;
        });
    }

    /**
     * Update an existing measurement.
     *
     * @throws \RuntimeException
     * @throws ValidationException
     */
    public static function update(User $editor, string $id, array $data): Measurement
    {
        $validated = Validator::make($data, [
            'value' => ['required', 'numeric'],
            'unit' => ['required', 'string'],
            'date' => ['required', 'date'],
            'fsc' => ['nullable', 'numeric'],
        ])->validate();

        return DB::transaction(function () use ($editor, $id, $validated) {
            $measurement = Measurement::lockForUpdate()->findOrFail($id);

            // Access check: only owner or manager/admin can update
            $canEdit = ($measurement->submitted_by_id === $editor->id) || $editor->canApprove();
            if (! $canEdit) {
                throw new \RuntimeException('Unauthorized to edit this measurement.');
            }

            $updateData = [
                'value' => $validated['value'],
                'unit' => $validated['unit'],
                'date' => Carbon::parse($validated['date']),
                'fsc' => $validated['fsc'] ?? null,
            ];

            // If a data clerk edits a rejected/approved record, revert status to pending
            if ($editor->role === User::ROLE_CLERK) {
                $updateData['status'] = 'pending';
                $updateData['reviewed_by_id'] = null;
                $updateData['reviewed_at'] = null;
                $updateData['review_notes'] = null;
                $updateData['is_self_override'] = false;
            }

            $measurement->update($updateData);

            return $measurement;
        });
    }

    /**
     * Delete a measurement.
     *
     * @throws \RuntimeException
     */
    public static function delete(User $deleter, string $id): void
    {
        if (! $deleter->canApprove()) {
            throw new \RuntimeException('Unauthorized to delete measurements.');
        }

        DB::transaction(function () use ($id) {
            $measurement = Measurement::lockForUpdate()->findOrFail($id);
            $measurement->delete();
        });
    }

    /**
     * Approve a pending measurement.
     *
     * @throws \RuntimeException
     */
    public static function approve(User $reviewer, string $id, ?string $reviewNotes = null): void
    {
        if (! $reviewer->canApprove()) {
            throw new \RuntimeException('Unauthorized to approve measurements.');
        }

        DB::transaction(function () use ($reviewer, $id, $reviewNotes) {
            $measurement = Measurement::lockForUpdate()->findOrFail($id);

            if ($measurement->status !== 'pending') {
                throw new \RuntimeException('Measurement is not pending approval.');
            }

            $isSelf = $measurement->submitted_by_id === $reviewer->id;
            $notes = $reviewNotes ?? 'Approved by Data Manager';

            $measurement->update([
                'status' => 'approved',
                'reviewed_by_id' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $notes,
                'is_self_override' => $isSelf,
            ]);

            $stationLabel = $measurement->station?->name
                ?? $measurement->station?->code
                ?? $measurement->station_id;
            $label = $stationLabel.' ('.$measurement->measurement_type.')';

            AuditService::record(
                actionType: AuditService::ACTION_MEASUREMENT_APPROVED,
                entityType: 'Measurement',
                entityId: $id,
                entityLabel: $label,
                previousState: ['status' => 'pending'],
                newState: ['status' => 'approved'],
                reason: $reviewNotes,
            );

            if ($isSelf) {
                AuditService::record(
                    actionType: AuditService::ACTION_SELF_APPROVAL,
                    entityType: 'Measurement',
                    entityId: $id,
                    entityLabel: $label,
                    reason: 'Self-approved measurement',
                );
            }
        });
    }

    /**
     * Reject a pending measurement.
     *
     * @throws \RuntimeException
     */
    public static function reject(User $reviewer, string $id, string $reviewNotes): void
    {
        if (! $reviewer->canApprove()) {
            throw new \RuntimeException('Unauthorized to reject measurements.');
        }

        DB::transaction(function () use ($reviewer, $id, $reviewNotes) {
            $measurement = Measurement::lockForUpdate()->findOrFail($id);

            if ($measurement->status !== 'pending') {
                throw new \RuntimeException('Measurement is not pending rejection.');
            }

            $measurement->update([
                'status' => 'rejected',
                'reviewed_by_id' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $reviewNotes,
            ]);

            $stationLabel = $measurement->station?->name
                ?? $measurement->station?->code
                ?? $measurement->station_id;
            $label = $stationLabel.' ('.$measurement->measurement_type.')';

            AuditService::record(
                actionType: AuditService::ACTION_MEASUREMENT_REJECTED,
                entityType: 'Measurement',
                entityId: $id,
                entityLabel: $label,
                previousState: ['status' => 'pending'],
                newState: ['status' => 'rejected'],
                reason: $reviewNotes,
            );
        });
    }

    /**
     * Bulk import measurements.
     */
    public static function import(User $user, string $measurementType, array $inserts): int
    {
        if (empty($inserts)) {
            return 0;
        }

        DB::transaction(function () use ($inserts, $user, $measurementType) {
            foreach (array_chunk($inserts, 500) as $chunk) {
                // Ensure every row has a UUID
                $prepared = array_map(function ($row) {
                    if (! isset($row['id'])) {
                        $row['id'] = (string) Str::uuid();
                    }

                    return $row;
                }, $chunk);

                DB::table('measurements')->insert($prepared);
            }

            $count = count($inserts);
            $firstId = $inserts[0]['id'] ?? (string) Str::uuid();

            AuditService::record(
                actionType: AuditService::ACTION_MEASUREMENT_IMPORTED,
                entityType: 'Measurement',
                entityId: $firstId,
                entityLabel: "Bulk import of {$count} {$measurementType} measurement(s)",
                actorId: $user->id,
                reason: "Bulk imported {$count} rows from CSV"
            );
        });

        return count($inserts);
    }
}
