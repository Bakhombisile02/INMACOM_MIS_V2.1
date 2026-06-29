<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Queries\AuditLogQuery;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditLogQueryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_audit_log_query_builder_filters(): void
    {
        $user = User::first();
        $this->assertNotNull($user, 'Expected seeded user.');

        // Insert mock audit logs
        AuditLog::create([
            'actor_id' => $user->id,
            'action_type' => 'role_change',
            'entity_type' => 'User',
            'entity_id' => $user->id,
            'entity_label' => 'Test Label 1',
            'reason' => 'Testing query',
            'actor_ip' => '127.0.0.1',
            'occurred_at' => Carbon::parse('2026-06-10 10:00:00'),
        ]);

        AuditLog::create([
            'actor_id' => $user->id,
            'action_type' => 'self_approval',
            'entity_type' => 'StationRevision',
            'entity_id' => (string) Str::uuid(),
            'entity_label' => 'Test Label 2',
            'reason' => '=CSVInjectionTest',
            'actor_ip' => '127.0.0.1',
            'occurred_at' => Carbon::parse('2026-06-12 12:00:00'),
        ]);

        // 1. Filter by action type
        $count = AuditLogQuery::query()->forActionType('role_change')->paginate()->total();
        $this->assertEquals(1, $count);

        // 2. Filter by entity type
        $count = AuditLogQuery::query()->forEntityType('StationRevision')->paginate()->total();
        $this->assertEquals(1, $count);

        // 3. Filter by date range
        $count = AuditLogQuery::query()
            ->forDateRange(Carbon::parse('2026-06-11 00:00:00'), Carbon::parse('2026-06-13 00:00:00'))
            ->paginate()
            ->total();
        $this->assertEquals(1, $count);
    }

    public function test_audit_log_summary_stats_and_unique_actors(): void
    {
        $user = User::first();
        $this->assertNotNull($user, 'Expected seeded user.');

        AuditLog::truncate();

        AuditLog::create([
            'actor_id' => $user->id,
            'action_type' => 'self_approval',
            'entity_type' => 'StationRevision',
            'entity_id' => (string) Str::uuid(),
            'entity_label' => 'Test Label',
            'occurred_at' => now(), // today & this month
        ]);

        $stats = AuditLogQuery::getSummaryStats();
        $this->assertEquals(1, $stats['today_count']);
        $this->assertEquals(1, $stats['self_approval_count']);
        $this->assertEquals($user->display_name, $stats['most_active_name']);

        $actors = AuditLogQuery::getUniqueActors();
        $this->assertCount(1, $actors);
        $this->assertEquals($user->id, $actors[0]['id']);
    }

    public function test_csv_sanitization_helper(): void
    {
        $this->assertNull(AuditLogQuery::sanitizeCsvCell(null));
        $this->assertEquals("'=Formula", AuditLogQuery::sanitizeCsvCell('=Formula'));
        $this->assertEquals("'+Formula", AuditLogQuery::sanitizeCsvCell('+Formula'));
        $this->assertEquals("'-Formula", AuditLogQuery::sanitizeCsvCell('-Formula'));
        $this->assertEquals("'@Formula", AuditLogQuery::sanitizeCsvCell('@Formula'));
        $this->assertEquals('NormalValue', AuditLogQuery::sanitizeCsvCell('NormalValue'));
    }

    public function test_controller_routes_functionality(): void
    {
        $user = User::first();
        $this->assertNotNull($user, 'Expected seeded user.');

        // 1. Guest is redirected
        $this->get(route('audit.index'))->assertRedirect(route('login'));

        // 2. Admin can access index
        $response = $this->actingAs($user)->get(route('audit.index'));
        $response->assertOk();

        // 3. Admin can export CSV
        $exportResponse = $this->actingAs($user)->get(route('audit.export'));
        $exportResponse->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
