<?php

namespace Tests\Feature;

use App\Models\Station;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class MeasurementsCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed the entire database reference system (including stations, parameters, and management areas)
        $this->seed(DatabaseSeeder::class);
    }

    public function test_guest_is_redirected_to_login_for_gis_pages(): void
    {
        $this->get('/flow-levels')->assertRedirect(route('login'));
        $this->get('/dam-levels')->assertRedirect(route('login'));
        $this->get('/water-quality')->assertRedirect(route('login'));
        $this->get('/rainfall')->assertRedirect(route('login'));
        $this->get('/groundwater')->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_gis_pages(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CLERK,
        ]);

        $this->actingAs($user)->get('/flow-levels')->assertOk();
        $this->actingAs($user)->get('/dam-levels')->assertOk();
        $this->actingAs($user)->get('/water-quality')->assertOk();
        $this->actingAs($user)->get('/rainfall')->assertOk();
        $this->actingAs($user)->get('/groundwater')->assertOk();
    }

    public function test_clerk_can_submit_measurement_and_it_is_pending(): void
    {
        $clerk = User::factory()->create([
            'role' => User::ROLE_CLERK,
        ]);

        $station = Station::first();
        $this->assertNotNull($station);

        $payload = [
            'station_id' => $station->id,
            'measurement_type' => 'flow',
            'value' => 15.6,
            'unit' => 'm³/s',
            'date' => '2026-05-25',
        ];

        $response = $this->actingAs($clerk)
            ->post(route('measurements.store'), $payload);

        $response->assertRedirect();

        // Assert record exists in pending status
        $this->assertDatabaseHas('measurements', [
            'station_id' => $station->id,
            'measurement_type' => 'flow',
            'value' => 15.6,
            'unit' => 'm³/s',
            'status' => 'pending',
            'submitted_by_id' => $clerk->id,
            'is_self_override' => false,
        ]);
    }

    public function test_clerk_cannot_approve_measurement(): void
    {
        $clerk = User::factory()->create([
            'role' => User::ROLE_CLERK,
        ]);

        $station = Station::first();
        $measurementId = (string) Str::uuid();

        DB::table('measurements')->insert([
            'id' => $measurementId,
            'station_id' => $station->id,
            'measurement_type' => 'flow',
            'value' => 12.0,
            'unit' => 'm³/s',
            'date' => '2026-05-25',
            'status' => 'pending',
            'submitted_by_id' => $clerk->id,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($clerk)
            ->post(route('measurements.approve', $measurementId));

        $response->assertStatus(403);
    }

    public function test_manager_can_approve_measurement(): void
    {
        $clerk = User::factory()->create([
            'role' => User::ROLE_CLERK,
        ]);
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $station = Station::first();
        $measurementId = (string) Str::uuid();

        DB::table('measurements')->insert([
            'id' => $measurementId,
            'station_id' => $station->id,
            'measurement_type' => 'flow',
            'value' => 20.4,
            'unit' => 'm³/s',
            'date' => '2026-05-25',
            'status' => 'pending',
            'submitted_by_id' => $clerk->id,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($manager)
            ->post(route('measurements.approve', $measurementId));

        $response->assertRedirect();

        // Assert measurement status was updated to approved
        $this->assertDatabaseHas('measurements', [
            'id' => $measurementId,
            'status' => 'approved',
            'reviewed_by_id' => $manager->id,
            'review_notes' => 'Approved by Data Manager',
        ]);
    }

    public function test_manager_can_reject_measurement_with_notes(): void
    {
        $clerk = User::factory()->create([
            'role' => User::ROLE_CLERK,
        ]);
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $station = Station::first();
        $measurementId = (string) Str::uuid();

        DB::table('measurements')->insert([
            'id' => $measurementId,
            'station_id' => $station->id,
            'measurement_type' => 'flow',
            'value' => 2000.4, // unrealistic value
            'unit' => 'm³/s',
            'date' => '2026-05-25',
            'status' => 'pending',
            'submitted_by_id' => $clerk->id,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($manager)
            ->post(route('measurements.reject', $measurementId), [
                'review_notes' => 'Value seems mathematically impossible, please verify gauge calibration.',
            ]);

        $response->assertRedirect();

        // Assert measurement status was updated to rejected with correct review notes
        $this->assertDatabaseHas('measurements', [
            'id' => $measurementId,
            'status' => 'rejected',
            'reviewed_by_id' => $manager->id,
            'review_notes' => 'Value seems mathematically impossible, please verify gauge calibration.',
        ]);
    }

    public function test_manager_or_admin_submission_is_auto_approved(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $station = Station::first();

        $payload = [
            'station_id' => $station->id,
            'measurement_type' => 'flow',
            'value' => 35.8,
            'unit' => 'm³/s',
            'date' => '2026-05-25',
        ];

        $response = $this->actingAs($manager)
            ->post(route('measurements.store'), $payload);

        $response->assertRedirect();

        // Assert record is created with auto-approved status (is_self_override = true)
        $this->assertDatabaseHas('measurements', [
            'station_id' => $station->id,
            'measurement_type' => 'flow',
            'value' => 35.8,
            'status' => 'approved',
            'submitted_by_id' => $manager->id,
            'reviewed_by_id' => $manager->id,
            'is_self_override' => true,
        ]);
    }

    public function test_clerk_editing_reverts_status_to_pending(): void
    {
        $clerk = User::factory()->create([
            'role' => User::ROLE_CLERK,
        ]);

        $station = Station::first();
        $measurementId = (string) Str::uuid();

        // Insert as an already approved/rejected measurement
        DB::table('measurements')->insert([
            'id' => $measurementId,
            'station_id' => $station->id,
            'measurement_type' => 'flow',
            'value' => 45.0,
            'unit' => 'm³/s',
            'date' => '2026-05-25',
            'status' => 'rejected',
            'submitted_by_id' => $clerk->id,
            'submitted_at' => now(),
            'review_notes' => 'Old notes',
        ]);

        // Clerk edits the measurement
        $response = $this->actingAs($clerk)
            ->patch(route('measurements.update', $measurementId), [
                'value' => 25.0, // corrected value
                'unit' => 'm³/s',
                'date' => '2026-05-25',
            ]);

        $response->assertRedirect();

        // Assert record value is updated and status is reverted to pending
        $this->assertDatabaseHas('measurements', [
            'id' => $measurementId,
            'value' => 25.0,
            'status' => 'pending',
            'reviewed_by_id' => null,
            'reviewed_at' => null,
            'review_notes' => null,
            'is_self_override' => false,
        ]);
    }

    public function test_manager_can_delete_measurement(): void
    {
        $clerk = User::factory()->create([
            'role' => User::ROLE_CLERK,
        ]);
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $station = Station::first();
        $measurementId = (string) Str::uuid();

        DB::table('measurements')->insert([
            'id' => $measurementId,
            'station_id' => $station->id,
            'measurement_type' => 'flow',
            'value' => 10.0,
            'unit' => 'm³/s',
            'date' => '2026-05-25',
            'status' => 'pending',
            'submitted_by_id' => $clerk->id,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($manager)
            ->delete(route('measurements.destroy', $measurementId));

        $response->assertRedirect();

        // Assert record is deleted
        $this->assertDatabaseMissing('measurements', [
            'id' => $measurementId,
        ]);
    }

    public function test_clerk_cannot_delete_measurement(): void
    {
        $clerk = User::factory()->create([
            'role' => User::ROLE_CLERK,
        ]);

        $station = Station::first();
        $measurementId = (string) Str::uuid();

        DB::table('measurements')->insert([
            'id' => $measurementId,
            'station_id' => $station->id,
            'measurement_type' => 'flow',
            'value' => 10.0,
            'unit' => 'm³/s',
            'date' => '2026-05-25',
            'status' => 'pending',
            'submitted_by_id' => $clerk->id,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($clerk)
            ->delete(route('measurements.destroy', $measurementId));

        $response->assertStatus(403);
    }
}
