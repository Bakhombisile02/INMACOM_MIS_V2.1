<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user && ($user->isAdmin() || $user->canApprove()), 403);

        $query = AuditLog::query()
            ->with('actor:id,display_name,email,photo_url,role')
            ->orderByDesc('occurred_at');

        if ($actionType = $request->string('action_type')->toString()) {
            $query->where('action_type', $actionType);
        }

        if ($entityType = $request->string('entity_type')->toString()) {
            $query->where('entity_type', $entityType);
        }

        if ($actorId = $request->string('actor_id')->toString()) {
            $query->where('actor_id', $actorId);
        }

        if ($from = $request->string('from')->toString()) {
            $query->where('occurred_at', '>=', $from);
        }

        if ($to = $request->string('to')->toString()) {
            $query->where('occurred_at', '<=', $to.' 23:59:59');
        }

        $logs = $query->paginate(50)->through(fn (AuditLog $log) => [
            'id' => $log->id,
            'action_type' => $log->action_type,
            'entity_type' => $log->entity_type,
            'entity_id' => $log->entity_id,
            'entity_label' => $log->entity_label,
            'previous_state' => $log->previous_state,
            'new_state' => $log->new_state,
            'reason' => $log->reason,
            'actor_ip' => $log->actor_ip,
            'occurred_at' => $log->occurred_at?->toISOString(),
            'actor' => $log->actor ? [
                'id' => $log->actor->id,
                'display_name' => $log->actor->display_name,
                'email' => $log->actor->email,
                'photo_url' => $log->actor->photo_url,
                'role' => $log->actor->role,
            ] : null,
        ]);

        // Stats
        $todayCount = AuditLog::whereDate('occurred_at', today())->count();
        $selfApprovalCount = AuditLog::where('action_type', 'self_approval')
            ->whereMonth('occurred_at', now()->month)
            ->count();
        $roleChangeCount = AuditLog::where('action_type', 'role_change')->count();

        $mostActive = AuditLog::selectRaw('actor_id, count(*) as total')
            ->whereMonth('occurred_at', now()->month)
            ->groupBy('actor_id')
            ->orderByDesc('total')
            ->first();
        $mostActiveUser = $mostActive ? User::find($mostActive->actor_id) : null;

        // Actor list for filter dropdown (distinct actors who have logged events)
        $actors = AuditLog::selectRaw('actor_id')
            ->distinct()
            ->with('actor:id,display_name,email')
            ->get()
            ->map(fn ($row) => $row->actor)
            ->filter()
            ->values()
            ->map(fn (User $u) => ['id' => $u->id, 'display_name' => $u->display_name, 'email' => $u->email]);

        return Inertia::render('Audit/Index', [
            'logs' => $logs,
            'filters' => [
                'action_type' => $request->string('action_type')->toString(),
                'entity_type' => $request->string('entity_type')->toString(),
                'actor_id' => $request->string('actor_id')->toString(),
                'from' => $request->string('from')->toString(),
                'to' => $request->string('to')->toString(),
            ],
            'stats' => [
                'today_count' => $todayCount,
                'self_approval_count' => $selfApprovalCount,
                'role_change_count' => $roleChangeCount,
                'most_active_name' => $mostActiveUser?->display_name,
            ],
            'actors' => $actors,
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user && $user->isAdmin(), 403);

        $query = AuditLog::query()
            ->with('actor:id,display_name,email')
            ->orderByDesc('occurred_at');

        if ($actionType = $request->string('action_type')->toString()) {
            $query->where('action_type', $actionType);
        }
        if ($entityType = $request->string('entity_type')->toString()) {
            $query->where('entity_type', $entityType);
        }
        if ($actorId = $request->string('actor_id')->toString()) {
            $query->where('actor_id', $actorId);
        }
        if ($from = $request->string('from')->toString()) {
            $query->where('occurred_at', '>=', $from);
        }
        if ($to = $request->string('to')->toString()) {
            $query->where('occurred_at', '<=', $to.' 23:59:59');
        }

        $filename = 'audit-log-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date/Time', 'Actor', 'Actor Email', 'Action', 'Entity Type', 'Entity Label', 'Entity ID', 'Reason', 'IP']);

            $query->chunk(500, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->occurred_at?->toDateTimeString(),
                        $log->actor?->display_name,
                        $log->actor?->email,
                        $log->action_type,
                        $log->entity_type,
                        $log->entity_label,
                        $log->entity_id,
                        $log->reason,
                        $log->actor_ip,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
