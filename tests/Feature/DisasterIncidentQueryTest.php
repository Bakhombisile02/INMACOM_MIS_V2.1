<?php

namespace Tests\Feature;

use App\Models\DisasterIncident;
use App\Models\ManagementArea;
use App\Models\Station;
use App\Models\User;
use App\Queries\DisasterIncidentQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DisasterIncidentQueryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('hazard_types')->insert([
            [
                'id' => (string) Str::uuid(),
                'code' => 'flood',
                'name' => 'Flood',
            ],
            [
                'id' => (string) Str::uuid(),
                'code' => 'drought',
                'name' => 'Drought',
            ],
        ]);
    }

    public function test_disaster_incident_query_filters_active(): void
    {
        $user = User::factory()->create();
        $area = ManagementArea::create([
            'code' => 'MA-001',
            'name' => 'Area 1',
            'basin' => 'Incomati',
            'country' => 'Mozambique',
            'is_active' => true,
        ]);

        // Clean table first to avoid interference
        DB::table('disaster_incidents')->delete();

        // 1. Active Incident
        DisasterIncident::create([
            'reference' => 'INC-001',
            'hazard_code' => 'flood',
            'title' => 'Flood Incident',
            'incident_status' => 'active',
            'reported_at' => now(),
            'submitted_by_id' => $user->id,
            'submitted_at' => now(),
            'review_status' => 'approved',
            'area_id' => $area->id,
            'resolved_at' => null,
        ]);

        // 2. Resolved Incident
        DisasterIncident::create([
            'reference' => 'INC-002',
            'hazard_code' => 'drought',
            'title' => 'Drought Incident',
            'incident_status' => 'resolved',
            'reported_at' => now()->subDays(5),
            'submitted_by_id' => $user->id,
            'submitted_at' => now()->subDays(5),
            'review_status' => 'approved',
            'area_id' => $area->id,
            'resolved_at' => now(),
        ]);

        $activeIncidents = DisasterIncidentQuery::query()->activeOnly()->get();
        $this->assertCount(1, $activeIncidents);
        $this->assertEquals('INC-001', $activeIncidents->first()->reference);
    }

    public function test_disaster_incident_query_recent_limits_correctly(): void
    {
        $user = User::factory()->create();

        DB::table('disaster_incidents')->delete();

        for ($i = 1; $i <= 5; $i++) {
            DisasterIncident::create([
                'reference' => "INC-00{$i}",
                'hazard_code' => 'flood',
                'title' => "Incident {$i}",
                'incident_status' => 'active',
                'reported_at' => now()->subMinutes($i * 10),
                'submitted_by_id' => $user->id,
                'submitted_at' => now(),
                'review_status' => 'approved',
            ]);
        }

        $recent = DisasterIncidentQuery::query()->recent(3)->get();
        $this->assertCount(3, $recent);
        // Assert ordering: desc of reported_at (INC-001 is most recent since it was subtracted by 10m vs 20m)
        $this->assertEquals('INC-001', $recent->first()->reference);
    }

    public function test_disaster_incident_query_gets_incident_stations(): void
    {
        $user = User::factory()->create();
        $station = Station::create([
            'code' => 'ST-001',
            'name' => 'Station 1',
            'latitude' => -25.5,
            'longitude' => 32.5,
            'category' => 'flow',
            'water_source' => 'river',
            'water_body_type' => 'river',
            'is_active' => true,
            'is_real_time' => false,
        ]);

        DB::table('disaster_incidents')->delete();

        $incident = DisasterIncident::create([
            'reference' => 'INC-999',
            'hazard_code' => 'flood',
            'title' => 'Flood Station Test',
            'incident_status' => 'active',
            'reported_at' => now(),
            'submitted_by_id' => $user->id,
            'submitted_at' => now(),
            'review_status' => 'approved',
            'resolved_at' => null, // Active
        ]);

        DB::table('incident_stations')->insert([
            'incident_id' => $incident->id,
            'station_id' => $station->id,
            'role' => 'monitoring',
        ]);

        $stations = DisasterIncidentQuery::getIncidentStations();
        $this->assertCount(1, $stations);
        $this->assertEquals($station->id, $stations[0]->station_id);
        $this->assertEquals('monitoring', $stations[0]->role);
    }
}
