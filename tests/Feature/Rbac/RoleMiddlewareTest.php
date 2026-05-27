<?php

namespace Tests\Feature\Rbac;

use App\Models\Station;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_clerk_forbidden_from_admin_only_user_update(): void
    {
        $clerk = User::factory()->create(['role' => User::ROLE_CLERK]);
        $target = User::factory()->create(['role' => User::ROLE_CLERK]);

        $this->actingAs($clerk)
            ->patch(route('users.update', $target), ['role' => User::ROLE_MANAGER])
            ->assertForbidden();
    }

    public function test_clerk_forbidden_from_creating_station(): void
    {
        $clerk = User::factory()->create(['role' => User::ROLE_CLERK]);

        $this->actingAs($clerk)
            ->post(route('stations.store'), [
                'code' => 'TEST-001',
                'name' => 'Test',
                'category' => 'flow',
                'water_source' => 'river',
                'water_body_type' => 'river',
            ])
            ->assertForbidden();
    }

    public function test_manager_create_station_submits_revision_not_immediate_row(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);

        $this->actingAs($manager)
            ->post(route('stations.store'), [
                'code' => 'TEST-001',
                'name' => 'Test',
                'latitude' => -25.5,
                'longitude' => 31.5,
                'category' => 'flow',
                'water_source' => 'river',
                'water_body_type' => 'river',
                'is_active' => true,
                'is_real_time' => false,
            ]);

        // Station is NOT created immediately — it goes through the approvals queue.
        $this->assertDatabaseMissing('stations', ['code' => 'TEST-001']);
        $this->assertDatabaseHas('station_revisions', [
            'submitted_by_id' => $manager->id,
            'change_type' => 'create',
            'status' => 'pending',
        ]);
    }

    public function test_manager_forbidden_from_deleting_station(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $station = Station::first();

        $this->actingAs($manager)
            ->delete(route('stations.destroy', $station))
            ->assertForbidden();
    }
}
