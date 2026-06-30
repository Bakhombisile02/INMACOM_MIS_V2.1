<?php

namespace App\Http\Controllers;

use App\Models\RegistrationPin;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;

/**
 * @description Admin-only user management: list, update role, and delete users.
 * @author ITPTC / Datamatics
 * @since 2.1.0
 */
class UsersController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::query()->orderBy('created_at', 'desc');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('lower(display_name) like ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('lower(email) like ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('lower(organization) like ?', ['%'.mb_strtolower($search).'%']);
            });
        }

        if ($role = $request->string('role')->toString()) {
            if (in_array($role, [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_CLERK], true)) {
                $query->where('role', $role);
            }
        }

        $users = $query->paginate(20)->through(fn (User $u) => [
            'id' => $u->id,
            'display_name' => $u->display_name,
            'email' => $u->email,
            'role' => $u->role,
            'photo_url' => $u->photo_url,
            'organization' => $u->organization,
            'country' => $u->country,
            'created_at' => $u->created_at?->toDateString(),
        ]);

        $isAdmin = (auth()->user()?->isAdmin() ?? false);

        $invitations = [];
        if ($isAdmin) {
            $invitations = RegistrationPin::query()
                ->orderByDesc('created_at')
                ->limit(100)
                ->get()
                ->map(fn (RegistrationPin $pin) => [
                    'id' => $pin->id,
                    'code' => $pin->code,
                    'role' => $pin->role,
                    'created_at' => $pin->created_at?->toDateString(),
                    'expires_at' => $pin->expires_at?->toISOString(),
                    'sent_to_email' => $pin->sent_to_email,
                    'invite_url' => $pin->link_token ? route('register.invite', $pin->link_token) : null,
                    'status' => $this->pinStatus($pin),
                ]);
        }

        return Inertia::render('Users/Index', [
            'users' => $users,
            'invitations' => $invitations,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'role' => $request->string('role')->toString(),
            ],
            'isAdmin' => $isAdmin,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->requireAdmin();

        $validated = $request->validate([
            'role' => ['required', Rule::in([
                User::ROLE_ADMIN,
                User::ROLE_MANAGER,
                User::ROLE_CLERK,
            ])],
        ]);

        $previousRole = $user->role;
        $user->update(['role' => $validated['role']]);

        AuditService::record(
            actionType: AuditService::ACTION_ROLE_CHANGE,
            entityType: 'User',
            entityId: $user->id,
            entityLabel: $user->display_name.' ('.$user->email.')',
            previousState: ['role' => $previousRole],
            newState: ['role' => $validated['role']],
        );

        return back();
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->requireAdmin();

        if ($user->id === auth()->id()) {
            return back()->withErrors(['delete' => 'You cannot delete your own account.']);
        }

        // Remove from Firebase if they have a UID
        if ($user->firebase_uid) {
            try {
                app(FirebaseAuth::class)->deleteUser($user->firebase_uid);
            } catch (\Throwable) {
                // Firebase deletion failures are non-fatal — local record still removed
            }
        }

        AuditService::record(
            actionType: AuditService::ACTION_USER_DELETED,
            entityType: 'User',
            entityId: $user->id,
            entityLabel: $user->display_name.' ('.$user->email.')',
            previousState: ['role' => $user->role, 'email' => $user->email],
            newState: null,
        );

        $user->delete();

        return back();
    }

    private function requireAdmin(): void
    {
        abort_unless((auth()->user()?->isAdmin() ?? false), 403);
    }

    private function pinStatus(RegistrationPin $pin): string
    {
        if ($pin->used_at !== null) {
            return 'used';
        }
        if ($pin->revoked_at !== null) {
            return 'revoked';
        }
        if ($pin->expires_at !== null && $pin->expires_at->isPast()) {
            return 'expired';
        }
        if ($pin->link_opened_at !== null) {
            return 'linkOpened';
        }

        return 'available';
    }
}
