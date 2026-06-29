<?php

namespace Tests\Feature;

use App\Models\HazardStatusCurrent;
use App\Models\ManagementArea;
use App\Models\Measurement;
use App\Models\Station;
use App\Models\User;
use App\Models\WaterQualityParameter;
use App\Queries\HazardStatusQuery;
use App\Queries\StationMeasurementQuery;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeepenedQueriesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_station_measurement_query_builder(): void
    {
        // 1. Total count matches raw query
        $totalRaw = DB::table('measurements')->count();
        $totalQuery = StationMeasurementQuery::query()->count();
        $this->assertEquals($totalRaw, $totalQuery);

        // 2. Filter by type
        $flowRaw = DB::table('measurements')->where('measurement_type', 'flow')->count();
        $flowQuery = StationMeasurementQuery::query()->forTypes('flow')->count();
        $this->assertEquals($flowRaw, $flowQuery);

        // 3. Filter by status
        $approvedRaw = DB::table('measurements')->where('status', 'approved')->count();
        $approvedQuery = StationMeasurementQuery::query()->withStatuses('approved')->count();
        $this->assertEquals($approvedRaw, $approvedQuery);

        // 4. Latest per station
        $latestCount = StationMeasurementQuery::query()
            ->forTypes('flow')
            ->withStatuses('approved')
            ->latestPerStation()
            ->get()
            ->count();

        $rawLatestCount = DB::table('measurements as m1')
            ->where('m1.status', 'approved')
            ->where('m1.measurement_type', 'flow')
            ->whereRaw("m1.date = (SELECT MAX(m2.date) FROM measurements as m2 WHERE m2.station_id = m1.station_id AND m2.measurement_type = m1.measurement_type AND m2.status = 'approved')")
            ->count();

        $this->assertEquals($rawLatestCount, $latestCount);
    }

    public function test_hazard_status_query_builder(): void
    {
        // 1. Total count
        $rawCount = DB::table('hazard_status_current')->count();
        $queryCount = HazardStatusQuery::query()->count();
        $this->assertEquals($rawCount, $queryCount);

        // 2. Filter by severity level (Watch or above)
        $watchOrAboveRaw = DB::table('hazard_status_current as hsc')
            ->join('hazard_status_levels as hsl', function ($join) {
                $join->on('hsc.hazard_code', '=', 'hsl.hazard_code')
                    ->on('hsc.level_code', '=', 'hsl.level_code');
            })
            ->where('hsl.severity', '>=', 2)
            ->count();

        $watchOrAboveQuery = HazardStatusQuery::query()
            ->withSeverityAtLeast(2)
            ->count();

        $this->assertEquals($watchOrAboveRaw, $watchOrAboveQuery);

        // 3. Count distinct areas
        $distinctAreasRaw = DB::table('hazard_status_current as hsc')
            ->join('hazard_status_levels as hsl', function ($join) {
                $join->on('hsc.hazard_code', '=', 'hsl.hazard_code')
                    ->on('hsc.level_code', '=', 'hsl.level_code');
            })
            ->where('hsl.severity', '>=', 2)
            ->distinct()
            ->count('hsc.area_id');

        $distinctAreasQuery = HazardStatusQuery::query()
            ->withSeverityAtLeast(2)
            ->countDistinctAreas();

        $this->assertEquals($distinctAreasRaw, $distinctAreasQuery);
    }

    public function test_measurement_parameter_relationship(): void
    {
        // Ensure there is a station
        $station = Station::first();
        if (! $station) {
            $station = Station::create([
                'code' => 'ST-01',
                'name' => 'Station 1',
                'country' => 'Mozambique',
                'river_basin' => 'Incomati',
                'is_active' => true,
            ]);
        }

        // Ensure there is a parameter
        $parameter = WaterQualityParameter::first();
        if (! $parameter) {
            $parameter = WaterQualityParameter::create([
                'code' => 'WQ-01',
                'name' => 'pH',
                'default_unit' => 'pH',
                'display_order' => 1,
                'is_active' => true,
                'is_priority_pollutant' => true,
            ]);
        }

        // Ensure there is a measurement linked to the parameter
        $measurement = Measurement::where('parameter_id', $parameter->id)->first();
        if (! $measurement) {
            $user = User::first();
            $this->assertNotNull($user, 'Expected seeded user for measurement creation.');
            $measurement = Measurement::create([
                'station_id' => $station->id,
                'measurement_type' => 'water_quality',
                'parameter_id' => $parameter->id,
                'value' => 7.5,
                'unit' => 'pH',
                'date' => now(),
                'status' => 'approved',
                'submitted_by_id' => $user->id,
                'submitted_at' => now(),
            ]);
        }

        $retrievedParameter = $measurement->parameter;
        $this->assertInstanceOf(WaterQualityParameter::class, $retrievedParameter);
        $this->assertEquals($parameter->id, $retrievedParameter->id);
    }

    public function test_hazard_status_area_relationship(): void
    {
        // Ensure there is a management area
        $area = ManagementArea::first();
        if (! $area) {
            $area = ManagementArea::create([
                'code' => 'MA-01',
                'name' => 'Area 1',
                'type' => 'subcatchment',
                'country' => 'South Africa',
            ]);
        }

        // Ensure there is a hazard record linked to the area
        $hazard = HazardStatusCurrent::where('area_id', $area->id)->first();
        if (! $hazard) {
            // Ensure there is a hazard type
            $hazardTypeId = DB::table('hazard_types')->where('code', 'drought')->value('id');
            if (! $hazardTypeId) {
                $hazardTypeId = (string) Str::uuid();
                DB::table('hazard_types')->insert([
                    'id' => $hazardTypeId,
                    'code' => 'drought',
                    'name' => 'Drought',
                    'description' => 'Drought Hazard',
                ]);
            }

            // Ensure there is a hazard status level
            $levelExists = DB::table('hazard_status_levels')
                ->where('hazard_code', 'drought')
                ->where('level_code', 'watch')
                ->exists();
            if (! $levelExists) {
                DB::table('hazard_status_levels')->insert([
                    'hazard_code' => 'drought',
                    'level_code' => 'watch',
                    'name' => 'Watch',
                    'severity' => 2,
                ]);
            }

            $hazard = HazardStatusCurrent::create([
                'hazard_code' => 'drought',
                'area_id' => $area->id,
                'level_code' => 'watch',
                'calculated_at' => now(),
                'score' => 1.5,
            ]);
        }

        $retrievedArea = $hazard->area;
        $this->assertInstanceOf(ManagementArea::class, $retrievedArea);
        $this->assertEquals($area->id, $retrievedArea->id);
    }

    public function test_gis_controller_historical_data_date_validation(): void
    {
        $user = User::first();
        $this->assertNotNull($user, 'Expected seeded user.');

        $stationId = DB::table('stations')->value('id');
        $this->assertNotNull($stationId, 'Expected seeded station.');

        // 1. Invalid date parameter returns 400
        $response = $this->actingAs($user)->getJson(route('stations.historical-data', [
            'id' => $stationId,
            'from' => 'invalid-date-string',
        ]));
        $response->assertStatus(400);

        // 2. Valid date parameters work
        $response2 = $this->actingAs($user)->getJson(route('stations.historical-data', [
            'id' => $stationId,
            'from' => '2026-01-01',
            'to' => '2026-12-31',
        ]));
        $response2->assertOk();
    }
}
