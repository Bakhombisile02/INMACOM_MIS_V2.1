<?php

namespace Tests\Feature\Approvals;

use App\Models\Comment;
use App\Models\CommentMention;
use App\Models\Station;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_authenticated_user_can_post_and_fetch_comment_on_station(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLERK]);
        $station = Station::first();

        $this->actingAs($user)
            ->postJson('/comments', [
                'commentable_type' => 'station',
                'commentable_id' => $station->id,
                'body' => 'Initial review note',
            ])
            ->assertStatus(201)
            ->assertJsonPath('comment.body', 'Initial review note');

        $this->assertDatabaseHas('comments', [
            'commentable_type' => 'station',
            'commentable_id' => $station->id,
            'author_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/comments?commentable_type=station&commentable_id='.$station->id);

        $response->assertOk()->assertJsonStructure(['comments' => [['id', 'body', 'author']]]);
    }

    public function test_uuid_mention_creates_mention_and_notification(): void
    {
        $author = User::factory()->create(['role' => User::ROLE_CLERK]);
        $target = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $station = Station::first();

        $this->actingAs($author)
            ->postJson('/comments', [
                'commentable_type' => 'station',
                'commentable_id' => $station->id,
                'body' => "Hello @[{$target->id}] please review",
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('comment_mentions', [
            'mentioned_user_id' => $target->id,
        ]);

        $this->assertEquals(1, CommentMention::where('mentioned_user_id', $target->id)->count());
    }

    public function test_only_admin_or_author_can_delete_comment(): void
    {
        $author = User::factory()->create(['role' => User::ROLE_CLERK]);
        $other = User::factory()->create(['role' => User::ROLE_CLERK]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $station = Station::first();

        $comment = Comment::create([
            'commentable_type' => 'station',
            'commentable_id' => $station->id,
            'author_id' => $author->id,
            'body' => 'note',
        ]);

        $this->actingAs($other)->deleteJson("/comments/{$comment->id}")->assertForbidden();
        $this->actingAs($admin)->deleteJson("/comments/{$comment->id}")->assertOk();
    }

    public function test_manager_can_resolve_and_unresolve_comment(): void
    {
        $clerk = User::factory()->create(['role' => User::ROLE_CLERK]);
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $station = Station::first();

        $comment = Comment::create([
            'commentable_type' => 'station',
            'commentable_id' => $station->id,
            'author_id' => $clerk->id,
            'body' => 'flag',
        ]);

        $this->actingAs($manager)
            ->patchJson("/comments/{$comment->id}/resolve")
            ->assertOk()
            ->assertJsonPath('comment.resolved_by_id', $manager->id);

        $this->actingAs($manager)
            ->patchJson("/comments/{$comment->id}/unresolve")
            ->assertOk()
            ->assertJsonPath('comment.resolved_by_id', null);
    }

    public function test_clerk_cannot_resolve_comment(): void
    {
        $clerk = User::factory()->create(['role' => User::ROLE_CLERK]);
        $station = Station::first();

        $comment = Comment::create([
            'commentable_type' => 'station',
            'commentable_id' => $station->id,
            'author_id' => $clerk->id,
            'body' => 'flag',
        ]);

        $this->actingAs($clerk)
            ->patchJson("/comments/{$comment->id}/resolve")
            ->assertForbidden();
    }
}
