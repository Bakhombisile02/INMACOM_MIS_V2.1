<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ThresholdsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed the entire database reference system
        $this->seed(DatabaseSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/thresholds')
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_access_thresholds(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($user)
            ->get('/thresholds')
            ->assertOk();
    }

    public function test_manager_can_access_thresholds(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $this->actingAs($user)
            ->get('/thresholds')
            ->assertOk();
    }

    public function test_clerk_user_is_forbidden_from_thresholds(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CLERK,
        ]);

        $this->actingAs($user)
            ->get('/thresholds')
            ->assertStatus(403);
    }

    public function test_authorized_user_can_update_compliance_threshold(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $threshold = DB::table('compliance_thresholds')->first();
        $this->assertNotNull($threshold);

        $this->actingAs($user)
            ->patch(route('thresholds.compliance.update', $threshold->id), [
                'min_value' => 6.0,
                'max_value' => 9.0,
            ])
            ->assertRedirect();

        $updated = DB::table('compliance_thresholds')->where('id', $threshold->id)->first();
        $this->assertEquals(6.0, (float) $updated->min_value);
        $this->assertEquals(9.0, (float) $updated->max_value);
        $this->assertEquals('Station-specific limit (Customized)', $updated->notes);
    }

    public function test_compliance_threshold_validation_fails_on_invalid_bounds(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $threshold = DB::table('compliance_thresholds')->first();

        $this->actingAs($user)
            ->from('/thresholds')
            ->patch(route('thresholds.compliance.update', $threshold->id), [
                'min_value' => 10.0,
                'max_value' => 5.0, // max < min
            ])
            ->assertRedirect('/thresholds')
            ->assertSessionHasErrors(['max_value']);
    }

    public function test_authorized_user_can_reset_compliance_threshold_to_default(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        // Find a pH threshold
        $paramId = DB::table('water_quality_parameters')->where('code', 'pH')->value('id');
        $threshold = DB::table('compliance_thresholds')->where('parameter_id', $paramId)->first();
        $this->assertNotNull($threshold);

        // Customize it first
        DB::table('compliance_thresholds')->where('id', $threshold->id)->update([
            'min_value' => 5.0,
            'max_value' => 10.0,
            'notes' => 'Customized',
        ]);

        // Trigger reset
        $this->actingAs($user)
            ->post(route('thresholds.compliance.reset', $threshold->id))
            ->assertRedirect();

        $reset = DB::table('compliance_thresholds')->where('id', $threshold->id)->first();
        $this->assertEquals(6.5, (float) $reset->min_value);
        $this->assertEquals(8.5, (float) $reset->max_value);
        $this->assertEquals('REIWQ Appendix A guideline', $reset->notes);
    }

    public function test_authorized_user_can_update_eflow_requirement(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $requirement = DB::table('iima_eflow_requirements')->first();
        $this->assertNotNull($requirement);

        $this->actingAs($user)
            ->patch(route('thresholds.eflow.update', $requirement->id), [
                'mean_annual_mm3' => 900.5,
                'min_flow_m3_s' => 3.2,
            ])
            ->assertRedirect();

        $updated = DB::table('iima_eflow_requirements')->where('id', $requirement->id)->first();
        $this->assertEquals(900.5, (float) $updated->mean_annual_mm3);
        $this->assertEquals(3.2, (float) $updated->min_flow_m3_s);
    }

    public function test_authorized_user_can_bulk_update_group_compliance(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $paramId = DB::table('water_quality_parameters')->where('code', 'pH')->value('id');
        $this->assertNotNull($paramId);

        // Run bulk update system-wide
        $this->actingAs($user)
            ->post(route('thresholds.compliance.group'), [
                'parameter_code' => 'pH',
                'scope' => 'system',
                'min_value' => 5.5,
                'max_value' => 9.5,
            ])
            ->assertRedirect();

        // Check that thresholds for pH are now updated
        $thresholds = DB::table('compliance_thresholds')->where('parameter_id', $paramId)->get();
        $this->assertNotEmpty($thresholds);
        foreach ($thresholds as $t) {
            $this->assertEquals(5.5, (float) $t->min_value);
            $this->assertEquals(9.5, (float) $t->max_value);
            $this->assertEquals('Group limit (Customized)', $t->notes);
        }
    }

    public function test_authorized_user_can_crud_water_allocations(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        // Trigger dynamic seeding in the controller
        $this->actingAs($user)->get('/thresholds')->assertOk();

        $subcatchment = DB::table('management_areas')->first();
        $this->assertNotNull($subcatchment);

        // Fetch user category
        $category = DB::table('iima_user_categories')->where('code', 'domestic')->first();
        $this->assertNotNull($category);

        // Create water allocation
        $this->actingAs($user)
            ->post(route('thresholds.allocations.store'), [
                'subcatchment_id' => $subcatchment->id,
                'country' => 'Eswatini',
                'user_category' => 'domestic',
                'allocation_mm3_a' => 12.5,
                'effective_from' => 2026,
                'note' => 'Test Create Allocation',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $allocation = DB::table('iima_allocations')
            ->where('subcatchment_id', $subcatchment->id)
            ->where('country', 'Eswatini')
            ->where('user_category', 'domestic')
            ->first();

        $this->assertNotNull($allocation);
        $this->assertEquals(12.5, (float) $allocation->allocation_mm3_a);
        $this->assertEquals(2026, $allocation->effective_from);
        $this->assertEquals('Test Create Allocation', $allocation->note);

        // Update allocation
        $this->actingAs($user)
            ->patch(route('thresholds.allocations.update', $allocation->id), [
                'allocation_mm3_a' => 15.0,
                'effective_from' => 2027,
                'note' => 'Test Update Allocation',
            ])
            ->assertRedirect();

        $updated = DB::table('iima_allocations')->where('id', $allocation->id)->first();
        $this->assertEquals(15.0, (float) $updated->allocation_mm3_a);
        $this->assertEquals(2027, $updated->effective_from);
        $this->assertEquals('Test Update Allocation', $updated->note);

        // Delete allocation
        $this->actingAs($user)
            ->delete(route('thresholds.allocations.destroy', $allocation->id))
            ->assertRedirect();

        $deleted = DB::table('iima_allocations')->where('id', $allocation->id)->first();
        $this->assertNull($deleted);
    }

    public function test_authorized_user_can_update_hazard_settings(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $payload = [
            'flood_watch' => 3.0,
            'flood_moderate' => 6.0,
            'flood_severe' => 9.0,
            'drought_watch' => 3.0,
            'drought_moderate' => 6.0,
            'drought_severe' => 9.0,
            'chemical_a' => 1.5,
            'chemical_b' => 10.0,
            'chemical_c' => 100.0,
            'chemical_d' => 1000.0,
            'chemical_x' => 5000.0,
            'coliform_orange' => 5000.0,
            'coliform_red' => 15000.0,
            'fish_kill_orange' => 2.0,
            'fish_kill_red_count' => 3.0,
            'fish_kill_red_mass' => 2000.0,
        ];

        // Backup existing file if any
        $path = storage_path('app/hazard_settings.json');
        $backup = file_exists($path) ? file_get_contents($path) : null;

        try {
            $this->actingAs($user)
                ->patch(route('thresholds.hazard.update'), $payload)
                ->assertRedirect();

            $this->assertFileExists($path);
            $written = json_decode(file_get_contents($path), true);
            foreach ($payload as $key => $val) {
                $this->assertEquals($val, $written[$key]);
            }
        } finally {
            // Restore backup
            if ($backup !== null) {
                file_put_contents($path, $backup);
            } else {
                @unlink($path);
            }
        }
    }
}
