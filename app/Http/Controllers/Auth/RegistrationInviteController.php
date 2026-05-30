<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationInviteMail;
use App\Models\RegistrationPin;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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
     */
    public function store(Request $request): JsonResponse
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

        $expiresAt = match ($validated['expires_in'] ?? 'never') {
            '24h' => now()->addHours(24),
            '7d' => now()->addDays(7),
            '30d' => now()->addDays(30),
            default => null,
        };

        $pin = RegistrationPin::create([
            'code' => RegistrationPin::generateUniqueCode(),
            'role' => $validated['role'],
            'created_by' => $request->user()->id,
            'note' => $validated['note'] ?? null,
            'expires_at' => $expiresAt,
            'link_token' => (string) Str::uuid(),
            'sent_to_email' => $validated['sent_to_email'] ?? null,
        ]);

        $inviteUrl = route('register.invite', $pin->link_token);

        if ($pin->sent_to_email) {
            Mail::to($pin->sent_to_email)->queue(new RegistrationInviteMail(
                inviteUrl: $inviteUrl,
                pin: $pin->code,
                roleLabel: self::ROLE_LABELS[$pin->role] ?? $pin->role,
            ));
        }

        return response()->json([
            'ok' => true,
            'pin' => $pin->code,
            'invite_url' => $inviteUrl,
            'role' => $pin->role,
            'role_label' => self::ROLE_LABELS[$pin->role] ?? $pin->role,
        ]);
    }

    /**
     * Revoke an unused invite PIN.
     * admin only.
     */
    public function destroy(Request $request, RegistrationPin $pin): JsonResponse
    {
        abort_unless(($request->user()?->isAdmin() ?? false), 403);
        abort_unless($pin->isAvailable(), 422);

        $pin->forceFill(['revoked_at' => now()])->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Resend an invite email (optionally to a new address).
     * If the invite link was already opened, a fresh link_token is generated so
     * the recipient gets a new one-time link.
     * admin only.
     */
    public function resend(Request $request, RegistrationPin $pin): JsonResponse
    {
        abort_unless(($request->user()?->isAdmin() ?? false), 403);

        // Cannot resend a used, revoked, or expired invite.
        if ($pin->used_at !== null || $pin->revoked_at !== null) {
            abort(422, 'Invite is no longer active.');
        }
        if ($pin->expires_at !== null && $pin->expires_at->isPast()) {
            abort(422, 'Invite has expired.');
        }

        $validated = $request->validate([
            'sent_to_email' => ['nullable', 'email', 'max:255'],
        ]);

        $updates = [];

        if (array_key_exists('sent_to_email', $validated)) {
            $updates['sent_to_email'] = $validated['sent_to_email'];
        }

        // If the link was already opened, regenerate it so the recipient gets a fresh one-time URL.
        if ($pin->link_opened_at !== null) {
            $updates['link_token'] = (string) Str::uuid();
            $updates['link_opened_at'] = null;
        }

        if ($updates) {
            $pin->forceFill($updates)->save();
            $pin->refresh();
        }

        $inviteUrl = route('register.invite', $pin->link_token);
        $emailTo = $pin->sent_to_email;

        if ($emailTo) {
            Mail::to($emailTo)->queue(new RegistrationInviteMail(
                inviteUrl: $inviteUrl,
                pin: $pin->code,
                roleLabel: self::ROLE_LABELS[$pin->role] ?? $pin->role,
            ));
        }

        return response()->json([
            'ok' => true,
            'invite_url' => $inviteUrl,
            'sent_to_email' => $emailTo,
        ]);
    }

    /**
     * On first visit: stamp link_opened_at, redirect to /login?pin={code}.
     * On subsequent visits: render the expired page.
     */
    public function open(string $token): RedirectResponse|Response
    {
        $pin = RegistrationPin::query()
            ->where('link_token', $token)
            ->first();

        if (
            ! $pin
            || $pin->link_opened_at !== null
            || ! $pin->isAvailable()
        ) {
            return Inertia::render('Auth/InviteExpired');
        }

        $pin->forceFill(['link_opened_at' => now()])->save();

        return redirect()->route('login', ['pin' => $pin->code]);
    }
}
