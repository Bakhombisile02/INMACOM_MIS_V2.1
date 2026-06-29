<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicExploreApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_guest_can_access_public_explore_page(): void
    {
        $this->get(route('explore'))->assertOk();
    }

    public function test_guest_can_fetch_public_station_historical_data(): void
    {
        $sample = DB::table('measurements as m')
            ->join('stations as s', 's.id', '=', 'm.station_id')
            ->where('s.is_active', true)
            ->whereIn('m.status', ['approved', 'pending'])
            ->whereIn('m.measurement_type', ['dam_level', 'flow', 'rainfall', 'groundwater_level', 'water_quality'])
            ->select('s.id as station_id', 'm.measurement_type')
            ->first();

        $this->assertNotNull($sample, 'Expected seeded station measurement data for public historical endpoint test.');

        $response = $this->getJson(route('public.stations.historical-data', [
            'id' => $sample->station_id,
            'type' => $sample->measurement_type,
        ]));

        $response->assertOk()->assertJsonStructure([
            'station' => ['id', 'code', 'name'],
            'type',
            'readings' => [
                '*' => ['id', 'station_id', 'measurement_type', 'value', 'unit', 'date', 'status', 'confidence'],
            ],
        ]);

        $readings = collect($response->json('readings'));

        $this->assertNotEmpty($readings, 'Expected at least one historical reading for selected public station/type.');
        $this->assertTrue(
            $readings->every(fn (array $row): bool => $row['measurement_type'] === $sample->measurement_type),
            'All readings should match requested measurement type.'
        );
        $this->assertTrue(
            $readings->every(fn (array $row): bool => in_array($row['status'], ['approved', 'pending'], true)),
            'Public historical endpoint should expose only approved/pending statuses.'
        );
    }

    public function test_public_station_historical_data_rejects_invalid_type(): void
    {
        $stationId = DB::table('stations')->where('is_active', true)->value('id');

        $this->assertNotNull($stationId, 'Expected at least one active station in seeded dataset.');

        $this->getJson(route('public.stations.historical-data', [
            'id' => $stationId,
            'type' => 'invalid_type',
        ]))->assertStatus(422);
    }

    public function test_authenticated_historical_endpoint_stays_protected_for_guests(): void
    {
        $sample = DB::table('measurements as m')
            ->join('stations as s', 's.id', '=', 'm.station_id')
            ->where('s.is_active', true)
            ->whereIn('m.status', ['approved', 'pending'])
            ->whereIn('m.measurement_type', ['dam_level', 'flow', 'rainfall', 'groundwater_level', 'water_quality'])
            ->select('s.id as station_id', 'm.measurement_type')
            ->first();

        $this->assertNotNull($sample, 'Expected seeded station measurement data for auth-protected endpoint test.');

        $this->get(route('stations.historical-data', [
            'id' => $sample->station_id,
            'type' => $sample->measurement_type,
        ]))->assertRedirect(route('login'));
    }
}
