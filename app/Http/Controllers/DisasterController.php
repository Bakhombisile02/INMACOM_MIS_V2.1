<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Queries\HazardStatusQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DisasterController extends Controller
{
    public function index(Request $request): Response
    {
        $hazardTypes = DB::table('hazard_types')
            ->orderBy('name')
            ->get(['code', 'name'])
            ->toArray();

        $statusLevels = DB::table('hazard_status_levels')
            ->orderBy('hazard_code')
            ->orderBy('severity')
            ->get(['hazard_code', 'level_code', 'name', 'severity', 'color', 'description', 'actions_required'])
            ->toArray();

        $areas = DB::table('management_areas')
            ->where('is_active', true)
            ->orderBy('country')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'basin', 'country'])
            ->toArray();

        $currentStatuses = HazardStatusQuery::query()
            ->withDetails()
            ->get()
            ->map(fn ($row) => (object) [
                'hazard_code' => $row->hazard_code,
                'area_id' => $row->area_id,
                'level_code' => $row->level_code,
                'score' => $row->score,
                'calculated_at' => $row->calculated_at,
                'calculation_notes' => $row->calculation_notes,
                'level_name' => $row->level_name,
                'color' => $row->color,
                'severity' => $row->severity,
                'area_name' => $row->area_name,
            ])
            ->toArray();

        $recentIncidents = DB::table('disaster_incidents as di')
            ->leftJoin('hazard_types as ht', 'di.hazard_code', '=', 'ht.code')
            ->leftJoin('management_areas as ma', 'di.area_id', '=', 'ma.id')
            ->select([
                'di.id',
                'di.reference',
                'di.title',
                'di.severity_level',
                'di.incident_status',
                'di.review_status',
                'di.hazard_code',
                'di.latitude',
                'di.longitude',
                'di.affected_radius_km',
                'di.occurred_at',
                'di.reported_at',
                'di.resolved_at',
                'di.area_id',
                'ht.name as hazard_name',
                'ma.name as area_name',
            ])
            ->orderBy('di.reported_at', 'desc')
            ->limit(50)
            ->get()
            ->toArray();

        $incidentStations = DB::table('incident_stations as ins')
            ->join('stations as s', 'ins.station_id', '=', 's.id')
            ->join('disaster_incidents as di', 'ins.incident_id', '=', 'di.id')
            ->whereNull('di.resolved_at')
            ->select([
                'ins.incident_id',
                'ins.station_id',
                'ins.role',
                's.code',
                's.name',
                's.latitude',
                's.longitude',
            ])
            ->get()
            ->toArray();

        $activeIncidentCount = DB::table('disaster_incidents')
            ->whereNull('resolved_at')
            ->count();

        $watchOrAboveAreas = HazardStatusQuery::query()
            ->withSeverityAtLeast(2)
            ->countDistinctAreas();

        $criticalAreas = HazardStatusQuery::query()
            ->withSeverityAtLeast(4)
            ->countDistinctAreas();

        $canManage = $request->user() && in_array($request->user()->role, [
            User::ROLE_ADMIN,
            User::ROLE_MANAGER,
        ], true);

        return Inertia::render('Disaster/Index', [
            'hazardTypes' => $hazardTypes,
            'statusLevels' => $statusLevels,
            'areas' => $areas,
            'currentStatuses' => $currentStatuses,
            'recentIncidents' => $recentIncidents,
            'incidentStations' => $incidentStations,
            'stats' => [
                'active_incidents' => $activeIncidentCount,
                'watch_or_above_areas' => $watchOrAboveAreas,
                'critical_areas' => $criticalAreas,
            ],
            'canManage' => $canManage,
        ]);
    }
}
