<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GisController extends Controller
{
    public function flowLevels(Request $request): Response
    {
        $user = auth()->user();
        $canManage = ($user?->canApprove() ?? false);

        $stations = Station::query()
            ->whereHas('capabilities', function ($query) {
                $query->where('measurement_type', 'flow');
            })
            ->where('is_active', true)
            ->get();

        // Get latest approved flow measurement per station
        $latest = DB::table('measurements as m1')
            ->select('m1.station_id', 'm1.value', 'm1.unit', 'm1.date', 'ct.min_value as limit_value')
            ->leftJoin('compliance_thresholds as ct', function ($join) {
                $join->on('ct.station_id', '=', 'm1.station_id')
                    ->where('ct.data_type', '=', 'flow');
            })
            ->where('m1.status', 'approved')
            ->where('m1.measurement_type', 'flow')
            ->whereRaw("m1.date = (SELECT MAX(m2.date) FROM measurements as m2 WHERE m2.station_id = m1.station_id AND m2.measurement_type = m1.measurement_type AND m2.status = 'approved')")
            ->get()
            ->keyBy('station_id');

        $mappedStations = $stations->map(function (Station $station) use ($latest) {
            $reading = $latest->get($station->id);

            return [
                'id' => $station->id,
                'code' => $station->code,
                'name' => $station->name,
                'latitude' => $station->latitude,
                'longitude' => $station->longitude,
                'country' => $station->country,
                'river_basin' => $station->river_basin,
                'is_real_time' => $station->is_real_time,
                'owner_org' => $station->owner_org,
                'show_url' => route('stations.show', $station),
                'status' => $station->is_active ? 'active' : 'inactive',
                'is_active' => $station->is_active,
                'category' => $station->category,
                'water_source' => $station->water_source,
                'water_body_type' => $station->water_body_type,
                'summary' => $station->summary,
                'telemetry_system' => $station->telemetry_system,
                'gauge_code' => $station->gauge_code,
                'value' => $reading ? (float) $reading->value : null,
                'unit' => $reading ? $reading->unit : 'm³/s',
                'date' => $reading ? $reading->date : null,
                'limit' => $reading ? ($reading->limit_value ? (float) $reading->limit_value : null) : null,
            ];
        });

        // Verification Queue of pending flow measurements
        $pendingQueue = DB::table('measurements as m')
            ->join('stations as s', 's.id', '=', 'm.station_id')
            ->join('users as u', 'u.id', '=', 'm.submitted_by_id')
            ->where('m.measurement_type', 'flow')
            ->where('m.status', 'pending')
            ->select([
                'm.id', 'm.station_id', 's.code as station_code', 's.name as station_name',
                'm.value', 'm.unit', 'm.date', 'm.status', 'u.display_name as submitted_by', 'm.submitted_at',
            ])
            ->orderBy('m.date', 'desc')
            ->get();

        // Recent historical logs (both approved and rejected) for CRUD management
        $historicalLogs = DB::table('measurements as m')
            ->join('stations as s', 's.id', '=', 'm.station_id')
            ->join('users as u', 'u.id', '=', 'm.submitted_by_id')
            ->leftJoin('users as r', 'r.id', '=', 'm.reviewed_by_id')
            ->where('m.measurement_type', 'flow')
            ->select([
                'm.id', 'm.station_id', 's.code as station_code', 's.name as station_name',
                'm.value', 'm.unit', 'm.date', 'm.status', 'm.review_notes',
                'u.display_name as submitted_by', 'm.submitted_at',
                'r.display_name as reviewed_by', 'm.reviewed_at',
            ])
            ->orderBy('m.date', 'desc')
            ->limit(100)
            ->get();

        return Inertia::render('Gis/FlowLevels', [
            'stations' => $mappedStations,
            'pendingQueue' => $pendingQueue,
            'historicalLogs' => $historicalLogs,
            'canManage' => $canManage,
            'userRole' => $user?->role,
        ]);
    }

    public function damLevels(Request $request): Response
    {
        $user = auth()->user();
        $canManage = ($user?->canApprove() ?? false);

        $stations = Station::query()
            ->whereHas('capabilities', function ($query) {
                $query->where('measurement_type', 'dam_level');
            })
            ->where('is_active', true)
            ->get();

        $latest = DB::table('measurements as m1')
            ->select('m1.station_id', 'm1.value', 'm1.unit', 'm1.date')
            ->where('m1.status', 'approved')
            ->where('m1.measurement_type', 'dam_level')
            ->whereRaw("m1.date = (SELECT MAX(m2.date) FROM measurements as m2 WHERE m2.station_id = m1.station_id AND m2.measurement_type = m1.measurement_type AND m2.status = 'approved')")
            ->get()
            ->keyBy('station_id');

        $mappedStations = $stations->map(function (Station $station) use ($latest) {
            $reading = $latest->get($station->id);

            return [
                'id' => $station->id,
                'code' => $station->code,
                'name' => $station->name,
                'latitude' => $station->latitude,
                'longitude' => $station->longitude,
                'country' => $station->country,
                'river_basin' => $station->river_basin,
                'is_real_time' => $station->is_real_time,
                'owner_org' => $station->owner_org,
                'show_url' => route('stations.show', $station),
                'status' => $station->is_active ? 'active' : 'inactive',
                'is_active' => $station->is_active,
                'category' => $station->category,
                'water_source' => $station->water_source,
                'water_body_type' => $station->water_body_type,
                'summary' => $station->summary,
                'telemetry_system' => $station->telemetry_system,
                'gauge_code' => $station->gauge_code,
                'value' => $reading ? (float) $reading->value : null,
                'unit' => $reading ? $reading->unit : '%',
                'date' => $reading ? $reading->date : null,
            ];
        });

        // Verification Queue of pending dam measurements
        $pendingQueue = DB::table('measurements as m')
            ->join('stations as s', 's.id', '=', 'm.station_id')
            ->join('users as u', 'u.id', '=', 'm.submitted_by_id')
            ->where('m.measurement_type', 'dam_level')
            ->where('m.status', 'pending')
            ->select([
                'm.id', 'm.station_id', 's.code as station_code', 's.name as station_name',
                'm.value', 'm.unit', 'm.date', 'm.status', 'u.display_name as submitted_by', 'm.submitted_at',
            ])
            ->orderBy('m.date', 'desc')
            ->get();

        // Recent historical logs (both approved and rejected) for CRUD management
        $historicalLogs = DB::table('measurements as m')
            ->join('stations as s', 's.id', '=', 'm.station_id')
            ->join('users as u', 'u.id', '=', 'm.submitted_by_id')
            ->leftJoin('users as r', 'r.id', '=', 'm.reviewed_by_id')
            ->where('m.measurement_type', 'dam_level')
            ->select([
                'm.id', 'm.station_id', 's.code as station_code', 's.name as station_name',
                'm.value', 'm.unit', 'm.date', 'm.status', 'm.review_notes',
                'u.display_name as submitted_by', 'm.submitted_at',
                'r.display_name as reviewed_by', 'm.reviewed_at',
            ])
            ->orderBy('m.date', 'desc')
            ->limit(100)
            ->get();

        return Inertia::render('Gis/DamLevels', [
            'stations' => $mappedStations,
            'pendingQueue' => $pendingQueue,
            'historicalLogs' => $historicalLogs,
            'canManage' => $canManage,
            'userRole' => $user?->role,
        ]);
    }

    public function waterQuality(Request $request): Response
    {
        $user = auth()->user();
        $canManage = ($user?->canApprove() ?? false);

        $stations = Station::query()
            ->whereHas('capabilities', function ($query) {
                $query->where('measurement_type', 'water_quality');
            })
            ->where('is_active', true)
            ->get();

        // Get all latest approved water quality readings per station & parameter
        $latest = DB::table('measurements as m1')
            ->select('m1.station_id', 'm1.value', 'm1.unit', 'm1.date', 'wq.code as parameter_code', 'ct.min_value', 'ct.max_value')
            ->join('water_quality_parameters as wq', 'wq.id', '=', 'm1.parameter_id')
            ->leftJoin('compliance_thresholds as ct', function ($join) {
                $join->on('ct.station_id', '=', 'm1.station_id')
                    ->on('ct.parameter_id', '=', 'm1.parameter_id')
                    ->where('ct.data_type', '=', 'water_quality');
            })
            ->where('m1.status', 'approved')
            ->where('m1.measurement_type', 'water_quality')
            ->whereRaw("m1.date = (SELECT MAX(m2.date) FROM measurements as m2 WHERE m2.station_id = m1.station_id AND m2.measurement_type = m1.measurement_type AND m2.parameter_id = m1.parameter_id AND m2.status = 'approved')")
            ->get();

        // Group readings by station ID
        $readingsGrouped = [];
        foreach ($latest as $row) {
            $readingsGrouped[$row->station_id][$row->parameter_code] = [
                'value' => (float) $row->value,
                'unit' => $row->unit,
                'date' => $row->date,
                'min' => $row->min_value !== null ? (float) $row->min_value : null,
                'max' => $row->max_value !== null ? (float) $row->max_value : null,
            ];
        }

        $mappedStations = $stations->map(function (Station $station) use ($readingsGrouped) {
            return [
                'id' => $station->id,
                'code' => $station->code,
                'name' => $station->name,
                'latitude' => $station->latitude,
                'longitude' => $station->longitude,
                'country' => $station->country,
                'river_basin' => $station->river_basin,
                'is_real_time' => $station->is_real_time,
                'owner_org' => $station->owner_org,
                'show_url' => route('stations.show', $station),
                'status' => $station->is_active ? 'active' : 'inactive',
                'is_active' => $station->is_active,
                'category' => $station->category,
                'water_source' => $station->water_source,
                'water_body_type' => $station->water_body_type,
                'summary' => $station->summary,
                'telemetry_system' => $station->telemetry_system,
                'gauge_code' => $station->gauge_code,
                'readings' => $readingsGrouped[$station->id] ?? [],
            ];
        });

        // Get active WQ parameters list
        $parameters = DB::table('water_quality_parameters')
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get(['id', 'code', 'name', 'default_unit']);

        // Verification Queue of pending water quality measurements
        $pendingQueue = DB::table('measurements as m')
            ->join('stations as s', 's.id', '=', 'm.station_id')
            ->join('water_quality_parameters as wqp', 'wqp.id', '=', 'm.parameter_id')
            ->join('users as u', 'u.id', '=', 'm.submitted_by_id')
            ->where('m.measurement_type', 'water_quality')
            ->where('m.status', 'pending')
            ->select([
                'm.id', 'm.station_id', 's.code as station_code', 's.name as station_name',
                'wqp.code as parameter_code', 'wqp.name as parameter_name',
                'm.value', 'm.unit', 'm.date', 'm.status', 'u.display_name as submitted_by', 'm.submitted_at',
            ])
            ->orderBy('m.date', 'desc')
            ->get();

        // Recent historical logs (both approved and rejected) for CRUD management
        $historicalLogs = DB::table('measurements as m')
            ->join('stations as s', 's.id', '=', 'm.station_id')
            ->join('water_quality_parameters as wqp', 'wqp.id', '=', 'm.parameter_id')
            ->join('users as u', 'u.id', '=', 'm.submitted_by_id')
            ->leftJoin('users as r', 'r.id', '=', 'm.reviewed_by_id')
            ->where('m.measurement_type', 'water_quality')
            ->select([
                'm.id', 'm.station_id', 's.code as station_code', 's.name as station_name',
                'wqp.code as parameter_code', 'wqp.name as parameter_name',
                'm.value', 'm.unit', 'm.date', 'm.status', 'm.review_notes',
                'u.display_name as submitted_by', 'm.submitted_at',
                'r.display_name as reviewed_by', 'm.reviewed_at',
            ])
            ->orderBy('m.date', 'desc')
            ->limit(100)
            ->get();

        return Inertia::render('Gis/WaterQuality', [
            'stations' => $mappedStations,
            'parameters' => $parameters,
            'pendingQueue' => $pendingQueue,
            'historicalLogs' => $historicalLogs,
            'canManage' => $canManage,
            'userRole' => $user?->role,
        ]);
    }

    public function rainfall(Request $request): Response
    {
        $user = auth()->user();
        $canManage = ($user?->canApprove() ?? false);

        $stations = Station::query()
            ->whereHas('capabilities', function ($query) {
                $query->where('measurement_type', 'rainfall');
            })
            ->where('is_active', true)
            ->get();

        $latest = DB::table('measurements as m1')
            ->select('m1.station_id', 'm1.value', 'm1.unit', 'm1.date')
            ->where('m1.status', 'approved')
            ->where('m1.measurement_type', 'rainfall')
            ->whereRaw("m1.date = (SELECT MAX(m2.date) FROM measurements as m2 WHERE m2.station_id = m1.station_id AND m2.measurement_type = m1.measurement_type AND m2.status = 'approved')")
            ->get()
            ->keyBy('station_id');

        $mappedStations = $stations->map(function (Station $station) use ($latest) {
            $reading = $latest->get($station->id);

            return [
                'id' => $station->id,
                'code' => $station->code,
                'name' => $station->name,
                'latitude' => $station->latitude,
                'longitude' => $station->longitude,
                'country' => $station->country,
                'river_basin' => $station->river_basin,
                'is_real_time' => $station->is_real_time,
                'owner_org' => $station->owner_org,
                'show_url' => route('stations.show', $station),
                'status' => $station->is_active ? 'active' : 'inactive',
                'is_active' => $station->is_active,
                'category' => $station->category,
                'water_source' => $station->water_source,
                'water_body_type' => $station->water_body_type,
                'summary' => $station->summary,
                'telemetry_system' => $station->telemetry_system,
                'gauge_code' => $station->gauge_code,
                'value' => $reading ? (float) $reading->value : null,
                'unit' => $reading ? $reading->unit : 'mm',
                'date' => $reading ? $reading->date : null,
            ];
        });

        // Verification Queue of pending rainfall measurements
        $pendingQueue = DB::table('measurements as m')
            ->join('stations as s', 's.id', '=', 'm.station_id')
            ->join('users as u', 'u.id', '=', 'm.submitted_by_id')
            ->where('m.measurement_type', 'rainfall')
            ->where('m.status', 'pending')
            ->select([
                'm.id', 'm.station_id', 's.code as station_code', 's.name as station_name',
                'm.value', 'm.unit', 'm.date', 'm.status', 'u.display_name as submitted_by', 'm.submitted_at',
            ])
            ->orderBy('m.date', 'desc')
            ->get();

        // Recent historical logs (both approved and rejected) for CRUD management
        $historicalLogs = DB::table('measurements as m')
            ->join('stations as s', 's.id', '=', 'm.station_id')
            ->join('users as u', 'u.id', '=', 'm.submitted_by_id')
            ->leftJoin('users as r', 'r.id', '=', 'm.reviewed_by_id')
            ->where('m.measurement_type', 'rainfall')
            ->select([
                'm.id', 'm.station_id', 's.code as station_code', 's.name as station_name',
                'm.value', 'm.unit', 'm.date', 'm.status', 'm.review_notes',
                'u.display_name as submitted_by', 'm.submitted_at',
                'r.display_name as reviewed_by', 'm.reviewed_at',
            ])
            ->orderBy('m.date', 'desc')
            ->limit(100)
            ->get();

        return Inertia::render('Gis/Rainfall', [
            'stations' => $mappedStations,
            'pendingQueue' => $pendingQueue,
            'historicalLogs' => $historicalLogs,
            'canManage' => $canManage,
            'userRole' => $user?->role,
        ]);
    }

    public function groundwater(Request $request): Response
    {
        $user = auth()->user();
        $canManage = ($user?->canApprove() ?? false);

        $stations = Station::query()
            ->whereHas('capabilities', function ($query) {
                $query->where('measurement_type', 'groundwater_level');
            })
            ->where('is_active', true)
            ->get();

        $latest = DB::table('measurements as m1')
            ->select('m1.station_id', 'm1.value', 'm1.unit', 'm1.date')
            ->where('m1.status', 'approved')
            ->where('m1.measurement_type', 'groundwater_level')
            ->whereRaw("m1.date = (SELECT MAX(m2.date) FROM measurements as m2 WHERE m2.station_id = m1.station_id AND m2.measurement_type = m1.measurement_type AND m2.status = 'approved')")
            ->get()
            ->keyBy('station_id');

        $mappedStations = $stations->map(function (Station $station) use ($latest) {
            $reading = $latest->get($station->id);

            return [
                'id' => $station->id,
                'code' => $station->code,
                'name' => $station->name,
                'latitude' => $station->latitude,
                'longitude' => $station->longitude,
                'country' => $station->country,
                'river_basin' => $station->river_basin,
                'is_real_time' => $station->is_real_time,
                'owner_org' => $station->owner_org,
                'show_url' => route('stations.show', $station),
                'status' => $station->is_active ? 'active' : 'inactive',
                'is_active' => $station->is_active,
                'category' => $station->category,
                'water_source' => $station->water_source,
                'water_body_type' => $station->water_body_type,
                'summary' => $station->summary,
                'telemetry_system' => $station->telemetry_system,
                'gauge_code' => $station->gauge_code,
                'value' => $reading ? (float) $reading->value : null,
                'unit' => $reading ? $reading->unit : 'm',
                'date' => $reading ? $reading->date : null,
            ];
        });

        // Verification Queue of pending groundwater measurements
        $pendingQueue = DB::table('measurements as m')
            ->join('stations as s', 's.id', '=', 'm.station_id')
            ->join('users as u', 'u.id', '=', 'm.submitted_by_id')
            ->where('m.measurement_type', 'groundwater_level')
            ->where('m.status', 'pending')
            ->select([
                'm.id', 'm.station_id', 's.code as station_code', 's.name as station_name',
                'm.value', 'm.unit', 'm.date', 'm.status', 'u.display_name as submitted_by', 'm.submitted_at',
            ])
            ->orderBy('m.date', 'desc')
            ->get();

        // Recent historical logs (both approved and rejected) for CRUD management
        $historicalLogs = DB::table('measurements as m')
            ->join('stations as s', 's.id', '=', 'm.station_id')
            ->join('users as u', 'u.id', '=', 'm.submitted_by_id')
            ->leftJoin('users as r', 'r.id', '=', 'm.reviewed_by_id')
            ->where('m.measurement_type', 'groundwater_level')
            ->select([
                'm.id', 'm.station_id', 's.code as station_code', 's.name as station_name',
                'm.value', 'm.unit', 'm.date', 'm.status', 'm.review_notes',
                'u.display_name as submitted_by', 'm.submitted_at',
                'r.display_name as reviewed_by', 'm.reviewed_at',
            ])
            ->orderBy('m.date', 'desc')
            ->limit(100)
            ->get();

        return Inertia::render('Gis/Groundwater', [
            'stations' => $mappedStations,
            'pendingQueue' => $pendingQueue,
            'historicalLogs' => $historicalLogs,
            'canManage' => $canManage,
            'userRole' => $user?->role,
        ]);
    }

    public function storeMeasurement(Request $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $validated = $request->validate([
            'station_id' => ['required', 'uuid', 'exists:stations,id'],
            'measurement_type' => ['required', 'string', 'in:flow,dam_level,water_quality,rainfall,groundwater_level'],
            'parameter_id' => ['required_if:measurement_type,water_quality', 'nullable', 'uuid', 'exists:water_quality_parameters,id'],
            'value' => ['required', 'numeric'],
            'unit' => ['required', 'string'],
            'date' => ['required', 'date'],
            'fsc' => ['nullable', 'numeric'],
        ]);

        $status = 'pending';
        $isSelfOverride = false;

        // Auto-approve if submitted by Admin or Manager
        if ($user->canApprove()) {
            $status = 'approved';
            $isSelfOverride = true;
        }

        DB::table('measurements')->insert([
            'id' => (string) Str::uuid(),
            'station_id' => $validated['station_id'],
            'measurement_type' => $validated['measurement_type'],
            'parameter_id' => $validated['parameter_id'] ?? null,
            'value' => $validated['value'],
            'unit' => $validated['unit'],
            'date' => Carbon::parse($validated['date']),
            'fsc' => $validated['fsc'] ?? null,
            'status' => $status,
            'submitted_by_id' => $user->id,
            'submitted_at' => now(),
            'reviewed_by_id' => $isSelfOverride ? $user->id : null,
            'reviewed_at' => $isSelfOverride ? now() : null,
            'is_self_override' => $isSelfOverride,
        ]);

        return back();
    }

    public function updateMeasurement(Request $request, string $id): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $validated = $request->validate([
            'value' => ['required', 'numeric'],
            'unit' => ['required', 'string'],
            'date' => ['required', 'date'],
            'fsc' => ['nullable', 'numeric'],
        ]);

        $measurement = DB::table('measurements')->where('id', $id)->first();
        abort_unless($measurement, 404);

        // Access check: only owner or manager/admin can update
        $canEdit = ($measurement->submitted_by_id === $user->id) || $user->canApprove();
        abort_unless($canEdit, 403);

        $updateData = [
            'value' => $validated['value'],
            'unit' => $validated['unit'],
            'date' => Carbon::parse($validated['date']),
            'fsc' => $validated['fsc'] ?? null,
        ];

        // If a data clerk edits a rejected/approved record, revert status to pending
        if ($user->role === User::ROLE_CLERK) {
            $updateData['status'] = 'pending';
            $updateData['reviewed_by_id'] = null;
            $updateData['reviewed_at'] = null;
            $updateData['review_notes'] = null;
            $updateData['is_self_override'] = false;
        }

        DB::table('measurements')->where('id', $id)->update($updateData);

        return back();
    }

    public function destroyMeasurement(string $id): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user && $user->canApprove(), 403);

        DB::table('measurements')->where('id', $id)->delete();

        return back();
    }

    public function approveMeasurement(Request $request, string $id): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user && $user->canApprove(), 403);

        $measurement = DB::table('measurements as m')
            ->join('stations as s', 's.id', '=', 'm.station_id')
            ->where('m.id', $id)
            ->select('m.*', 's.name as station_name', 's.code as station_code')
            ->first();

        DB::table('measurements')->where('id', $id)->update([
            'status' => 'approved',
            'reviewed_by_id' => $user->id,
            'reviewed_at' => now(),
            'review_notes' => $request->input('review_notes') ?? 'Approved by Data Manager',
        ]);

        if ($measurement) {
            $isSelf = $measurement->submitted_by_id === $user->id;
            $label = ($measurement->station_name ?? $measurement->station_code ?? $id)
                .' ('.$measurement->measurement_type.')';

            AuditService::record(
                actionType: AuditService::ACTION_MEASUREMENT_APPROVED,
                entityType: 'Measurement',
                entityId: $id,
                entityLabel: $label,
                previousState: ['status' => 'pending'],
                newState: ['status' => 'approved'],
                reason: $request->input('review_notes'),
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
        }

        return back();
    }

    public function rejectMeasurement(Request $request, string $id): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user && $user->canApprove(), 403);

        $request->validate([
            'review_notes' => ['required', 'string'],
        ]);

        $measurement = DB::table('measurements as m')
            ->join('stations as s', 's.id', '=', 'm.station_id')
            ->where('m.id', $id)
            ->select('m.*', 's.name as station_name', 's.code as station_code')
            ->first();

        DB::table('measurements')->where('id', $id)->update([
            'status' => 'rejected',
            'reviewed_by_id' => $user->id,
            'reviewed_at' => now(),
            'review_notes' => $request->input('review_notes'),
        ]);

        if ($measurement) {
            $label = ($measurement->station_name ?? $measurement->station_code ?? $id)
                .' ('.$measurement->measurement_type.')';
            AuditService::record(
                actionType: AuditService::ACTION_MEASUREMENT_REJECTED,
                entityType: 'Measurement',
                entityId: $id,
                entityLabel: $label,
                previousState: ['status' => 'pending'],
                newState: ['status' => 'rejected'],
                reason: $request->input('review_notes'),
            );
        }

        return back();
    }

    public function getHistoricalData(Request $request, string $id): JsonResponse
    {
        $station = Station::find($id);
        abort_unless($station, 404);

        // Accept optional date-range and type filters
        $from = $request->query('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : now()->subYear();
        $to = $request->query('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : now();
        $typeFilter = $request->query('type'); // optional measurement_type filter

        $query = DB::table('measurements as m')
            ->leftJoin('water_quality_parameters as wqp', 'wqp.id', '=', 'm.parameter_id')
            ->where('m.station_id', $id)
            ->where('m.status', 'approved')
            ->whereBetween('m.date', [$from->toDateString(), $to->toDateString()])
            ->select(['m.id', 'm.measurement_type', 'm.value', 'm.unit', 'm.date', 'wqp.code as parameter_code'])
            ->orderBy('m.date', 'asc');

        if ($typeFilter) {
            $query->where('m.measurement_type', $typeFilter);
        }

        // Fetch last 365 days of approved measurements for this station
        $readings = $query->get();

        // Calculate flow duration percentiles if any flow records exist
        $flowVals = $readings->where('measurement_type', 'flow')->pluck('value')->toArray();
        $fdc = [];
        if (count($flowVals) > 0) {
            sort($flowVals);
            $n = count($flowVals);
            // Generate flow percentiles (Q0 to Q100)
            for ($i = 0; $i <= 100; $i += 5) {
                // Exceedance probability index
                $idx = (int) round(($n - 1) * ($i / 100));
                // Sort ascending means index = $n-1 is Q0 (exceeded 0% of the time, max flow)
                // index = 0 is Q100 (exceeded 100% of the time, min flow)
                $exceedIndex = $n - 1 - $idx;
                $fdc[] = [
                    'percentile' => $i,
                    'value' => (float) $flowVals[max(0, $exceedIndex)],
                ];
            }
        }

        // Calculate water quality exceedance counts
        $wqExceedances = [];
        $wqReadings = $readings->where('measurement_type', 'water_quality');
        if ($wqReadings->count() > 0) {
            $thresholds = DB::table('compliance_thresholds as ct')
                ->join('water_quality_parameters as wqp', 'wqp.id', '=', 'ct.parameter_id')
                ->where('ct.station_id', $id)
                ->get(['wqp.code', 'ct.min_value', 'ct.max_value'])
                ->keyBy('code');

            $grouped = $wqReadings->groupBy('parameter_code');
            foreach ($grouped as $code => $samples) {
                $limit = $thresholds->get($code);
                $exceedCount = 0;
                foreach ($samples as $sample) {
                    if ($limit) {
                        if ($limit->min_value !== null && $sample->value < $limit->min_value) {
                            $exceedCount++;
                        }
                        if ($limit->max_value !== null && $sample->value > $limit->max_value) {
                            $exceedCount++;
                        }
                    }
                }
                $wqExceedances[] = [
                    'parameter' => $code,
                    'total_samples' => $samples->count(),
                    'exceedances' => $exceedCount,
                    'min_value' => $samples->min('value'),
                    'max_value' => $samples->max('value'),
                ];
            }
        }

        return response()->json([
            'station_code' => $station->code,
            'station_name' => $station->name,
            'readings' => $readings,
            'fdc' => $fdc,
            'wq_matrix' => $wqExceedances,
        ]);
    }

    // ─── Measurement Bulk Import ──────────────────────────────────────────────

    public function importFlow(Request $request): JsonResponse
    {
        return $this->importMeasurementsBase($request, 'flow', 'm³/s');
    }

    public function importDamLevel(Request $request): JsonResponse
    {
        return $this->importMeasurementsBase($request, 'dam_level', 'm');
    }

    public function importWaterQuality(Request $request): JsonResponse
    {
        return $this->importMeasurementsBase($request, 'water_quality', null, true);
    }

    public function importRainfall(Request $request): JsonResponse
    {
        return $this->importMeasurementsBase($request, 'rainfall', 'mm');
    }

    public function importGroundwater(Request $request): JsonResponse
    {
        return $this->importMeasurementsBase($request, 'groundwater_level', 'm');
    }

    private function importMeasurementsBase(
        Request $request,
        string $measurementType,
        ?string $defaultUnit,
        bool $hasParameterCode = false
    ): JsonResponse {
        $user = auth()->user();
        abort_unless(
            $user && $user->canApprove(),
            403
        );

        $rows = $request->input('rows', []);
        if (! is_array($rows) || empty($rows)) {
            return response()->json(['error' => 'No rows provided.'], 422);
        }

        $isSelfOverride = true;
        $status = 'approved';

        // Build station_code → station_id map
        $stationCodes = array_unique(array_column($rows, 'station_code'));
        $stationMap = DB::table('stations')
            ->whereIn('code', $stationCodes)
            ->pluck('id', 'code');

        // For water quality: parameter_code → parameter_id map
        $paramMap = collect();
        if ($hasParameterCode) {
            $paramCodes = array_unique(array_filter(array_column($rows, 'parameter_code')));
            if (! empty($paramCodes)) {
                $paramMap = DB::table('water_quality_parameters')
                    ->whereIn('code', $paramCodes)
                    ->pluck('id', 'code');
            }
        }

        $inserts = [];
        $skipped = 0;
        $now = now();

        foreach ($rows as $row) {
            $stationCode = $row['station_code'] ?? null;
            $stationId = $stationCode ? $stationMap->get($stationCode) : null;
            if (! $stationId) {
                $skipped++;
                continue;
            }

            $parameterId = null;
            if ($hasParameterCode) {
                $paramCode = $row['parameter_code'] ?? null;
                $parameterId = $paramCode ? $paramMap->get($paramCode) : null;
                if (! $parameterId) {
                    $skipped++;
                    continue;
                }
            }

            $inserts[] = [
                'id' => (string) Str::uuid(),
                'station_id' => $stationId,
                'measurement_type' => $measurementType,
                'parameter_id' => $parameterId,
                'value' => (float) $row['value'],
                'unit' => isset($row['unit']) && $row['unit'] !== '' ? (string) $row['unit'] : ($defaultUnit ?? ''),
                'date' => Carbon::parse($row['date'])->toDateString(),
                'status' => $status,
                'submitted_by_id' => $user->id,
                'submitted_at' => $now,
                'reviewed_by_id' => $user->id,
                'reviewed_at' => $now,
                'is_self_override' => $isSelfOverride,
            ];
        }

        if (! empty($inserts)) {
            DB::table('measurements')->insert($inserts);
        }

        return response()->json([
            'inserted' => count($inserts),
            'skipped' => $skipped,
        ]);
    }

    // ─── Bulk CSV Exports ─────────────────────────────────────────────────────

    public function exportFlowCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->streamMeasurementsCsv('flow', 'flow-levels');
    }

    public function exportDamCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->streamMeasurementsCsv('dam_level', 'dam-levels');
    }

    public function exportWaterQualityCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->streamMeasurementsCsv('water_quality', 'water-quality');
    }

    public function exportRainfallCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->streamMeasurementsCsv('rainfall', 'rainfall');
    }

    public function exportGroundwaterCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->streamMeasurementsCsv('groundwater_level', 'groundwater');
    }

    private function streamMeasurementsCsv(
        string $measurementType,
        string $filePrefix
    ): \Symfony\Component\HttpFoundation\StreamedResponse {
        $filename = "{$filePrefix}-export-" . now()->format('Y-m-d') . '.csv';

        return response()->stream(function () use ($measurementType) {
            $handle = fopen('php://output', 'w');

            // BOM for UTF-8 Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'station_code', 'station_name', 'country', 'river_basin',
                'date', 'measurement_type', 'parameter_code', 'value', 'unit', 'status',
                'submitted_by', 'reviewed_by',
            ]);

            DB::table('measurements as m')
                ->join('stations as s', 's.id', '=', 'm.station_id')
                ->leftJoin('water_quality_parameters as wqp', 'wqp.id', '=', 'm.parameter_id')
                ->leftJoin('users as su', 'su.id', '=', 'm.submitted_by_id')
                ->leftJoin('users as ru', 'ru.id', '=', 'm.reviewed_by_id')
                ->where('m.measurement_type', $measurementType)
                ->orderBy('s.code')
                ->orderBy('m.date')
                ->select([
                    's.code as station_code',
                    's.name as station_name',
                    's.country',
                    's.river_basin',
                    'm.date',
                    'm.measurement_type',
                    'wqp.code as parameter_code',
                    'm.value',
                    'm.unit',
                    'm.status',
                    'su.display_name as submitted_by',
                    'ru.display_name as reviewed_by',
                ])
                ->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->station_code,
                            $row->station_name,
                            $row->country ?? '',
                            $row->river_basin ?? '',
                            $row->date,
                            $row->measurement_type,
                            $row->parameter_code ?? '',
                            $row->value,
                            $row->unit,
                            $row->status,
                            $row->submitted_by ?? '',
                            $row->reviewed_by ?? '',
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
