<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Queries\HazardStatusQuery;
use App\Queries\StationMeasurementQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public-facing GIS controller.
 *
 * Powers the anonymous "Explore" page. Returns a curated, low-risk slice of the
 * monitoring network:
 *  - Approved + pending readings (each tagged with a confidence level).
 *  - Hazards aggregated by management area — no exact incident coordinates.
 *  - Daily basin-aggregated historical series per module (last 90 days).
 */
class PublicGisController extends Controller
{
    /**
     * Measurement types surfaced publicly. Order matters for default tab.
     */
    private const MODULES = [
        'dam_level' => ['unit' => '%', 'isPercent' => true],
        'flow' => ['unit' => 'm³/s', 'isPercent' => false],
        'rainfall' => ['unit' => 'mm', 'isPercent' => false],
        'groundwater_level' => ['unit' => 'm', 'isPercent' => false],
        'water_quality' => ['unit' => '', 'isPercent' => false],
    ];

    public function explore(): Response
    {
        $stationsByType = $this->stationsByType();
        $latestByType = $this->latestReadingsByType();

        $modules = [];
        foreach (self::MODULES as $type => $meta) {
            $stations = $stationsByType[$type] ?? collect();
            $latest = $latestByType[$type] ?? collect();
            $mapped = $this->mapStations($stations, $latest, $meta);
            $historical = $this->historicalDailyAverage($type, 90);

            $modules[$type] = [
                'stations' => $mapped->values()->all(),
                'historical' => $historical,
                'stats' => [
                    'count' => $mapped->count(),
                    'with_data' => $mapped->whereNotNull('value')->count(),
                    'alerts' => $mapped->where('is_alert', true)->count(),
                    'provisional' => $mapped->where('confidence', 'provisional')->count(),
                    'verified' => $mapped->where('confidence', 'verified')->count(),
                    'last_updated' => $mapped->max('date'),
                ],
            ];
        }

        return Inertia::render('Public/Explore', [
            'modules' => $modules,
            'hazardAreas' => $this->hazardAreas(),
            'stats' => $this->headlineStats(),
        ]);
    }

    /**
     * Public station-level historical series for Explore station detail charts.
     *
     * Returns only non-sensitive fields and only approved/pending records so
     * public users can see verified + provisional trends consistently.
     */
    public function stationHistorical(Request $request, string $id): JsonResponse
    {
        $type = (string) $request->query('type', '');
        if (! array_key_exists($type, self::MODULES)) {
            return response()->json([
                'message' => 'Invalid type parameter.',
            ], 422);
        }

        $station = Station::query()
            ->where('id', $id)
            ->where('is_active', true)
            ->first();

        abort_unless($station, 404);

        $fromRaw = $request->query('from');
        $toRaw = $request->query('to');
        $from = $this->parseOptionalDate($fromRaw, true);
        $to = $this->parseOptionalDate($toRaw, false);

        if (($fromRaw && ! $from) || ($toRaw && ! $to)) {
            return response()->json([
                'message' => 'Invalid date format. Use YYYY-MM-DD.',
            ], 422);
        }

        $readings = StationMeasurementQuery::query()
            ->forStations($id)
            ->forTypes($type)
            ->withStatuses(['approved', 'pending'])
            ->forDateRange($from, $to)
            ->orderBy('m.date', 'asc')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'station_id' => $row->station_id,
                    'measurement_type' => $row->measurement_type,
                    'value' => (float) $row->value,
                    'unit' => $row->unit,
                    'date' => $row->date,
                    'status' => $row->status,
                    'confidence' => $row->status === 'approved' ? 'verified' : 'provisional',
                ];
            })->values();

        return response()->json([
            'station' => [
                'id' => $station->id,
                'code' => $station->code,
                'name' => $station->name,
            ],
            'type' => $type,
            'readings' => $readings,
        ]);
    }

    /**
     * Group active stations by the measurement types they advertise via
     * station_capabilities. A single station may surface under multiple modules.
     */
    private function stationsByType(): array
    {
        $rows = Station::query()
            ->where('is_active', true)
            ->with('capabilities:station_id,measurement_type')
            ->get();

        $byType = [];
        foreach ($rows as $station) {
            foreach ($station->capabilities as $cap) {
                $byType[$cap->measurement_type][] = $station;
            }
        }

        return array_map(fn ($list) => collect($list), $byType);
    }

    /**
     * Latest reading (approved OR pending) per station per measurement type.
     */
    private function latestReadingsByType(): array
    {
        $rows = StationMeasurementQuery::query()
            ->withStatuses(['approved', 'pending'])
            ->forTypes(array_keys(self::MODULES))
            ->latestPerStation()
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row->measurement_type][$row->station_id] = $row;
        }

        return array_map(fn ($map) => collect($map), $grouped);
    }

    /**
     * Map raw station + reading into the GisStationData shape used by the
     * shared map component, with a `confidence` tag and pre-computed alert
     * flag (red marker) for module-specific thresholds.
     */
    private function mapStations($stations, $latest, array $meta)
    {
        return $stations->map(function (Station $station) use ($latest, $meta) {
            $reading = $latest->get($station->id);
            $value = $reading ? (float) $reading->value : null;
            $unit = $reading->unit ?? $meta['unit'];
            $confidence = $reading
                ? ($reading->status === 'approved' ? 'verified' : 'provisional')
                : 'unknown';

            // Simple module-aware alert heuristic (dam < 20%, missing → no alert).
            $isAlert = false;
            if ($value !== null && $meta['isPercent']) {
                $isAlert = $value < 20;
            }

            $color = $reading === null
                ? 'rgb(127, 127, 127)'   // grey: no data
                : ($isAlert
                    ? 'rgb(255, 0, 0)'   // red: alert
                    : 'rgb(43, 138, 62)' // green: ok
                );

            return [
                'id' => $station->id,
                'code' => $station->code,
                'name' => $station->name,
                'latitude' => (float) $station->latitude,
                'longitude' => (float) $station->longitude,
                'country' => $station->country,
                'river_basin' => $station->river_basin,
                'is_real_time' => (bool) $station->is_real_time,
                'owner_org' => $station->owner_org,
                'value' => $value,
                'unit' => $unit,
                'date' => $reading?->date,
                'confidence' => $confidence,
                'is_alert' => $isAlert,
                'color' => $color,
                'popupData' => array_values(array_filter([
                    $value !== null ? ['label' => 'Latest', 'value' => "{$value} {$unit}"] : null,
                    $reading ? ['label' => 'As of', 'value' => $reading->date] : null,
                    [
                        'label' => 'Confidence',
                        'value' => $confidence === 'verified' ? 'Verified' : ($confidence === 'provisional' ? 'Provisional' : 'Unknown'),
                        'color' => $confidence === 'verified' ? '#2b8a3e' : ($confidence === 'provisional' ? '#e8590c' : '#868e96'),
                    ],
                    $station->country ? ['label' => 'Country', 'value' => $station->country] : null,
                ])),
            ];
        });
    }

    /**
     * Basin-wide daily average for one measurement type over the last N days.
     * Aggregates approved + pending readings (provisional values still help
     * visualise the trend, with the daily count shown for context).
     */
    private function historicalDailyAverage(string $type, int $days): array
    {
        $since = now()->subDays($days);

        return StationMeasurementQuery::query()
            ->forTypes($type)
            ->withStatuses(['approved', 'pending'])
            ->forDateRange($since, null)
            ->aggregateDailyAverage()
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => [
                'date' => $r->date,
                'value' => round((float) $r->value, 2),
                'samples' => (int) $r->samples,
            ])
            ->all();
    }

    /**
     * Hazard status aggregated by management area (no precise incident
     * coordinates — the public layer is area-level only). Returns at most
     * the most severe status per area+hazard pair.
     */
    private function hazardAreas(): array
    {
        return HazardStatusQuery::query()
            ->withDetails()
            ->withActiveIncidentCounts()
            ->orderBy('severity', 'desc')
            ->orderBy('area_name')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    private function headlineStats(): array
    {
        $totalStations = Station::where('is_active', true)->count();
        $countries = Station::where('is_active', true)
            ->whereNotNull('country')
            ->distinct()
            ->count('country');
        $lastSync = StationMeasurementQuery::query()
            ->withStatuses(['approved', 'pending'])
            ->maxSubmittedAt();
        $activeHazards = Schema::hasTable('disaster_incidents')
            ? DB::table('disaster_incidents')->whereNull('resolved_at')->count()
            : 0;
        $watchOrAbove = HazardStatusQuery::query()
            ->withSeverityAtLeast(2)
            ->countDistinctAreas();

        return [
            'total_stations' => $totalStations,
            'countries' => $countries,
            'last_sync' => $lastSync,
            'active_hazards' => $activeHazards,
            'watch_or_above_areas' => $watchOrAbove,
        ];
    }

    private function parseOptionalDate(mixed $value, bool $startOfDay): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $date = Carbon::parse((string) $value);

            return $startOfDay ? $date->startOfDay() : $date->endOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
