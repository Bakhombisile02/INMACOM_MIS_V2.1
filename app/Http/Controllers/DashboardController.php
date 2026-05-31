<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        // 1. Core Summary Stats
        $summary = [
            'stations' => $this->countTable('stations'),
            'measurements' => $this->countTable('measurements'),
            'incidents' => $this->countTable('disaster_incidents'),
            'documents' => $this->countTable('documents'),
        ];

        // 2. Reservoir storage details (% fill levels)
        $damStations = DB::table('stations')
            ->where('category', 'dam')
            ->where('is_active', true)
            ->get();

        $damStationIds = $damStations->pluck('id');

        $latestDamMeasurements = DB::table('measurements')
            ->whereIn('station_id', $damStationIds)
            ->where('measurement_type', 'dam_level')
            ->where('status', 'approved')
            ->orderBy('date', 'desc')
            ->get()
            ->unique('station_id')
            ->keyBy('station_id');

        $reservoirs = $damStations
            ->map(function ($station) use ($latestDamMeasurements) {
                $latestMeasurement = $latestDamMeasurements->get($station->id);
                
                return [
                    'id' => $station->id,
                    'code' => $station->code,
                    'name' => $station->name,
                    'country' => $station->country,
                    'river_basin' => $station->river_basin,
                    'latest_value' => $latestMeasurement ? (float) $latestMeasurement->value : null,
                    'unit' => $latestMeasurement ? $latestMeasurement->unit : 'Mm³',
                    'fsc' => $latestMeasurement ? (float) $latestMeasurement->fsc : null,
                    'date' => $latestMeasurement ? $latestMeasurement->date : null,
                ];
            })
            ->filter(fn ($d) => $d['latest_value'] !== null)
            ->values()
            ->toArray();

        // 3. Ressano Garcia gauge flow compliance (IIMA most critical gauge E-23 / X2H036)
        $ressanoGarcia = DB::table('stations')
            ->where(function ($query) {
                $query->where('code', 'X2H036')
                      ->orWhere('code', 'E-23')
                      ->orWhere('gauge_code', 'E-23')
                      ->orWhere('gauge_code', 'X2H036');
            })
            ->first();
        
        $ressanoGarciaFlow = null;
        if ($ressanoGarcia) {
            $latestFlow = DB::table('measurements')
                ->where('station_id', $ressanoGarcia->id)
                ->where('measurement_type', 'flow')
                ->where('status', 'approved')
                ->orderBy('date', 'desc')
                ->first();
            
            if ($latestFlow) {
                $ressanoGarciaFlow = [
                    'value' => (float) $latestFlow->value,
                    'unit' => $latestFlow->unit,
                    'date' => $latestFlow->date,
                    'min_required' => 2.6, // IIMA minimum E-23
                    'is_compliant' => $latestFlow->value >= 2.6,
                ];
            }
        }

        // 4. IIMA Cross-border Ecological Flow Key Points Compliance
        $activeKeyPoints = DB::table('iima_eflow_key_points')
            ->where('is_active', true)
            ->get();

        $pointIds = $activeKeyPoints->pluck('id');
        $stationIds = $activeKeyPoints->whereNotNull('station_id')->pluck('station_id');

        $requirements = DB::table('iima_eflow_requirements')
            ->whereIn('key_point_id', $pointIds)
            ->get()
            ->keyBy('key_point_id');

        $latestFlows = DB::table('measurements')
            ->whereIn('station_id', $stationIds)
            ->where('measurement_type', 'flow')
            ->where('status', 'approved')
            ->orderBy('date', 'desc')
            ->get()
            ->unique('station_id')
            ->keyBy('station_id');

        $eflowPoints = $activeKeyPoints
            ->map(function ($point) use ($requirements, $latestFlows) {
                $requirement = $requirements->get($point->id);
                $latestFlow = $point->station_id ? $latestFlows->get($point->station_id) : null;
                
                $currentFlow = $latestFlow ? (float) $latestFlow->value : null;
                $minRequired = $requirement ? (float) $requirement->min_flow_m3_s : null;
                
                $isCompliant = null;
                if ($currentFlow !== null && $minRequired !== null) {
                    $isCompliant = $currentFlow >= $minRequired;
                }
                
                return [
                    'id' => $point->id,
                    'code' => $point->code,
                    'name' => $point->name,
                    'river' => $point->river,
                    'country' => $point->country,
                    'min_flow_m3_s' => $minRequired,
                    'current_flow' => $currentFlow,
                    'is_compliant' => $isCompliant,
                    'date' => $latestFlow ? $latestFlow->date : null,
                ];
            })
            ->toArray();

        // 5. Disaster / Hazard Status Levels and Scores per subcatchment (FSW and Ds)
        $hazardStatuses = [];
        if (Schema::hasTable('hazard_status_current')) {
            $hazardStatuses = DB::table('hazard_status_current')
                ->join('hazard_types', 'hazard_status_current.hazard_code', '=', 'hazard_types.code')
                ->join('management_areas', 'hazard_status_current.area_id', '=', 'management_areas.id')
                ->leftJoin('hazard_status_levels', function ($join) {
                    $join->on('hazard_status_current.hazard_code', '=', 'hazard_status_levels.hazard_code')
                         ->on('hazard_status_current.level_code', '=', 'hazard_status_levels.level_code');
                })
                ->select(
                    'hazard_status_current.id',
                    'hazard_status_current.hazard_code',
                    'hazard_types.name as hazard_name',
                    'management_areas.name as area_name',
                    'management_areas.code as area_code',
                    'hazard_status_current.level_code',
                    'hazard_status_levels.name as level_name',
                    'hazard_status_levels.severity',
                    'hazard_status_levels.color',
                    'hazard_status_current.score',
                    'hazard_status_current.calculated_at'
                )
                ->orderBy('hazard_status_current.calculated_at', 'desc')
                ->get()
                ->toArray();
        }

        // 6. Active Emergency Incidents (active Floods, Droughts, and Spills)
        $activeIncidents = DB::table('disaster_incidents')
            ->join('hazard_types', 'disaster_incidents.hazard_code', '=', 'hazard_types.code')
            ->leftJoin('management_areas', 'disaster_incidents.area_id', '=', 'management_areas.id')
            ->leftJoin('pollution_incident_details', 'disaster_incidents.id', '=', 'pollution_incident_details.incident_id')
            ->select(
                'disaster_incidents.id',
                'disaster_incidents.reference',
                'disaster_incidents.title',
                'disaster_incidents.hazard_code',
                'hazard_types.name as hazard_name',
                'disaster_incidents.incident_status',
                'disaster_incidents.severity_level',
                'disaster_incidents.reported_at',
                'management_areas.name as area_name',
                'pollution_incident_details.pollutant_name',
                'pollution_incident_details.estimated_mass_kg',
                'pollution_incident_details.fish_kill_observed',
                'pollution_incident_details.waterborne_disease_reported'
            )
            ->where('disaster_incidents.incident_status', '!=', 'resolved')
            ->orderBy('disaster_incidents.reported_at', 'desc')
            ->get()
            ->toArray();

        // 7. Pending queue metrics for Data Managers
        $pendingMeasurementsCount = DB::table('measurements')->where('status', 'pending')->count();
        $pendingIncidentsCount = DB::table('disaster_incidents')->where('review_status', 'pending')->count();

        return Inertia::render('Dashboard/Index', [
            'summary' => $summary,
            'reservoirs' => $reservoirs,
            'ressanoGarciaFlow' => $ressanoGarciaFlow,
            'eflowPoints' => $eflowPoints,
            'hazardStatuses' => $hazardStatuses,
            'activeIncidents' => $activeIncidents,
            'verificationQueue' => [
                'measurements' => $pendingMeasurementsCount,
                'incidents' => $pendingIncidentsCount,
            ],
        ]);
    }

    private function countTable(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)->count();
    }
}
