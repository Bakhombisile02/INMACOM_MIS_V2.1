<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\RegistrationPin;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationInviteController extends Controller
{
    private const ROLE_LABELS = [
        User::ROLE_ADMIN => 'Clerk Admin',
        User::ROLE_MANAGER => 'Data Manager',
        User::ROLE_CLERK => 'Data Entry',
    ];

    /**
     * Create a new invite PIN with a one-time link token.
     * admin only.
    /**
     * Create a new invite PIN with a one-time link token.
     * admin only.
     */
    public function store(Request $request, RegistrationService $service): JsonResponse
    {
        abort_unless(($request->user()?->isAdmin() ?? false), 403);

        $validated = $request->validate([
            'role' => ['required', Rule::in([
                User::ROLE_ADMIN,
                User::ROLE_MANAGER,
                User::ROLE_CLERK,
            ])],
            'note' => ['nullable', 'string', 'max:200'],
            'expires_in' => ['nullable', Rule::in(['24h', '7d', '30d', 'never'])],
            'sent_to_email' => ['nullable', 'email', 'max:255'],
        ]);

        $result = $service->createInvite(
            role: $validated['role'],
            note: $validated['note'] ?? null,
            sentToEmail: $validated['sent_to_email'] ?? null,
            expiresIn: $validated['expires_in'] ?? 'never',
            adminId: $request->user()->id
        );

        return response()->json(array_merge(['ok' => true], $result));
    }

    /**
     * Revoke an unused invite PIN.
     * admin only.
     */
    public function destroy(Request $request, RegistrationPin $pin, RegistrationService $service): JsonResponse
    {
        abort_unless(($request->user()?->isAdmin() ?? false), 403);

        try {
            $service->revokeInvite($pin);
        } catch (\InvalidArgumentException $e) {
            abort(422, $e->getMessage());
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Resend an invite email (optionally to a new address).
     * If the invite link was already opened, a fresh link_token is generated so
     * the recipient gets a new one-time link.
     * admin only.
     */
    public function resend(Request $request, RegistrationPin $pin, RegistrationService $service): JsonResponse
    {
        abort_unless(($request->user()?->isAdmin() ?? false), 403);

        $validated = $request->validate([
            'sent_to_email' => ['nullable', 'email', 'max:255'],
        ]);

        try {
            $result = $service->resendInvite($pin, $validated['sent_to_email'] ?? null);
        } catch (\InvalidArgumentException $e) {
            abort(422, $e->getMessage());
        }

        return response()->json(array_merge(['ok' => true], $result));
    }

    /**
     * On first visit: stamp link_opened_at, redirect to /login?pin={code}.
     * On subsequent visits: render the expired page.
     */
    public function open(string $token, RegistrationService $service): RedirectResponse|Response
    {
        $code = $service->openInvite($token);

        if (! $code) {
            return Inertia::render('Auth/InviteExpired');
        }

        return redirect()->route('login', ['pin' => $code]);
    }
}
