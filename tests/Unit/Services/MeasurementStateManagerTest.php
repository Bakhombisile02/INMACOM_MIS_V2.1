<?php

namespace Tests\Unit\Services;

use App\Models\Measurement;
use App\Models\Station;
use App\Models\User;
use App\Services\AuditService;
use App\Services\MeasurementStateManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MeasurementStateManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_pending_measurement_for_clerk(): void
    {
        $clerk = User::factory()->create(['role' => User::ROLE_CLERK]);
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

        $measurement = MeasurementStateManager::store($clerk, [
            'station_id' => $station->id,
            'measurement_type' => 'flow',
            'value' => 12.34,
            'unit' => 'm3/s',
            'date' => '2026-06-12',
        ]);

        $this->assertDatabaseHas('measurements', [
            'id' => $measurement->id,
            'status' => 'pending',
            'value' => 12.34,
            'submitted_by_id' => $clerk->id,
        ]);
    }

    public function test_store_creates_approved_measurement_for_manager(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $station = Station::create([
            'code' => 'ST-002',
            'name' => 'Station 2',
            'latitude' => -25.5,
            'longitude' => 32.5,
            'category' => 'flow',
            'water_source' => 'river',
            'water_body_type' => 'river',
            'is_active' => true,
            'is_real_time' => false,
        ]);

        $measurement = MeasurementStateManager::store($manager, [
            'station_id' => $station->id,
            'measurement_type' => 'flow',
            'value' => 45.67,
            'unit' => 'm3/s',
            'date' => '2026-06-12',
        ]);

        $this->assertDatabaseHas('measurements', [
            'id' => $measurement->id,
            'status' => 'approved',
            'is_self_override' => true,
            'reviewed_by_id' => $manager->id,
        ]);
    }

    public function test_update_modifies_data(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $station = Station::create([
            'code' => 'ST-003',
            'name' => 'Station 3',
            'latitude' => -25.5,
            'longitude' => 32.5,
            'category' => 'flow',
            'water_source' => 'river',
            'water_body_type' => 'river',
            'is_active' => true,
            'is_real_time' => false,
        ]);

        $measurement = Measurement::create([
            'station_id' => $station->id,
            'measurement_type' => 'flow',
            'value' => 10.0,
            'unit' => 'm3/s',
            'date' => '2026-06-12',
            'status' => 'pending',
            'submitted_by_id' => $manager->id,
            'submitted_at' => now(),
        ]);

        MeasurementStateManager::update($manager, $measurement->id, [
            'value' => 25.5,
            'unit' => 'm3/s',
            'date' => '2026-06-12',
        ]);

        $measurement->refresh();
        $this->assertEquals(25.5, $measurement->value);
    }

    public function test_update_reverts_status_to_pending_for_clerk(): void
    {
        $clerk = User::factory()->create(['role' => User::ROLE_CLERK]);
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $station = Station::create([
            'code' => 'ST-004',
            'name' => 'Station 4',
            'latitude' => -25.5,
            'longitude' => 32.5,
            'category' => 'flow',
            'water_source' => 'river',
            'water_body_type' => 'river',
            'is_active' => true,
            'is_real_time' => false,
        ]);

        $measurement = Measurement::create([
            'station_id' => $station->id,
            'measurement_type' => 'flow',
            'value' => 10.0,
            'unit' => 'm3/s',
            'date' => '2026-06-12',
            'status' => 'approved',
            'submitted_by_id' => $clerk->id,
            'submitted_at' => now(),
            'reviewed_by_id' => $manager->id,
        ]);

        MeasurementStateManager::update($clerk, $measurement->id, [
            'value' => 15.0,
            'unit' => 'm3/s',
            'date' => '2026-06-12',
        ]);

        $measurement->refresh();
        $this->assertEquals('pending', $measurement->status);
        $this->assertNull($measurement->reviewed_by_id);
    }

    public function test_delete_removes_measurement(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $station = Station::create([
            'code' => 'ST-005',
            'name' => 'Station 5',
            'latitude' => -25.5,
            'longitude' => 32.5,
            'category' => 'flow',
            'water_source' => 'river',
            'water_body_type' => 'river',
            'is_active' => true,
            'is_real_time' => false,
        ]);

        $measurement = Measurement::create([
            'station_id' => $station->id,
            'measurement_type' => 'flow',
            'value' => 10.0,
            'unit' => 'm3/s',
            'date' => '2026-06-12',
            'status' => 'pending',
            'submitted_by_id' => $manager->id,
            'submitted_at' => now(),
        ]);

        MeasurementStateManager::delete($manager, $measurement->id);

        $this->assertDatabaseMissing('measurements', [
            'id' => $measurement->id,
        ]);
    }

    public function test_approve_sets_status_approved(): void
    {
        $clerk = User::factory()->create(['role' => User::ROLE_CLERK]);
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $station = Station::create([
            'code' => 'ST-006',
            'name' => 'Station 6',
            'latitude' => -25.5,
            'longitude' => 32.5,
            'category' => 'flow',
            'water_source' => 'river',
            'water_body_type' => 'river',
            'is_active' => true,
            'is_real_time' => false,
        ]);

        $measurement = Measurement::create([
            'station_id' => $station->id,
            'measurement_type' => 'flow',
            'value' => 10.0,
            'unit' => 'm3/s',
            'date' => '2026-06-12',
            'status' => 'pending',
            'submitted_by_id' => $clerk->id,
            'submitted_at' => now(),
        ]);

        MeasurementStateManager::approve($manager, $measurement->id, 'Approved');

        $measurement->refresh();
        $this->assertEquals('approved', $measurement->status);
        $this->assertEquals($manager->id, $measurement->reviewed_by_id);
    }

    public function test_reject_sets_status_rejected(): void
    {
        $clerk = User::factory()->create(['role' => User::ROLE_CLERK]);
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $station = Station::create([
            'code' => 'ST-007',
            'name' => 'Station 7',
            'latitude' => -25.5,
            'longitude' => 32.5,
            'category' => 'flow',
            'water_source' => 'river',
            'water_body_type' => 'river',
            'is_active' => true,
            'is_real_time' => false,
        ]);

        $measurement = Measurement::create([
            'station_id' => $station->id,
            'measurement_type' => 'flow',
            'value' => 10.0,
            'unit' => 'm3/s',
            'date' => '2026-06-12',
            'status' => 'pending',
            'submitted_by_id' => $clerk->id,
            'submitted_at' => now(),
        ]);

        MeasurementStateManager::reject($manager, $measurement->id, 'Rejected due to out of range');

        $measurement->refresh();
        $this->assertEquals('rejected', $measurement->status);
        $this->assertEquals($manager->id, $measurement->reviewed_by_id);
        $this->assertEquals('Rejected due to out of range', $measurement->review_notes);
    }

    public function test_import_inserts_records_and_writes_audit_log(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $station = Station::create([
            'code' => 'ST-IMPORT',
            'name' => 'Import Station',
            'latitude' => -25.5,
            'longitude' => 32.5,
            'category' => 'flow',
            'water_source' => 'river',
            'water_body_type' => 'river',
            'is_active' => true,
            'is_real_time' => false,
        ]);

        $uuid = (string) Str::uuid();
        $inserts = [
            [
                'id' => $uuid,
                'station_id' => $station->id,
                'measurement_type' => 'flow',
                'value' => 22.5,
                'unit' => 'm3/s',
                'date' => '2026-06-12',
                'status' => 'approved',
                'submitted_by_id' => $manager->id,
                'submitted_at' => now(),
            ],
        ];

        $imported = MeasurementStateManager::import($manager, 'flow', $inserts);

        $this->assertEquals(1, $imported);
        $this->assertDatabaseHas('measurements', [
            'id' => $uuid,
            'value' => 22.5,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action_type' => AuditService::ACTION_MEASUREMENT_IMPORTED,
            'entity_id' => $uuid,
            'actor_id' => $manager->id,
        ]);
    }
}
