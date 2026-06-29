<?php

namespace Tests\Unit\Services;

use App\Models\Comment;
use App\Models\CommentMention;
use App\Models\User;
use App\Services\CommentMentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentMentionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_extract_and_store_canonical_uuid_mention(): void
    {
        $author = User::factory()->create(['role' => User::ROLE_CLERK]);
        $target = User::factory()->create(['role' => User::ROLE_MANAGER]);

        $comment = Comment::create([
            'commentable_type' => 'station',
            'commentable_id' => \Illuminate\Support\Str::uuid(),
            'author_id' => $author->id,
            'body' => "Hey @[{$target->id}] please review this.",
        ]);

        CommentMentionService::extractAndStore($comment);

        $this->assertDatabaseHas('comment_mentions', [
            'comment_id' => $comment->id,
            'mentioned_user_id' => $target->id,
        ]);
    }

    public function test_extract_and_store_fallback_display_name_mention(): void
    {
        $author = User::factory()->create(['role' => User::ROLE_CLERK]);
        $target = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'display_name' => 'JohnDoe',
        ]);

        $comment = Comment::create([
            'commentable_type' => 'station',
            'commentable_id' => \Illuminate\Support\Str::uuid(),
            'author_id' => $author->id,
            'body' => 'Hey @JohnDoe can you check this out?',
        ]);

        CommentMentionService::extractAndStore($comment);

        $this->assertDatabaseHas('comment_mentions', [
            'comment_id' => $comment->id,
            'mentioned_user_id' => $target->id,
        ]);
    }

    public function test_extract_and_store_ignores_self_mentions(): void
    {
        $author = User::factory()->create([
            'role' => User::ROLE_CLERK,
            'display_name' => 'SelfAuthor',
        ]);

        // Self mention via display name
        $comment1 = Comment::create([
            'commentable_type' => 'station',
            'commentable_id' => \Illuminate\Support\Str::uuid(),
            'author_id' => $author->id,
            'body' => 'Self note @SelfAuthor',
        ]);

        CommentMentionService::extractAndStore($comment1);

        $this->assertDatabaseMissing('comment_mentions', [
            'comment_id' => $comment1->id,
            'mentioned_user_id' => $author->id,
        ]);

        // Self mention via UUID
        $comment2 = Comment::create([
            'commentable_type' => 'station',
            'commentable_id' => \Illuminate\Support\Str::uuid(),
            'author_id' => $author->id,
            'body' => "Self note @[{$author->id}]",
        ]);

        CommentMentionService::extractAndStore($comment2);

        $this->assertDatabaseMissing('comment_mentions', [
            'comment_id' => $comment2->id,
            'mentioned_user_id' => $author->id,
        ]);
    }

    public function test_extract_and_store_ignores_non_existent_users(): void
    {
        $author = User::factory()->create(['role' => User::ROLE_CLERK]);
        $fakeUuid = \Illuminate\Support\Str::uuid()->toString();

        $comment = Comment::create([
            'commentable_type' => 'station',
            'commentable_id' => \Illuminate\Support\Str::uuid(),
            'author_id' => $author->id,
            'body' => "Hey @[{$fakeUuid}] and @FakeUser please review.",
        ]);

        CommentMentionService::extractAndStore($comment);

        $this->assertDatabaseMissing('comment_mentions', [
            'comment_id' => $comment->id,
        ]);
    }

    public function test_extract_and_store_deduplicates_multiple_mentions(): void
    {
        $author = User::factory()->create(['role' => User::ROLE_CLERK]);
        $target = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'display_name' => 'JaneDoe',
        ]);

        $comment = Comment::create([
            'commentable_type' => 'station',
            'commentable_id' => \Illuminate\Support\Str::uuid(),
            'author_id' => $author->id,
            'body' => "Hey @[{$target->id}] and @JaneDoe, please help @[{$target->id}].",
        ]);

        CommentMentionService::extractAndStore($comment);

        $this->assertEquals(1, CommentMention::where('comment_id', $comment->id)->count());
        $this->assertDatabaseHas('comment_mentions', [
            'comment_id' => $comment->id,
            'mentioned_user_id' => $target->id,
        ]);
    }
}
