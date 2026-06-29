<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentMention;
use App\Models\User;
use App\Services\CommentMentionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommentsController extends Controller
{
    /**
     * Allowed polymorphic commentable type keys. Some target Eloquent models, others raw DB tables.
     */
    private const ALLOWED_TYPES = [
        'measurement',
        'station',
        'station_revision',
        'disaster_incident',
    ];

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commentable_type' => ['required', 'string'],
            'commentable_id' => ['required', 'string'],
            'field_name' => ['nullable', 'string', 'max:100'],
        ]);

        $type = $this->resolveType($validated['commentable_type']);

        $query = Comment::query()
            ->with(['author:id,display_name,email,photo_url,role', 'mentions.user:id,display_name,email,role'])
            ->where('commentable_type', $type)
            ->where('commentable_id', $validated['commentable_id']);

        if ($request->filled('field_name')) {
            $query->where('field_name', $validated['field_name']);
        }

        $comments = $query->orderBy('created_at')->get();

        return response()->json([
            'comments' => $comments->map(fn (Comment $c) => $this->serialize($c)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commentable_type' => ['required', 'string'],
            'commentable_id' => ['required', 'string'],
            'parent_id' => ['nullable', 'uuid', 'exists:comments,id'],
            'field_name' => ['nullable', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $type = $this->resolveType($validated['commentable_type']);
        $user = $request->user();

        $comment = DB::transaction(function () use ($validated, $type, $user) {
            $comment = Comment::create([
                'commentable_type' => $type,
                'commentable_id' => $validated['commentable_id'],
                'parent_id' => $validated['parent_id'] ?? null,
                'field_name' => $validated['field_name'] ?? null,
                'author_id' => $user->id,
                'body' => $validated['body'],
            ]);

            CommentMentionService::extractAndStore($comment);

            return $comment;
        });

        $comment->load(['author:id,display_name,email,photo_url,role', 'mentions.user:id,display_name,email,role']);

        return response()->json(['comment' => $this->serialize($comment)], 201);
    }

    public function resolve(Request $request, Comment $comment): JsonResponse
    {
        abort_unless($request->user()->canApprove(), 403);

        $comment->update([
            'resolved_at' => now(),
            'resolved_by_id' => $request->user()->id,
        ]);

        return response()->json(['comment' => $this->serialize($comment->fresh(['author', 'mentions.user']))]);
    }

    public function unresolve(Request $request, Comment $comment): JsonResponse
    {
        abort_unless($request->user()->canApprove(), 403);

        $comment->update([
            'resolved_at' => null,
            'resolved_by_id' => null,
        ]);

        return response()->json(['comment' => $this->serialize($comment->fresh(['author', 'mentions.user']))]);
    }

    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        $user = $request->user();
        abort_unless($comment->author_id === $user->id || $user->isAdmin(), 403);

        $comment->delete();

        return response()->json(['deleted' => true]);
    }

    public function mentions(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless((bool) $user, 401);

        $mentions = CommentMention::query()
            ->with(['comment.author:id,display_name,email,photo_url'])
            ->where('mentioned_user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        return response()->json([
            'mentions' => $mentions->map(fn (CommentMention $m) => [
                'id' => $m->id,
                'read_at' => $m->read_at,
                'created_at' => $m->created_at,
                'comment' => $m->comment ? [
                    'id' => $m->comment->id,
                    'body' => $m->comment->body,
                    'commentable_type' => $m->comment->commentable_type,
                    'commentable_id' => $m->comment->commentable_id,
                    'field_name' => $m->comment->field_name,
                    'author' => $m->comment->author ? [
                        'id' => $m->comment->author->id,
                        'display_name' => $m->comment->author->display_name,
                        'photo_url' => $m->comment->author->photo_url,
                    ] : null,
                ] : null,
            ]),
        ]);
    }

    public function markMentionsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless((bool) $user, 401);

        CommentMention::query()
            ->where('mentioned_user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function searchUsers(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->whereRaw('lower(display_name) like ?', ['%'.mb_strtolower($q).'%'])
                        ->orWhereRaw('lower(email) like ?', ['%'.mb_strtolower($q).'%']);
                });
            })
            ->orderBy('display_name')
            ->limit(10)
            ->get(['id', 'display_name', 'email', 'photo_url', 'role']);

        return response()->json([
            'users' => $users->map(fn (User $u) => [
                'id' => $u->id,
                'display_name' => $u->display_name,
                'email' => $u->email,
                'photo_url' => $u->photo_url,
                'role' => $u->role,
            ]),
        ]);
    }

    private function resolveType(string $key): string
    {
        $key = strtolower($key);
        abort_unless(in_array($key, self::ALLOWED_TYPES, true), 422, 'Unknown commentable type.');

        return $key;
    }

    private function serialize(Comment $c): array
    {
        return [
            'id' => $c->id,
            'commentable_type' => $c->commentable_type,
            'commentable_id' => $c->commentable_id,
            'parent_id' => $c->parent_id,
            'field_name' => $c->field_name,
            'body' => $c->body,
            'resolved_at' => $c->resolved_at,
            'resolved_by_id' => $c->resolved_by_id,
            'created_at' => $c->created_at,
            'updated_at' => $c->updated_at,
            'author' => $c->author ? [
                'id' => $c->author->id,
                'display_name' => $c->author->display_name,
                'email' => $c->author->email,
                'photo_url' => $c->author->photo_url,
                'role' => $c->author->role,
            ] : null,
            'mentions' => $c->mentions->map(fn (CommentMention $m) => [
                'id' => $m->id,
                'user' => $m->user ? [
                    'id' => $m->user->id,
                    'display_name' => $m->user->display_name,
                    'role' => $m->user->role,
                ] : null,
                'read_at' => $m->read_at,
            ])->all(),
        ];
    }
}
