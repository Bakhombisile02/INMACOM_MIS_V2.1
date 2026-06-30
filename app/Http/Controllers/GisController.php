<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Queries\StationMeasurementQuery;
use App\Services\MeasurementStateManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @description Handles GIS data pages (flow, dam, water quality, rainfall, groundwater): renders Inertia views, processes measurement CRUD, 2-step approval, CSV import/export.
 * @author ITPTC / Datamatics
 * @since 2.1.0
 */
class GisController extends Controller
{
    public function flowLevels(Request $request): Response
    {
        return Inertia::render('Gis/FlowLevels', $this->getGisData('flow', 'flow', 'flow'));
    }

    public function damLevels(Request $request): Response
    {
        return Inertia::render('Gis/DamLevels', $this->getGisData('dam_level', 'dam_level'));
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

        $latest = StationMeasurementQuery::query()
            ->forTypes('water_quality')
            ->withStatuses('approved')
            ->latestPerStationAndParameter()
            ->withComplianceThresholds('water_quality')
            ->withWaterQualityParameters()
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

        $pendingQueue = StationMeasurementQuery::query()
            ->forTypes('water_quality')
            ->withStatuses('pending')
            ->withStationDetails()
            ->withWaterQualityParameters()
            ->withSubmitter()
            ->orderBy('m.date', 'desc')
            ->get();

        $historicalLogs = StationMeasurementQuery::query()
            ->forTypes('water_quality')
            ->withStationDetails()
            ->withWaterQualityParameters()
            ->withSubmitter()
            ->withReviewer()
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
        return Inertia::render('Gis/Rainfall', $this->getGisData('rainfall', 'rainfall'));
    }

    public function groundwater(Request $request): Response
    {
        return Inertia::render('Gis/Groundwater', $this->getGisData('groundwater_level', 'groundwater'));
    }

    /**
     * Retrieve common GIS page datasets for single-value measurement categories.
     * Reduces cognitive load and duplication (APoSD alignment).
     */
    private function getGisData(string $measurementType, string $capabilityType, ?string $thresholdType = null): array
    {
        $user = auth()->user();
        $canManage = ($user?->canApprove() ?? false);

        $stations = Station::query()
            ->whereHas('capabilities', function ($query) use ($capabilityType) {
                $query->where('measurement_type', $capabilityType);
            })
            ->where('is_active', true)
            ->get();

        $latest = StationMeasurementQuery::query()
            ->forTypes($measurementType)
            ->withStatuses('approved')
            ->latestPerStation()
            ->withComplianceThresholds($thresholdType)
            ->get()
            ->keyBy('station_id');

        $mappedStations = $stations->map(function (Station $station) use ($latest, $measurementType, $thresholdType) {
            $reading = $latest->get($station->id);

            $data = [
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
                'unit' => $reading ? $reading->unit : $this->getDefaultUnit($measurementType),
                'date' => $reading ? $reading->date : null,
            ];

            if ($thresholdType) {
                $data['limit'] = ($reading && $reading->limit_value !== null) ? (float) $reading->limit_value : null;
            }

            return $data;
        });

        $pendingQueue = StationMeasurementQuery::query()
            ->forTypes($measurementType)
            ->withStatuses('pending')
            ->withStationDetails()
            ->withSubmitter()
            ->orderBy('m.date', 'desc')
            ->get();

        $historicalLogs = StationMeasurementQuery::query()
            ->forTypes($measurementType)
            ->withStationDetails()
            ->withSubmitter()
            ->withReviewer()
            ->orderBy('m.date', 'desc')
            ->limit(100)
            ->get();

        return [
            'stations' => $mappedStations,
            'pendingQueue' => $pendingQueue,
            'historicalLogs' => $historicalLogs,
            'canManage' => $canManage,
            'userRole' => $user?->role,
        ];
    }

    /**
     * Map measurement types to their default units
     */
    private function getDefaultUnit(string $measurementType): string
    {
        return match ($measurementType) {
            'flow' => 'm³/s',
            'dam_level' => '%',
            'rainfall' => 'mm',
            'groundwater_level' => 'm',
            default => '',
        };
    }

    public function storeMeasurement(Request $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user, 403);

        MeasurementStateManager::store($user, $request->all());

        return back();
    }

    public function updateMeasurement(Request $request, string $id): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user, 403);

        MeasurementStateManager::update($user, $id, $request->all());

        return back();
    }

    public function destroyMeasurement(string $id): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user, 403);

        try {
            MeasurementStateManager::delete($user, $id);
        } catch (\RuntimeException $e) {
            abort(403, $e->getMessage());
        }

        return back();
    }

    public function approveMeasurement(Request $request, string $id): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user, 403);

        try {
            MeasurementStateManager::approve($user, $id, $request->input('review_notes'));
        } catch (\RuntimeException $e) {
            return back()->with('status', $e->getMessage());
        }

        return back();
    }

    public function rejectMeasurement(Request $request, string $id): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $request->validate([
            'review_notes' => ['required', 'string'],
        ]);

        try {
            MeasurementStateManager::reject($user, $id, (string) $request->input('review_notes'));
        } catch (\RuntimeException $e) {
            return back()->with('status', $e->getMessage());
        }

        return back();
    }

    public function getHistoricalData(Request $request, string $id): JsonResponse
    {
        $station = Station::find($id);
        abort_unless($station, 404);

        // Accept optional date-range and type filters; default to last year if none provided.
        try {
            $hasExplicitRange = $request->query('from') || $request->query('to');
            $to = $request->query('to')
                ? Carbon::parse($request->query('to'))->endOfDay()
                : ($hasExplicitRange ? null : now());
            $from = $request->query('from')
                ? Carbon::parse($request->query('from'))->startOfDay()
                : ($hasExplicitRange ? ($to ? $to->copy()->subYears(5) : null) : now()->subYear());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid date format for from/to parameters'], 400);
        }
        $typeFilter = $request->query('type'); // optional measurement_type filter

        $readingsQuery = StationMeasurementQuery::query()
            ->forStations($id)
            ->withStatuses('approved')
            ->withWaterQualityParameters()
            ->forDateRange($from, $to)
            ->orderBy('m.date', 'asc');

        if ($typeFilter) {
            $readingsQuery->forTypes($typeFilter);
        }

        $readings = $readingsQuery->get();

        $historyLogsQueryBuilder = StationMeasurementQuery::query()
            ->forStations($id)
            ->withStationDetails()
            ->withWaterQualityParameters()
            ->withSubmitter()
            ->withReviewer()
            ->forDateRange($from, $to)
            ->orderBy('m.date', 'desc')
            ->orderBy('m.submitted_at', 'desc');

        if ($typeFilter) {
            $historyLogsQueryBuilder->forTypes($typeFilter);
        }

        $historyLogs = $historyLogsQueryBuilder->get();

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
            'history_logs' => $historyLogs,
            'fdc' => $fdc,
            'wq_matrix' => $wqExceedances,
        ]);
    }

    // ─── Measurement Bulk Import ──────────────────────────────────────────────

    public function importFlow(Request $request)
    {
        return $this->importMeasurementsBase($request, 'flow', 'm³/s');
    }

    public function importDamLevel(Request $request)
    {
        return $this->importMeasurementsBase($request, 'dam_level', 'm');
    }

    public function importWaterQuality(Request $request)
    {
        return $this->importMeasurementsBase($request, 'water_quality', null, true);
    }

    public function importRainfall(Request $request)
    {
        return $this->importMeasurementsBase($request, 'rainfall', 'mm');
    }

    public function importGroundwater(Request $request)
    {
        return $this->importMeasurementsBase($request, 'groundwater_level', 'm');
    }

    private function importMeasurementsBase(
        Request $request,
        string $measurementType,
        ?string $defaultUnit,
        bool $hasParameterCode = false
    ) {
        $user = auth()->user();
        abort_unless(
            $user && $user->canApprove(),
            403
        );

        $rows = $request->input('rows', []);
        if (! is_array($rows) || empty($rows)) {
            return back()->withErrors(['message' => 'No rows provided.']);
        }

        $isSelfOverride = true;
        $status = 'approved';

        // Build station_code → station_id map
        $stationCodes = array_unique(array_column($rows, 'station_code'));
        $stationMap = DB::table('stations')
            ->whereIn('code', $stationCodes)
            ->pluck('id', 'code');

        // Fetch station capabilities
        $requiredCapability = $measurementType;
        if ($measurementType === 'groundwater_level') {
            $requiredCapability = 'groundwater';
        }

        $stationCapabilities = DB::table('station_capabilities')
            ->whereIn('station_id', $stationMap->values())
            ->get()
            ->groupBy('station_id')
            ->map(fn ($items) => $items->pluck('measurement_type')->all());

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
        $now = now();

        foreach ($rows as $row) {
            $stationCode = $row['station_code'] ?? null;
            $stationId = $stationCode ? $stationMap->get($stationCode) : null;
            if (! $stationId) {
                return back()->withErrors(['message' => "Station code '{$stationCode}' not found in the database."]);
            }

            // Verify station capability to prevent data type mismatch
            $caps = $stationCapabilities->get($stationId, []);
            if (! in_array($requiredCapability, $caps)) {
                return back()->withErrors([
                    'message' => "Station '{$stationCode}' does not support '{$measurementType}' measurements.",
                ]);
            }

            // Verify mandatory value is present and not empty
            if (! isset($row['value']) || $row['value'] === '' || $row['value'] === null) {
                return back()->withErrors([
                    'message' => "Value is required for all records. Gaps or empty cells are not allowed (found empty value for station '{$stationCode}' on date '{$row['date']}').",
                ]);
            }

            $parameterId = null;
            if ($hasParameterCode) {
                $paramCode = $row['parameter_code'] ?? null;
                $parameterId = $paramCode ? $paramMap->get($paramCode) : null;
                if (! $parameterId) {
                    return back()->withErrors(['message' => "Water quality parameter '{$paramCode}' not found in the database."]);
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
            MeasurementStateManager::import($user, $measurementType, $inserts);
        }

        $count = count($inserts);

        return redirect()->back()->with('status', "{$count} {$measurementType} measurement(s) imported successfully.");
    }

    // ─── Bulk CSV Exports ─────────────────────────────────────────────────────

    public function exportFlowCsv(): StreamedResponse
    {
        return $this->streamMeasurementsCsv('flow', 'flow-levels');
    }

    public function exportDamCsv(): StreamedResponse
    {
        return $this->streamMeasurementsCsv('dam_level', 'dam-levels');
    }

    public function exportWaterQualityCsv(): StreamedResponse
    {
        return $this->streamMeasurementsCsv('water_quality', 'water-quality');
    }

    public function exportRainfallCsv(): StreamedResponse
    {
        return $this->streamMeasurementsCsv('rainfall', 'rainfall');
    }

    public function exportGroundwaterCsv(): StreamedResponse
    {
        return $this->streamMeasurementsCsv('groundwater_level', 'groundwater');
    }

    private function streamMeasurementsCsv(
        string $measurementType,
        string $filePrefix
    ): StreamedResponse {
        $filename = "{$filePrefix}-export-".now()->format('Y-m-d').'.csv';

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
