<?php

namespace App\Http\Controllers;

use App\Queries\AuditLogQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class AuditController extends Controller
{
    public function index(Request $request): mixed
    {
        $user = $request->user();
        abort_unless($user && ($user->isAdmin() || $user->canApprove()), 403);

        try {
            $from = $request->filled('from') ? Carbon::parse($request->string('from')->toString())->startOfDay() : null;
            $to = $request->filled('to') ? Carbon::parse($request->string('to')->toString())->endOfDay() : null;
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'Invalid date format for from/to parameters.']);
        }

        $logs = AuditLogQuery::query()
            ->withActor()
            ->forActionType($request->string('action_type')->toString())
            ->forEntityType($request->string('entity_type')->toString())
            ->forActor($request->string('actor_id')->toString())
            ->forDateRange($from, $to)
            ->paginate(50)
            ->through(fn ($log) => [
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

        return Inertia::render('Audit/Index', [
            'logs' => $logs,
            'filters' => [
                'action_type' => $request->string('action_type')->toString(),
                'entity_type' => $request->string('entity_type')->toString(),
                'actor_id' => $request->string('actor_id')->toString(),
                'from' => $request->string('from')->toString(),
                'to' => $request->string('to')->toString(),
            ],
            'stats' => AuditLogQuery::getSummaryStats(),
            'actors' => AuditLogQuery::getUniqueActors(),
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    public function export(Request $request): mixed
    {
        $user = $request->user();
        abort_unless($user && $user->isAdmin(), 403);

        try {
            $from = $request->filled('from') ? Carbon::parse($request->string('from')->toString())->startOfDay() : null;
            $to = $request->filled('to') ? Carbon::parse($request->string('to')->toString())->endOfDay() : null;
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'Invalid date format for from/to parameters.']);
        }

        $query = AuditLogQuery::query()
            ->withActor()
            ->forActionType($request->string('action_type')->toString())
            ->forEntityType($request->string('entity_type')->toString())
            ->forActor($request->string('actor_id')->toString())
            ->forDateRange($from, $to);

        $filename = 'audit-log-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date/Time', 'Actor', 'Actor Email', 'Action', 'Entity Type', 'Entity Label', 'Entity ID', 'Reason', 'IP']);

            foreach ($query->cursor() as $log) {
                fputcsv($handle, [
                    $log->occurred_at?->toDateTimeString(),
                    AuditLogQuery::sanitizeCsvCell($log->actor?->display_name),
                    AuditLogQuery::sanitizeCsvCell($log->actor?->email),
                    $log->action_type,
                    $log->entity_type,
                    AuditLogQuery::sanitizeCsvCell($log->entity_label),
                    $log->entity_id,
                    AuditLogQuery::sanitizeCsvCell($log->reason),
                    $log->actor_ip,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
