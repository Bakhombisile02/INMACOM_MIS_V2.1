<?php

namespace Tests\Unit\Services;

use App\Models\Station;
use App\Models\StationRevision;
use App\Models\User;
use App\Services\StationRevisionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StationRevisionManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_propose_creates_pending_revision(): void
    {
        $submitter = User::factory()->create(['role' => User::ROLE_CLERK]);

        $proposedChanges = [
            'code' => 'TEST-001',
            'name' => 'Proposed Test Station',
            'category' => 'flow',
        ];

        $revision = StationRevisionManager::propose(
            $submitter,
            null,
            StationRevision::CHANGE_TYPE_CREATE,
            $proposedChanges
        );

        $this->assertDatabaseHas('station_revisions', [
            'id' => $revision->id,
            'submitted_by_id' => $submitter->id,
            'status' => 'pending',
            'change_type' => 'create',
        ]);
        $this->assertEquals($proposedChanges, $revision->proposed_changes);
    }

    public function test_approve_create_type_creates_station_and_sets_reviewer(): void
    {
        $submitter = User::factory()->create(['role' => User::ROLE_CLERK]);
        $reviewer = User::factory()->create(['role' => User::ROLE_MANAGER]);

        $proposedChanges = [
            'code' => 'CRE-999',
            'name' => 'Created Via Service',
            'latitude' => -26.5,
            'longitude' => 32.5,
            'category' => 'flow',
            'water_source' => 'river',
            'water_body_type' => 'river',
            'is_active' => true,
            'is_real_time' => false,
        ];

        $revision = StationRevision::create([
            'station_id' => null,
            'submitted_by_id' => $submitter->id,
            'status' => StationRevision::STATUS_PENDING,
            'change_type' => StationRevision::CHANGE_TYPE_CREATE,
            'proposed_changes' => $proposedChanges,
        ]);

        StationRevisionManager::approve($revision, $reviewer, 'Looks good');

        $revision->refresh();
        $this->assertSame('approved', $revision->status);
        $this->assertSame($reviewer->id, $revision->reviewed_by_id);
        $this->assertSame('Looks good', $revision->review_notes);
        $this->assertFalse($revision->is_self_override);

        $this->assertDatabaseHas('stations', [
            'id' => $revision->station_id,
            'code' => 'CRE-999',
            'name' => 'Created Via Service',
        ]);
    }

    public function test_approve_delete_type_deletes_station(): void
    {
        $submitter = User::factory()->create(['role' => User::ROLE_CLERK]);
        $reviewer = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $station = Station::create([
            'code' => 'DEL-001',
            'name' => 'To Be Deleted',
            'category' => 'flow',
            'water_source' => 'river',
            'water_body_type' => 'river',
            'is_active' => true,
            'is_real_time' => false,
            'latitude' => 0.0,
            'longitude' => 0.0,
        ]);

        $revision = StationRevision::create([
            'station_id' => $station->id,
            'submitted_by_id' => $submitter->id,
            'status' => StationRevision::STATUS_PENDING,
            'change_type' => StationRevision::CHANGE_TYPE_DELETE,
            'proposed_changes' => [],
        ]);

        StationRevisionManager::approve($revision, $reviewer, 'Confirm deletion');

        $this->assertDatabaseMissing('stations', [
            'id' => $station->id,
        ]);

        $this->assertDatabaseMissing('station_revisions', [
            'id' => $revision->id,
        ]);
    }

    public function test_approve_update_type_applies_changes_and_sets_self_override(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $station = Station::create([
            'code' => 'UPD-001',
            'name' => 'Original Name',
            'category' => 'flow',
            'water_source' => 'river',
            'water_body_type' => 'river',
            'is_active' => true,
            'is_real_time' => false,
            'latitude' => 0.0,
            'longitude' => 0.0,
        ]);

        $revision = StationRevision::create([
            'station_id' => $station->id,
            'submitted_by_id' => $manager->id,
            'status' => StationRevision::STATUS_PENDING,
            'change_type' => StationRevision::CHANGE_TYPE_UPDATE,
            'proposed_changes' => [
                'name' => ['from' => 'Original Name', 'to' => 'Updated Name'],
            ],
        ]);

        StationRevisionManager::approve($revision, $manager, 'Self overriding');

        $revision->refresh();
        $station->refresh();

        $this->assertSame('approved', $revision->status);
        $this->assertSame($manager->id, $revision->reviewed_by_id);
        $this->assertTrue($revision->is_self_override);
        $this->assertSame('Updated Name', $station->name);
    }

    public function test_reject_sets_status_and_reviewer(): void
    {
        $submitter = User::factory()->create(['role' => User::ROLE_CLERK]);
        $reviewer = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $station = Station::create([
            'code' => 'REJ-001',
            'name' => 'Station Name',
            'category' => 'flow',
            'water_source' => 'river',
            'water_body_type' => 'river',
            'is_active' => true,
            'is_real_time' => false,
            'latitude' => 0.0,
            'longitude' => 0.0,
        ]);

        $revision = StationRevision::create([
            'station_id' => $station->id,
            'submitted_by_id' => $submitter->id,
            'status' => StationRevision::STATUS_PENDING,
            'change_type' => StationRevision::CHANGE_TYPE_UPDATE,
            'proposed_changes' => [
                'name' => ['from' => 'Station Name', 'to' => 'Rejected Proposal'],
            ],
        ]);

        StationRevisionManager::reject($revision, $reviewer, 'Not needed');

        $revision->refresh();
        $station->refresh();

        $this->assertSame('rejected', $revision->status);
        $this->assertSame($reviewer->id, $revision->reviewed_by_id);
        $this->assertSame('Not needed', $revision->review_notes);
        $this->assertSame('Station Name', $station->name);
    }

    public function test_reviewed_revision_throws_runtime_exception_on_approve_or_reject(): void
    {
        $submitter = User::factory()->create(['role' => User::ROLE_CLERK]);
        $reviewer = User::factory()->create(['role' => User::ROLE_MANAGER]);

        $revision = StationRevision::create([
            'station_id' => null,
            'submitted_by_id' => $submitter->id,
            'status' => StationRevision::STATUS_APPROVED,
            'change_type' => StationRevision::CHANGE_TYPE_CREATE,
            'proposed_changes' => [],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Revision has already been reviewed.');

        StationRevisionManager::approve($revision, $reviewer);
    }

    public function test_approve_create_with_empty_proposed_changes_throws_exception(): void
    {
        $submitter = User::factory()->create(['role' => User::ROLE_CLERK]);
        $reviewer = User::factory()->create(['role' => User::ROLE_MANAGER]);

        $revision = StationRevision::create([
            'station_id' => null,
            'submitted_by_id' => $submitter->id,
            'status' => StationRevision::STATUS_PENDING,
            'change_type' => StationRevision::CHANGE_TYPE_CREATE,
            'proposed_changes' => [],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot create station: proposed_changes is empty.');

        StationRevisionManager::approve($revision, $reviewer);
    }

    public function test_approve_with_invalid_data_throws_validation_exception(): void
    {
        $submitter = User::factory()->create(['role' => User::ROLE_CLERK]);
        $reviewer = User::factory()->create(['role' => User::ROLE_MANAGER]);

        $revision = StationRevision::create([
            'station_id' => null,
            'submitted_by_id' => $submitter->id,
            'status' => StationRevision::STATUS_PENDING,
            'change_type' => StationRevision::CHANGE_TYPE_CREATE,
            // Invalid data: missing name, latitude, etc.
            'proposed_changes' => [
                'code' => 'INVALID-STATION',
            ],
        ]);

        $this->expectException(ValidationException::class);

        StationRevisionManager::approve($revision, $reviewer);
    }
}
