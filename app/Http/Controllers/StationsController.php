<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStationRequest;
use App\Http\Requests\UpdateStationRequest;
use App\Models\Station;
use App\Models\StationRevision;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class StationsController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $canManage = ($user?->canApprove() ?? false);
        $isAdmin = ($user?->isAdmin() ?? false);

        $stations = Station::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Station $station) => [
                'id' => $station->id,
                'code' => $station->code,
                'name' => $station->name,
                'country' => $station->country,
                'category' => $station->category,
                'water_source' => $station->water_source,
                'water_body_type' => $station->water_body_type,
                'status' => $station->is_active ? 'active' : 'inactive',
                'is_active' => $station->is_active,
                'is_real_time' => $station->is_real_time,
                'latitude' => $station->latitude,
                'longitude' => $station->longitude,
                'show_url' => route('stations.show', $station),
                'summary' => $station->summary,
                'river_basin' => $station->river_basin,
                'telemetry_system' => $station->telemetry_system,
                'gauge_code' => $station->gauge_code,
                'owner_org' => $station->owner_org,
            ]);

        $pendingRevisions = collect();
        if ($canManage) {
            $pendingRevisions = StationRevision::query()
                ->with(['station:id,name,code', 'submittedBy:id,display_name'])
                ->where('status', StationRevision::STATUS_PENDING)
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (StationRevision $r) => [
                    'id' => $r->id,
                    'station_id' => $r->station_id,
                    'station_name' => $r->station?->name ?? ($r->proposed_changes['name'] ?? null),
                    'station_code' => $r->station?->code ?? ($r->proposed_changes['code'] ?? null),
                    'submitted_by_id' => $r->submitted_by_id,
                    'submitted_by_name' => $r->submittedBy?->display_name,
                    'change_type' => $r->change_type,
                    'proposed_changes' => $r->proposed_changes,
                    'status' => $r->status,
                    'review_notes' => $r->review_notes,
                    'reviewed_by_id' => $r->reviewed_by_id,
                    'reviewed_at' => $r->reviewed_at,
                    'created_at' => $r->created_at,
                    'updated_at' => $r->updated_at,
                ]);
        }

        return Inertia::render('Stations/Index', [
            'stations' => $stations,
            'canManage' => $canManage,
            'isAdmin' => $isAdmin,
            'pendingRevisions' => $pendingRevisions,
        ]);
    }

    public function show(Station $station): Response
    {
        $user = auth()->user();
        $canManage = ($user?->canApprove() ?? false);
        $isAdmin = ($user?->isAdmin() ?? false);

        $recentMeasurements = collect();
        if (Schema::hasTable('measurements')) {
            $recentMeasurements = DB::table('measurements')
                ->where('station_id', $station->id)
                ->orderByDesc('date')
                ->limit(12)
                ->get(['measurement_type', 'value', 'unit', 'status', 'date'])
                ->map(fn ($row) => [
                    'measurement_type' => $row->measurement_type,
                    'value' => (float) $row->value,
                    'unit' => $row->unit,
                    'status' => $row->status,
                    'date' => $row->date,
                ]);
        }

        $capabilities = collect();
        if (Schema::hasTable('station_capabilities')) {
            $capabilities = DB::table('station_capabilities')
                ->where('station_id', $station->id)
                ->get(['measurement_type', 'is_primary', 'installed_at', 'notes'])
                ->map(fn ($row) => [
                    'measurement_type' => $row->measurement_type,
                    'is_primary' => (bool) $row->is_primary,
                    'installed_at' => $row->installed_at,
                    'notes' => $row->notes,
                ]);
        }

        $operationalStatuses = collect();
        if (Schema::hasTable('station_operational_statuses')) {
            $operationalStatuses = DB::table('station_operational_statuses as sos')
                ->leftJoin('users', 'users.id', '=', 'sos.reported_by_id')
                ->where('sos.station_id', $station->id)
                ->orderByDesc('sos.started_at')
                ->limit(10)
                ->get([
                    'sos.status',
                    'sos.reason',
                    'sos.started_at',
                    'sos.expected_resolution_at',
                    'sos.resolved_at',
                    'users.display_name as reported_by',
                ])
                ->map(fn ($row) => [
                    'status' => $row->status,
                    'reason' => $row->reason,
                    'started_at' => $row->started_at,
                    'expected_resolution_at' => $row->expected_resolution_at,
                    'resolved_at' => $row->resolved_at,
                    'reported_by' => $row->reported_by,
                ]);
        }

        $managementAreas = collect();
        if (Schema::hasTable('management_area_stations') && Schema::hasTable('management_areas')) {
            $managementAreas = DB::table('management_area_stations as mas')
                ->join('management_areas as ma', 'ma.id', '=', 'mas.management_area_id')
                ->where('mas.station_id', $station->id)
                ->get(['ma.code', 'ma.name', 'ma.basin', 'ma.country'])
                ->map(fn ($row) => [
                    'code' => $row->code,
                    'name' => $row->name,
                    'basin' => $row->basin,
                    'country' => $row->country,
                ]);
        }

        $eflowKeyPoint = null;
        if (Schema::hasTable('iima_eflow_key_points')) {
            $row = DB::table('iima_eflow_key_points')
                ->where('station_id', $station->id)
                ->first(['code', 'name', 'river', 'country', 'note']);
            if ($row) {
                $eflowKeyPoint = [
                    'code' => $row->code,
                    'name' => $row->name,
                    'river' => $row->river,
                    'country' => $row->country,
                    'note' => $row->note,
                ];
            }
        }

        return Inertia::render('Stations/Show', [
            'station' => [
                'id' => $station->id,
                'code' => $station->code,
                'name' => $station->name,
                'country' => $station->country,
                'category' => $station->category,
                'water_source' => $station->water_source,
                'water_body_type' => $station->water_body_type,
                'summary' => $station->summary,
                'owner_org' => $station->owner_org,
                'river_basin' => $station->river_basin,
                'telemetry_system' => $station->telemetry_system,
                'gauge_code' => $station->gauge_code,
                'is_active' => $station->is_active,
                'is_real_time' => $station->is_real_time,
                'latitude' => $station->latitude,
                'longitude' => $station->longitude,
            ],
            'recentMeasurements' => $recentMeasurements,
            'capabilities' => $capabilities,
            'operationalStatuses' => $operationalStatuses,
            'managementAreas' => $managementAreas,
            'eflowKeyPoint' => $eflowKeyPoint,
            'canManage' => $canManage,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function store(StoreStationRequest $request): RedirectResponse
    {
        // Only data managers and admins may *propose* a new station.
        // Clerks must request creation via another channel (or be promoted).
        abort_unless(
            $request->user()->canApprove(),
            403,
        );

        // ALWAYS route through the approvals queue for an auditable trail.
        // Admin/manager submitters may self-approve later; that is logged via
        // `is_self_override = true` on approval.
        StationRevision::create([
            'station_id' => null,
            'submitted_by_id' => $request->user()->id,
            'status' => StationRevision::STATUS_PENDING,
            'change_type' => StationRevision::CHANGE_TYPE_CREATE,
            'proposed_changes' => $request->validated(),
        ]);

        return redirect()
            ->route('stations.index')
            ->with('status', 'Station submitted for review.');
    }

    public function import(Request $request)
    {
        abort_unless(
            $request->user()->canApprove(),
            403,
        );

        $request->validate([
            'rows' => ['required', 'array', 'min:1', 'max:500'],
            'rows.*.code' => ['required', 'string', 'max:50'],
            'rows.*.name' => ['required', 'string', 'max:255'],
            'rows.*.latitude' => ['required', 'numeric', 'between:-90,90'],
            'rows.*.longitude' => ['required', 'numeric', 'between:-180,180'],
            'rows.*.category' => ['required', 'string', 'max:100'],
            'rows.*.water_source' => ['required', 'string', 'max:100'],
            'rows.*.water_body_type' => ['required', 'string', 'max:100'],
            'rows.*.is_active' => ['required', 'boolean'],
            'rows.*.is_real_time' => ['required', 'boolean'],
            'rows.*.summary' => ['nullable', 'string', 'max:2000'],
            'rows.*.telemetry_system' => ['nullable', 'string', 'max:255'],
            'rows.*.gauge_code' => ['nullable', 'string', 'max:100'],
            'rows.*.owner_org' => ['nullable', 'string', 'max:255'],
            'rows.*.country' => ['nullable', 'string', 'max:100'],
            'rows.*.river_basin' => ['nullable', 'string', 'max:255'],
        ]);

        $rows = $request->input('rows');

        // Check for duplicate codes within the batch
        $codes = array_column($rows, 'code');
        if (count($codes) !== count(array_unique($codes))) {
            return back()->withErrors(['message' => 'Duplicate station codes found in the batch.']);
        }

        // Check each code is unique in the DB
        $existingCodes = Station::whereIn('code', $codes)->pluck('code')->all();
        if (!empty($existingCodes)) {
            return back()->withErrors([
                'message' => 'Some codes already exist in the database: ' . implode(', ', $existingCodes),
            ]);
        }

        // Insert all rows in one transaction
        $inserted = 0;
        DB::transaction(function () use ($rows, &$inserted) {
            foreach ($rows as $row) {
                Station::create([
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'country' => $row['country'] ?? null,
                    'category' => $row['category'],
                    'water_source' => $row['water_source'],
                    'water_body_type' => $row['water_body_type'],
                    'latitude' => (float) $row['latitude'],
                    'longitude' => (float) $row['longitude'],
                    'river_basin' => $row['river_basin'] ?? null,
                    'owner_org' => $row['owner_org'] ?? null,
                    'telemetry_system' => $row['telemetry_system'] ?? null,
                    'gauge_code' => $row['gauge_code'] ?? null,
                    'summary' => $row['summary'] ?? null,
                    'is_active' => (bool) $row['is_active'],
                    'is_real_time' => (bool) $row['is_real_time'],
                ]);
                $inserted++;
            }
        });

        return redirect()->back();
    }

    public function update(UpdateStationRequest $request, Station $station): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // ALL roles route through the approvals queue for an auditable trail.
        // Admin/manager submitters may self-approve later; this is logged via
        // `is_self_override = true` on approval.
        $diff = [];
        foreach ($validated as $key => $value) {
            $current = $station->getAttribute($key);
            if ($current != $value) {
                $diff[$key] = ['from' => $current, 'to' => $value];
            }
        }

        if (empty($diff)) {
            return back()->with('status', 'No changes to submit.');
        }

        StationRevision::create([
            'station_id' => $station->id,
            'submitted_by_id' => $user->id,
            'status' => StationRevision::STATUS_PENDING,
            'change_type' => StationRevision::CHANGE_TYPE_UPDATE,
            'proposed_changes' => $diff,
        ]);

        return back()->with('status', 'Station change submitted for review.');
    }

    public function destroy(Request $request, Station $station): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $station->delete();

        return redirect()->route('stations.index');
    }

    public function exportCsv(Station $station): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = 'station-' . $station->code . '-' . now()->format('Y-m-d') . '.csv';

        return response()->stream(function () use ($station) {
            $handle = fopen('php://output', 'w');

            // BOM for UTF-8 Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'station_code', 'station_name',
                'date', 'measurement_type', 'parameter_code', 'value', 'unit', 'status',
            ]);

            DB::table('measurements as m')
                ->leftJoin('water_quality_parameters as wqp', 'wqp.id', '=', 'm.parameter_id')
                ->where('m.station_id', $station->id)
                ->orderBy('m.date')
                ->orderBy('m.measurement_type')
                ->select([
                    'm.date',
                    'm.measurement_type',
                    'wqp.code as parameter_code',
                    'm.value',
                    'm.unit',
                    'm.status',
                ])
                ->chunk(500, function ($rows) use ($handle, $station) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $station->code,
                            $station->name,
                            $row->date,
                            $row->measurement_type,
                            $row->parameter_code ?? '',
                            $row->value,
                            $row->unit,
                            $row->status,
                        ]);
                    }
                });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
