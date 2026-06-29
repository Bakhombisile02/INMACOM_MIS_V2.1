<?php

namespace App\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HazardStatusQuery deep module.
 * Consolidates querying of active hazard levels, severity calculations, and disaster counts.
 * Maximises flexibility to support custom filters, flexible joins, and multiple types.
 */
class HazardStatusQuery
{
    protected array $hazardCodes = [];

    protected array $areaIds = [];

    protected ?int $minSeverity = null;

    protected bool $includeDetails = false;

    protected bool $includeActiveIncidentCounts = false;

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
     * Filter by hazard type code(s) (e.g. flood, drought, pollution_spill).
     */
    public function forHazards(string|array $hazardCodes): self
    {
        $this->hazardCodes = array_merge($this->hazardCodes, (array) $hazardCodes);

        return $this;
    }

    /**
     * Filter by management area ID(s).
     */
    public function forAreas(string|array|Collection $areaIds): self
    {
        if ($areaIds instanceof Collection) {
            $areaIds = $areaIds->toArray();
        }
        $this->areaIds = array_merge($this->areaIds, (array) $areaIds);

        return $this;
    }

    /**
     * Filter by severity level (e.g. at least 2 for Watch, at least 4 for Critical).
     */
    public function withSeverityAtLeast(int $severity): self
    {
        $this->minSeverity = $severity;

        return $this;
    }

    /**
     * Join hazard types, management areas, and status level details.
     */
    public function withDetails(): self
    {
        $this->includeDetails = true;

        return $this;
    }

    /**
     * Join the count of active disaster incidents per area and hazard.
     */
    public function withActiveIncidentCounts(): self
    {
        $this->includeActiveIncidentCounts = true;

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
     * Limit results.
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Build the underlying query builder.
     */
    protected function buildQuery(): Builder
    {
        $query = DB::table('hazard_status_current as hsc');

        // Select core columns
        $query->select([
            'hsc.id',
            'hsc.hazard_code',
            'hsc.area_id',
            'hsc.level_code',
            'hsc.score',
            'hsc.calculated_at',
            'hsc.calculation_notes',
        ]);

        // Apply filters
        if (! empty($this->hazardCodes)) {
            $query->whereIn('hsc.hazard_code', $this->hazardCodes);
        }

        if (! empty($this->areaIds)) {
            $query->whereIn('hsc.area_id', $this->areaIds);
        }

        // Apply joins
        if ($this->includeDetails || $this->minSeverity !== null) {
            // Join status levels for severity/color (needed for filtering severity as well)
            $query->join('hazard_status_levels as hsl', function ($join) {
                $join->on('hsc.hazard_code', '=', 'hsl.hazard_code')
                    ->on('hsc.level_code', '=', 'hsl.level_code');
            })->addSelect([
                'hsl.name as level_name',
                'hsl.severity as severity',
                'hsl.color as color',
                'hsl.description as level_description',
                'hsl.actions_required as actions_required',
            ]);
        }

        if ($this->includeDetails) {
            // Join management areas
            $query->join('management_areas as ma', 'hsc.area_id', '=', 'ma.id')
                ->addSelect([
                    'ma.name as area_name',
                    'ma.code as area_code',
                    'ma.country as country',
                    'ma.basin as basin',
                ]);

            // Join hazard types
            $query->leftJoin('hazard_types as ht', 'hsc.hazard_code', '=', 'ht.code')
                ->addSelect('ht.name as hazard_name');
        }

        if ($this->includeActiveIncidentCounts) {
            // Left join subquery of active incident counts
            $incidentsSub = DB::table('disaster_incidents')
                ->whereNull('resolved_at')
                ->selectRaw('area_id, hazard_code, COUNT(*) as active_count')
                ->groupBy('area_id', 'hazard_code');

            $query->leftJoinSub($incidentsSub, 'ai', function ($join) {
                $join->on('ai.area_id', '=', 'hsc.area_id')
                    ->on('ai.hazard_code', '=', 'hsc.hazard_code');
            })->addSelect(DB::raw('COALESCE(ai.active_count, 0) as active_incidents'));
        }

        // Apply severity filter
        if ($this->minSeverity !== null) {
            $query->where('hsl.severity', '>=', $this->minSeverity);
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
     * Execute the query and return the Collection.
     */
    public function get(): Collection
    {
        if (! Schema::hasTable('hazard_status_current')) {
            return collect();
        }

        return $this->buildQuery()->get();
    }

    /**
     * Count the matching records.
     */
    public function count(): int
    {
        if (! Schema::hasTable('hazard_status_current')) {
            return 0;
        }
        $originalLimit = $this->limit;
        $originalOrderBys = $this->orderBys;

        $this->limit = null;
        $this->orderBys = [];

        $count = (int) $this->buildQuery()->count();

        $this->limit = $originalLimit;
        $this->orderBys = $originalOrderBys;

        return $count;
    }

    /**
     * Count distinct management areas matching the query filters.
     */
    public function countDistinctAreas(): int
    {
        if (! Schema::hasTable('hazard_status_current')) {
            return 0;
        }
        $originalLimit = $this->limit;
        $originalOrderBys = $this->orderBys;

        $this->limit = null;
        $this->orderBys = [];

        $count = (int) $this->buildQuery()->distinct()->count('hsc.area_id');

        $this->limit = $originalLimit;
        $this->orderBys = $originalOrderBys;

        return $count;
    }
}
