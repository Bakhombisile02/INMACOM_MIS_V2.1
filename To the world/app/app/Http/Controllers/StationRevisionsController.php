<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\StationRevision;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StationRevisionsController extends Controller
{
    public function approve(Request $request, StationRevision $stationRevision): RedirectResponse
    {
        abort_unless($request->user()?->canApprove(), 403);

        if ($stationRevision->status !== StationRevision::STATUS_PENDING) {
            return back()->with('status', 'Revision has already been reviewed.');
        }

        $reviewer = $request->user();
        $isSelfOverride = $reviewer->id === $stationRevision->submitted_by_id;

        DB::transaction(function () use ($stationRevision, $reviewer, $isSelfOverride, $request) {
            if ($stationRevision->change_type === StationRevision::CHANGE_TYPE_CREATE) {
                // Create flow: proposed_changes holds the full station payload.
                $station = Station::create($stationRevision->proposed_changes ?? []);
                $stationRevision->station_id = $station->id;
            } else {
                // Update flow: proposed_changes is a diff of from/to per field.
                $station = Station::findOrFail($stationRevision->station_id);

                $updates = [];
                foreach ($stationRevision->proposed_changes as $field => $change) {
                    if (is_array($change) && array_key_exists('to', $change)) {
                        $updates[$field] = $change['to'];
                    }
                }

                if (! empty($updates)) {
                    $station->update($updates);
                }
            }

            $stationRevision->fill([
                'status' => StationRevision::STATUS_APPROVED,
                'reviewed_by_id' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $request->input('review_notes'),
                'is_self_override' => $isSelfOverride,
            ])->save();
        });

        $stationLabel = $stationRevision->station?->name
            ?? ($stationRevision->proposed_changes['name'] ?? (string) $stationRevision->id);

        AuditService::record(
            actionType: AuditService::ACTION_STATION_REVISION_APPROVED,
            entityType: 'StationRevision',
            entityId: $stationRevision->id,
            entityLabel: $stationLabel.' ('.$stationRevision->change_type.')',
            previousState: ['status' => 'pending'],
            newState: ['status' => 'approved'],
            reason: $request->input('review_notes'),
        );

        if ($isSelfOverride) {
            AuditService::record(
                actionType: AuditService::ACTION_SELF_APPROVAL,
                entityType: 'StationRevision',
                entityId: $stationRevision->id,
                entityLabel: $stationLabel,
                reason: 'Self-approved station revision',
            );
        }

        return back()->with(
            'status',
            $isSelfOverride ? 'Revision self-approved (logged as override).' : 'Revision approved.'
        );
    }

    public function reject(Request $request, StationRevision $stationRevision): RedirectResponse
    {
        abort_unless($request->user()?->canApprove(), 403);

        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($stationRevision->status !== StationRevision::STATUS_PENDING) {
            return back()->with('status', 'Revision has already been reviewed.');
        }

        $stationRevision->update([
            'status' => StationRevision::STATUS_REJECTED,
            'reviewed_by_id' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'] ?? null,
        ]);

        $stationLabel = $stationRevision->station?->name
            ?? ($stationRevision->proposed_changes['name'] ?? (string) $stationRevision->id);

        AuditService::record(
            actionType: AuditService::ACTION_STATION_REVISION_REJECTED,
            entityType: 'StationRevision',
            entityId: $stationRevision->id,
            entityLabel: $stationLabel.' ('.$stationRevision->change_type.')',
            previousState: ['status' => 'pending'],
            newState: ['status' => 'rejected'],
            reason: $validated['review_notes'] ?? null,
        );

        return back()->with('status', 'Revision rejected.');
    }
}
