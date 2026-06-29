<?php

namespace App\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DisasterIncidentQuery deep module.
 * Consolidates querying of disaster incidents and active monitoring stations.
 */
class DisasterIncidentQuery
{
    protected bool $activeOnly = false;

    protected bool $includeDetails = false;

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
     * Filter by active incidents (unresolved).
     */
    public function activeOnly(): self
    {
        $this->activeOnly = true;

        return $this;
    }

    /**
     * Join hazard types and management areas.
     */
    public function withDetails(): self
    {
        $this->includeDetails = true;

        return $this;
    }

    /**
     * Order by reported_at desc and apply a limit.
     */
    public function recent(int $limit = 50): self
    {
        $this->orderBy('di.reported_at', 'desc');
        $this->limit = $limit;

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
        $query = DB::table('disaster_incidents as di');

        $query->select([
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
        ]);

        if ($this->activeOnly) {
            $query->whereNull('di.resolved_at');
        }

        if ($this->includeDetails) {
            $query->leftJoin('hazard_types as ht', 'di.hazard_code', '=', 'ht.code')
                ->leftJoin('management_areas as ma', 'di.area_id', '=', 'ma.id')
                ->addSelect([
                    'ht.name as hazard_name',
                    'ma.name as area_name',
                ]);
        }

        foreach ($this->orderBys as $order) {
            $query->orderBy($order['column'], $order['direction']);
        }

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
        if (! Schema::hasTable('disaster_incidents')) {
            return collect();
        }

        return $this->buildQuery()->get();
    }

    /**
     * Retrieve list of stations associated with active incidents.
     */
    public static function getIncidentStations(): array
    {
        if (! Schema::hasTable('incident_stations')) {
            return [];
        }

        return DB::table('incident_stations as ins')
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
    }
}
