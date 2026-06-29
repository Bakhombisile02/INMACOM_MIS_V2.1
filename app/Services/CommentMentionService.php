<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\CommentMention;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class CommentMentionService
{
    /**
     * Parse @mentions from the comment body, resolve to user IDs, and store CommentMention rows.
     * Mentions look like `@[uuid]` (canonical) or fallback `@displayName` (best-effort lookup).
     * Never throws — failures are logged.
     */
    public static function extractAndStore(Comment $comment): void
    {
        try {
            $userIds = [];

            // Canonical: @[uuid]
            if (preg_match_all('/@\[([0-9a-f-]{36})\]/i', $comment->body, $m)) {
                $userIds = array_merge($userIds, $m[1]);
            }

            // Fallback: @display_name (no spaces). Skip if canonical mentions exist for that name.
            if (preg_match_all('/(?<!\w)@([A-Za-z0-9_.-]{2,})(?![\[a-zA-Z0-9_.-])/', $comment->body, $m)) {
                $names = array_diff($m[1], []);
                if (! empty($names)) {
                    $found = User::query()
                        ->whereRaw('LOWER(display_name) IN ('.implode(',', array_fill(0, count($names), '?')).')', array_map('strtolower', $names))
                        ->pluck('id')
                        ->all();
                    $userIds = array_merge($userIds, $found);
                }
            }

            $userIds = array_values(array_unique(array_filter($userIds)));
            if (empty($userIds)) {
                return;
            }

            // Confirm users exist
            $existing = User::whereIn('id', $userIds)->pluck('id')->all();

            foreach ($existing as $uid) {
                if ($uid === $comment->author_id) {
                    continue;
                }
                CommentMention::firstOrCreate([
                    'comment_id' => $comment->id,
                    'mentioned_user_id' => $uid,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('CommentMentionService::extractAndStore failed', [
                'error' => $e->getMessage(),
                'comment_id' => $comment->id,
            ]);
        }
    }
}
