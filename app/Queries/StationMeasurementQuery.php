<?php

namespace App\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * StationMeasurementQuery deep module.
 * Consolidates querying of approved, pending, and historical measurements across all GIS and Dashboard modules.
 * Maximises flexibility to support custom filters, flexible joins, and multiple types.
 */
class StationMeasurementQuery
{
    protected array $types = [];

    protected array $stationIds = [];

    protected array $statuses = [];

    protected ?Carbon $fromDate = null;

    protected ?Carbon $toDate = null;

    protected bool $latestOnly = false;

    protected bool $byParameter = false;

    protected ?string $thresholdType = null;

    protected bool $includeWaterQualityParameters = false;

    protected bool $includeSubmitter = false;

    protected bool $includeReviewer = false;

    protected bool $includeStationDetails = false;

    protected bool $aggregateDailyAverage = false;

    protected array $orderBys = [];

    protected ?int $limit = null;

    /**
     * Factory method to start a new fluent query.
     */
    public static function query(): self
    {
        return new self;
    }

    /**
     * Filter by measurement type(s) (e.g. flow, dam_level, water_quality, rainfall, groundwater_level).
     */
    public function forTypes(string|array $types): self
    {
        $this->types = array_merge($this->types, (array) $types);

        return $this;
    }

    /**
     * Filter by station ID(s).
     */
    public function forStations(string|array|Collection $stationIds): self
    {
        if ($stationIds instanceof Collection) {
            $stationIds = $stationIds->toArray();
        }
        $this->stationIds = array_merge($this->stationIds, (array) $stationIds);

        return $this;
    }

    /**
     * Filter by measurement approval status(es) (e.g. approved, pending, rejected).
     */
    public function withStatuses(string|array $statuses): self
    {
        $this->statuses = array_merge($this->statuses, (array) $statuses);

        return $this;
    }

    /**
     * Filter by a date range.
     */
    public function forDateRange(?Carbon $from, ?Carbon $to): self
    {
        $this->fromDate = $from;
        $this->toDate = $to;

        return $this;
    }

    /**
     * Restrict results to the latest measurement per station.
     */
    public function latestPerStation(): self
    {
        $this->latestOnly = true;
        $this->byParameter = false;

        return $this;
    }

    /**
     * Restrict results to the latest measurement per station and water quality parameter.
     */
    public function latestPerStationAndParameter(): self
    {
        $this->latestOnly = true;
        $this->byParameter = true;

        return $this;
    }

    /**
     * Join compliance thresholds for the station/parameter.
     */
    public function withComplianceThresholds(?string $thresholdType = null): self
    {
        $this->thresholdType = $thresholdType;

        return $this;
    }

    /**
     * Join water quality parameters details.
     */
    public function withWaterQualityParameters(): self
    {
        $this->includeWaterQualityParameters = true;

        return $this;
    }

    /**
     * Join submitter user details.
     */
    public function withSubmitter(): self
    {
        $this->includeSubmitter = true;

        return $this;
    }

    /**
     * Join reviewer user details.
     */
    public function withReviewer(): self
    {
        $this->includeReviewer = true;

        return $this;
    }

    /**
     * Join station details (code, name, basin, country, coordinates).
     */
    public function withStationDetails(): self
    {
        $this->includeStationDetails = true;

        return $this;
    }

    /**
     * Group results and calculate the daily average value.
     */
    public function aggregateDailyAverage(): self
    {
        $this->aggregateDailyAverage = true;

        return $this;
    }

    /**
     * Add ordering.
     */
    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orderBys[] = ['column' => $column, 'direction' => $direction];

        return $this;
    }

    /**
     * Limit the number of returned records.
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Build the underlying DB query builder based on current filters and joins.
     */
    protected function buildQuery(): Builder
    {
        // Validate incompatible options
        if ($this->aggregateDailyAverage && (
            $this->includeStationDetails ||
            $this->includeWaterQualityParameters ||
            $this->includeSubmitter ||
            $this->includeReviewer ||
            $this->thresholdType
        )) {
            throw new \InvalidArgumentException(
                'Daily aggregation cannot be combined with detail joins. Use only aggregateDailyAverage() without withStationDetails(), withWaterQualityParameters(), withSubmitter(), withReviewer(), or withComplianceThresholds().'
            );
        }

        $query = DB::table('measurements as m');

        // Select columns
        if ($this->aggregateDailyAverage) {
            $query->selectRaw('DATE(m.date) as date, CAST(AVG(m.value) AS DECIMAL(12,4)) as value, COUNT(*) as samples');
        } else {
            $query->select([
                'm.id',
                'm.station_id',
                'm.measurement_type',
                'm.parameter_id',
                'm.value',
                'm.unit',
                'm.date',
                'm.status',
                'm.fsc',
                'm.review_notes',
                'm.submitted_at',
                'm.reviewed_at',
            ]);
        }

        // Apply filters
        if (! empty($this->types)) {
            $query->whereIn('m.measurement_type', $this->types);
        }

        if (! empty($this->stationIds)) {
            $query->whereIn('m.station_id', $this->stationIds);
        }

        if (! empty($this->statuses)) {
            $query->whereIn('m.status', $this->statuses);
        }

        if ($this->fromDate) {
            $query->whereDate('m.date', '>=', $this->fromDate->toDateString());
        }

        if ($this->toDate) {
            $query->whereDate('m.date', '<=', $this->toDate->toDateString());
        }

        // Join station details if requested or if ordering/grouping by station fields
        if ($this->includeStationDetails) {
            $query->join('stations as s', 's.id', '=', 'm.station_id')
                ->addSelect([
                    's.code as station_code',
                    's.name as station_name',
                    's.country as country',
                    's.river_basin as river_basin',
                    's.latitude as latitude',
                    's.longitude as longitude',
                    's.is_real_time as is_real_time',
                    's.owner_org as owner_org',
                ]);
        }

        // Join WQ parameters
        if ($this->includeWaterQualityParameters) {
            $query->leftJoin('water_quality_parameters as wqp', 'wqp.id', '=', 'm.parameter_id')
                ->addSelect([
                    'wqp.code as parameter_code',
                    'wqp.name as parameter_name',
                ]);
        }

        // Join compliance thresholds
        if ($this->thresholdType) {
            $query->leftJoin('compliance_thresholds as ct', function ($join) {
                $join->on('ct.station_id', '=', 'm.station_id')
                    ->where('ct.data_type', '=', $this->thresholdType);
                if ($this->thresholdType === 'water_quality') {
                    $join->on('ct.parameter_id', '=', 'm.parameter_id');
                }
            })->addSelect([
                'ct.min_value as min_value',
                'ct.max_value as max_value',
                'ct.min_value as limit_value', // legacy alias support
            ]);
        }

        // Join users
        if ($this->includeSubmitter) {
            $query->leftJoin('users as su', 'su.id', '=', 'm.submitted_by_id')
                ->addSelect('su.display_name as submitted_by');
        }

        if ($this->includeReviewer) {
            $query->leftJoin('users as ru', 'ru.id', '=', 'm.reviewed_by_id')
                ->addSelect('ru.display_name as reviewed_by');
        }

        // Handle latest-only subqueries
        if ($this->latestOnly) {
            if ($this->byParameter) {
                // Latest per station & parameter
                $subQuery = DB::table('measurements as m2')
                    ->select('m2.id')
                    ->selectRaw('ROW_NUMBER() OVER (PARTITION BY m2.station_id, m2.measurement_type, m2.parameter_id ORDER BY m2.date DESC, m2.id DESC) as rn');

                if (! empty($this->statuses)) {
                    $subQuery->whereIn('m2.status', $this->statuses);
                }
                if (! empty($this->types)) {
                    $subQuery->whereIn('m2.measurement_type', $this->types);
                }
                if (! empty($this->stationIds)) {
                    $subQuery->whereIn('m2.station_id', $this->stationIds);
                }
                if ($this->fromDate) {
                    $subQuery->whereDate('m2.date', '>=', $this->fromDate->toDateString());
                }
                if ($this->toDate) {
                    $subQuery->whereDate('m2.date', '<=', $this->toDate->toDateString());
                }

                $query->joinSub($subQuery, 'm_latest', function ($join) {
                    $join->on('m.id', '=', 'm_latest.id')
                        ->where('m_latest.rn', '=', 1);
                });
            } else {
                // Latest per station
                $subQuery = DB::table('measurements as m2')
                    ->select('m2.id')
                    ->selectRaw('ROW_NUMBER() OVER (PARTITION BY m2.station_id, m2.measurement_type ORDER BY m2.date DESC, m2.id DESC) as rn');

                if (! empty($this->statuses)) {
                    $subQuery->whereIn('m2.status', $this->statuses);
                }
                if (! empty($this->types)) {
                    $subQuery->whereIn('m2.measurement_type', $this->types);
                }
                if (! empty($this->stationIds)) {
                    $subQuery->whereIn('m2.station_id', $this->stationIds);
                }
                if ($this->fromDate) {
                    $subQuery->whereDate('m2.date', '>=', $this->fromDate->toDateString());
                }
                if ($this->toDate) {
                    $subQuery->whereDate('m2.date', '<=', $this->toDate->toDateString());
                }

                $query->joinSub($subQuery, 'm_latest', function ($join) {
                    $join->on('m.id', '=', 'm_latest.id')
                        ->where('m_latest.rn', '=', 1);
                });
            }
        }

        // Apply grouping for aggregates
        if ($this->aggregateDailyAverage) {
            $query->groupByRaw('DATE(m.date)');
        }

        // Apply ordering
        foreach ($this->orderBys as $order) {
            $query->orderBy($order['column'], $order['direction']);
        }

        // Apply limit
        if ($this->limit) {
            $query->limit($this->limit);
        }

        return $query;
    }

    /**
     * Retrieve the collection of results.
     */
    public function get(): Collection
    {
        return $this->buildQuery()->get();
    }

    public function count(): int
    {
        // For counts, build the query without orderings/limits and aggregation to avoid SQL errors
        $originalLimit = $this->limit;
        $originalOrderBys = $this->orderBys;
        $originalAggregate = $this->aggregateDailyAverage;

        $this->limit = null;
        $this->orderBys = [];
        $this->aggregateDailyAverage = false;

        $count = (int) $this->buildQuery()->count();

        $this->limit = $originalLimit;
        $this->orderBys = $originalOrderBys;
        $this->aggregateDailyAverage = $originalAggregate;

        return $count;
    }

    /**
     * Retrieve the max submission timestamp.
     */
    public function maxSubmittedAt(): ?string
    {
        $originalLimit = $this->limit;
        $originalOrderBys = $this->orderBys;
        $originalAggregate = $this->aggregateDailyAverage;

        $this->limit = null;
        $this->orderBys = [];
        $this->aggregateDailyAverage = false;

        $result = $this->buildQuery()->max('m.submitted_at');

        $this->limit = $originalLimit;
        $this->orderBys = $originalOrderBys;
        $this->aggregateDailyAverage = $originalAggregate;

        return $result;
    }
}
