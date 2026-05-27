<?php

namespace App\Http\Middleware;

use App\Models\CommentMention;
use App\Support\NavigationBuilder;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $navigation = app(NavigationBuilder::class)->build($request->user());
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'permissions' => [
                'isAdmin' => $user?->isAdmin() ?? false,
                'isManager' => $user?->isManager() ?? false,
                'isClerk' => $user?->isClerk() ?? false,
                'canApprove' => $user?->canApprove() ?? false,
                'canManageUsers' => $user?->canManageUsers() ?? false,
                'canManageDocuments' => $user?->canManageDocuments() ?? false,
            ],
            'unreadMentions' => fn () => $user
                ? CommentMention::query()
                    ->where('mentioned_user_id', $user->id)
                    ->whereNull('read_at')
                    ->count()
                : 0,
            'navigation' => [
                'main' => $navigation['main'],
                'bottom' => $navigation['bottom'],
            ],
            'navigationMeta' => $navigation['meta'],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
            ],
        ];
    }
}
