<?php

namespace Tests\Feature\Approvals;

use App\Models\Station;
use App\Models\StationRevision;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StationRevisionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_clerk_update_creates_pending_revision_instead_of_applying(): void
    {
        $clerk = User::factory()->create(['role' => User::ROLE_CLERK]);
        $station = Station::first();
        $originalName = $station->name;

        $this->actingAs($clerk)
            ->patch(route('stations.update', $station), [
                'code' => $station->code,
                'name' => 'Proposed New Name',
                'latitude' => $station->latitude ?? 0,
                'longitude' => $station->longitude ?? 0,
                'category' => $station->category,
                'water_source' => $station->water_source,
                'water_body_type' => $station->water_body_type,
                'is_active' => (bool) $station->is_active,
                'is_real_time' => (bool) $station->is_real_time,
            ])
            ->assertRedirect();

        $station->refresh();
        $this->assertSame($originalName, $station->name);
        $this->assertDatabaseHas('station_revisions', [
            'station_id' => $station->id,
            'submitted_by_id' => $clerk->id,
            'status' => 'pending',
        ]);
    }

    public function test_manager_can_approve_revision_and_changes_apply(): void
    {
        $clerk = User::factory()->create(['role' => User::ROLE_CLERK]);
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $station = Station::first();

        $revision = StationRevision::create([
            'station_id' => $station->id,
            'submitted_by_id' => $clerk->id,
            'status' => StationRevision::STATUS_PENDING,
            'proposed_changes' => [
                'name' => ['from' => $station->name, 'to' => 'Approved Name'],
            ],
        ]);

        $this->actingAs($manager)
            ->post(route('station-revisions.approve', $revision))
            ->assertRedirect();

        $station->refresh();
        $revision->refresh();
        $this->assertSame('Approved Name', $station->name);
        $this->assertSame('approved', $revision->status);
        $this->assertSame($manager->id, $revision->reviewed_by_id);
    }

    public function test_manager_can_reject_revision_with_notes(): void
    {
        $clerk = User::factory()->create(['role' => User::ROLE_CLERK]);
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $station = Station::first();
        $originalName = $station->name;

        $revision = StationRevision::create([
            'station_id' => $station->id,
            'submitted_by_id' => $clerk->id,
            'status' => StationRevision::STATUS_PENDING,
            'proposed_changes' => [
                'name' => ['from' => $originalName, 'to' => 'Rejected Name'],
            ],
        ]);

        $this->actingAs($manager)
            ->post(route('station-revisions.reject', $revision), [
                'review_notes' => 'Insufficient justification',
            ])
            ->assertRedirect();

        $station->refresh();
        $revision->refresh();
        $this->assertSame($originalName, $station->name);
        $this->assertSame('rejected', $revision->status);
        $this->assertSame('Insufficient justification', $revision->review_notes);
    }

    public function test_clerk_cannot_approve_or_reject_revision(): void
    {
        $clerk = User::factory()->create(['role' => User::ROLE_CLERK]);
        $other = User::factory()->create(['role' => User::ROLE_CLERK]);
        $station = Station::first();

        $revision = StationRevision::create([
            'station_id' => $station->id,
            'submitted_by_id' => $clerk->id,
            'status' => StationRevision::STATUS_PENDING,
            'proposed_changes' => ['name' => ['from' => $station->name, 'to' => 'X']],
        ]);

        $this->actingAs($other)
            ->post(route('station-revisions.approve', $revision))
            ->assertForbidden();

        $this->actingAs($other)
            ->post(route('station-revisions.reject', $revision), ['review_notes' => 'no'])
            ->assertForbidden();
    }

    public function test_manager_create_station_routes_through_approvals_queue(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);

        $this->actingAs($manager)
            ->post(route('stations.store'), [
                'code' => 'NEW-001',
                'name' => 'Proposed New Station',
                'latitude' => -26.0,
                'longitude' => 32.0,
                'category' => 'flow',
                'water_source' => 'river',
                'water_body_type' => 'river',
                'is_active' => true,
                'is_real_time' => false,
            ])->assertRedirect();

        $this->assertDatabaseMissing('stations', ['code' => 'NEW-001']);
        $this->assertDatabaseHas('station_revisions', [
            'submitted_by_id' => $manager->id,
            'change_type' => 'create',
            'status' => 'pending',
        ]);
    }

    public function test_manager_update_station_routes_through_approvals_queue(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $station = Station::first();
        $originalName = $station->name;

        $this->actingAs($manager)
            ->patch(route('stations.update', $station), [
                'code' => $station->code,
                'name' => 'Manager Proposed Name',
                'latitude' => $station->latitude ?? 0,
                'longitude' => $station->longitude ?? 0,
                'category' => $station->category,
                'water_source' => $station->water_source,
                'water_body_type' => $station->water_body_type,
                'is_active' => (bool) $station->is_active,
                'is_real_time' => (bool) $station->is_real_time,
            ])->assertRedirect();

        // Station unchanged until approval.
        $station->refresh();
        $this->assertSame($originalName, $station->name);
        $this->assertDatabaseHas('station_revisions', [
            'station_id' => $station->id,
            'submitted_by_id' => $manager->id,
            'change_type' => 'update',
            'status' => 'pending',
        ]);
    }

    public function test_self_approval_is_allowed_and_logged_as_override(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $station = Station::first();

        $revision = StationRevision::create([
            'station_id' => $station->id,
            'submitted_by_id' => $manager->id,
            'status' => StationRevision::STATUS_PENDING,
            'change_type' => StationRevision::CHANGE_TYPE_UPDATE,
            'proposed_changes' => [
                'name' => ['from' => $station->name, 'to' => 'Self Approved Name'],
            ],
        ]);

        $this->actingAs($manager)
            ->post(route('station-revisions.approve', $revision))
            ->assertRedirect();

        $station->refresh();
        $revision->refresh();
        $this->assertSame('Self Approved Name', $station->name);
        $this->assertSame('approved', $revision->status);
        $this->assertSame($manager->id, $revision->reviewed_by_id);
        $this->assertTrue($revision->is_self_override);
    }

    public function test_approving_a_create_revision_creates_the_station(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $revision = StationRevision::create([
            'station_id' => null,
            'submitted_by_id' => $manager->id,
            'status' => StationRevision::STATUS_PENDING,
            'change_type' => StationRevision::CHANGE_TYPE_CREATE,
            'proposed_changes' => [
                'code' => 'CRE-001',
                'name' => 'Created Via Approval',
                'latitude' => -25.0,
                'longitude' => 31.0,
                'category' => 'flow',
                'water_source' => 'river',
                'water_body_type' => 'river',
                'is_active' => true,
                'is_real_time' => false,
            ],
        ]);

        $this->actingAs($admin)
            ->post(route('station-revisions.approve', $revision))
            ->assertRedirect();

        $revision->refresh();
        $this->assertSame('approved', $revision->status);
        $this->assertFalse($revision->is_self_override);
        $this->assertDatabaseHas('stations', ['code' => 'CRE-001', 'name' => 'Created Via Approval']);
        $this->assertNotNull($revision->station_id);
    }
}
