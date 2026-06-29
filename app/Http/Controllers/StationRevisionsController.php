<?php

namespace App\Http\Controllers;

use App\Models\StationRevision;
use App\Services\StationRevisionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StationRevisionsController extends Controller
{
    public function approve(Request $request, StationRevision $stationRevision): RedirectResponse
    {
        abort_unless($request->user()?->canApprove(), 403);

        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            StationRevisionManager::approve(
                $stationRevision,
                $request->user(),
                $validated['review_notes'] ?? null
            );

            $stationRevision->refresh();

            return back()->with(
                'status',
                $stationRevision->is_self_override ? 'Revision self-approved (logged as override).' : 'Revision approved.'
            );
        } catch (\RuntimeException $e) {
            return back()->with('status', $e->getMessage());
        }
    }

    public function reject(Request $request, StationRevision $stationRevision): RedirectResponse
    {
        abort_unless($request->user()?->canApprove(), 403);

        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            StationRevisionManager::reject(
                $stationRevision,
                $request->user(),
                $validated['review_notes'] ?? null
            );

            return back()->with('status', 'Revision rejected.');
        } catch (\RuntimeException $e) {
            return back()->with('status', $e->getMessage());
        }
    }
}
