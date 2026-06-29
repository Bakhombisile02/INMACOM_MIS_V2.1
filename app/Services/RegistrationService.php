<?php

namespace App\Services;

use App\Mail\RegistrationInviteMail;
use App\Models\RegistrationPin;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrationService
{
    /** Human-readable labels for each role. Used by the frontend register Alert. */
    public const ROLE_LABELS = [
        User::ROLE_ADMIN => 'Clerk Admin',
        User::ROLE_MANAGER => 'Data Manager',
        User::ROLE_CLERK => 'Data Entry',
    ];

    /**
     * Verify a registration PIN without consuming it.
     */
    public function verifyPin(string $code): array
    {
        $code = strtoupper(trim($code));

        $pin = RegistrationPin::query()->available()->where('code', $code)->first();

        if (! $pin) {
            throw ValidationException::withMessages([
                'code' => __('Invalid or expired PIN.'),
            ]);
        }

        return [
            'ok' => true,
            'role' => $pin->role,
            'role_label' => self::ROLE_LABELS[$pin->role] ?? $pin->role,
        ];
    }

    /**
     * Reserve a verified PIN against a freshly-created Firebase identity.
     */
    public function reservePin(string $code, string $email, string $uid): void
    {
        $code = strtoupper(trim($code));
        $email = strtolower(trim($email));
        $uid = trim($uid);

        DB::transaction(function () use ($code, $email, $uid) {
            $pin = RegistrationPin::query()
                ->available()
                ->where('code', $code)
                ->lockForUpdate()
                ->first();

            if (! $pin) {
                throw ValidationException::withMessages([
                    'code' => __('Invalid or expired PIN.'),
                ]);
            }

            if ($pin->reserved_at !== null) {
                throw ValidationException::withMessages([
                    'code' => __('This PIN has already been reserved.'),
                ]);
            }

            $pin->forceFill([
                'reserved_for_email' => $email,
                'reserved_for_uid' => $uid,
                'reserved_at' => now(),
            ])->save();
        });
    }

    /**
     * Create or resolve a user using a verified Firebase ID token claim, consuming/binding their PIN.
     */
    public function createUserWithPin(string $uid, string $email, ?string $displayName, ?string $picture, ?string $pinCode): User
    {
        return DB::transaction(function () use ($uid, $email, $displayName, $picture, $pinCode) {
            $existing = User::where('firebase_uid', $uid)->first()
                ?? User::where('email', strtolower((string) $email))
                    ->whereNull('firebase_uid')
                    ->first();

            $isNewUser = $existing === null;
            $user = $existing ?? new User;

            $assignedRole = $user->role ?: User::ROLE_CLERK;
            $pin = null;

            if ($isNewUser) {
                if ($pinCode) {
                    $pin = RegistrationPin::query()
                        ->available()
                        ->where('code', $pinCode)
                        ->lockForUpdate()
                        ->first();

                    if (! $pin) {
                        throw new \RuntimeException('invalid_pin');
                    }
                } else {
                    // Look for a PIN reserved against this Firebase identity
                    $pin = RegistrationPin::query()
                        ->available()
                        ->where(function ($q) use ($uid, $email) {
                            $q->where('reserved_for_uid', $uid)
                                ->orWhere('reserved_for_email', strtolower((string) $email));
                        })
                        ->lockForUpdate()
                        ->first();
                }

                if ($pin) {
                    $assignedRole = $pin->role;
                } else {
                    // New account but neither an explicit PIN nor a reservation.
                    // Block registration — admin must whitelist this user first.
                    throw new \RuntimeException('no_pin');
                }
            }

            $user->fill([
                'firebase_uid' => $uid,
                'email' => strtolower((string) $email),
                'display_name' => $displayName ?: ($user->display_name ?: 'INMACOM User'),
                'photo_url' => $picture ?: $user->photo_url,
                'role' => $assignedRole,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);

            $user->save();

            if ($pin) {
                $pin->markUsedBy($user);
            }

            return $user;
        });
    }

    /**
     * Create a new invite PIN with a one-time link token.
     */
    public function createInvite(string $role, ?string $note, ?string $sentToEmail, ?string $expiresIn, string $adminId): array
    {
        $expiresAt = match ($expiresIn ?? 'never') {
            '24h' => now()->addHours(24),
            '7d' => now()->addDays(7),
            '30d' => now()->addDays(30),
            default => null,
        };

        $pin = RegistrationPin::create([
            'code' => RegistrationPin::generateUniqueCode(),
            'role' => $role,
            'created_by' => $adminId,
            'note' => $note ?? null,
            'expires_at' => $expiresAt,
            'link_token' => (string) Str::uuid(),
            'sent_to_email' => $sentToEmail ?? null,
        ]);

        $inviteUrl = route('register.invite', $pin->link_token);

        if ($pin->sent_to_email) {
            Mail::to($pin->sent_to_email)->queue(new RegistrationInviteMail(
                inviteUrl: $inviteUrl,
                pin: $pin->code,
                roleLabel: self::ROLE_LABELS[$pin->role] ?? $pin->role,
            ));
        }

        return [
            'pin' => $pin->code,
            'invite_url' => $inviteUrl,
            'role' => $pin->role,
            'role_label' => self::ROLE_LABELS[$pin->role] ?? $pin->role,
        ];
    }

    /**
     * Revoke an unused invite PIN.
     */
    public function revokeInvite(RegistrationPin $pin): void
    {
        if (! $pin->isAvailable()) {
            throw new \InvalidArgumentException('Invite is no longer active.');
        }

        $pin->forceFill(['revoked_at' => now()])->save();
    }

    /**
     * Resend an invite email (optionally to a new address).
     */
    public function resendInvite(RegistrationPin $pin, ?string $newEmail): array
    {
        // Cannot resend a used, revoked, or expired invite.
        if ($pin->used_at !== null || $pin->revoked_at !== null) {
            throw new \InvalidArgumentException('Invite is no longer active.');
        }
        if ($pin->expires_at !== null && $pin->expires_at->isPast()) {
            throw new \InvalidArgumentException('Invite has expired.');
        }

        $updates = [];

        if ($newEmail !== null) {
            $updates['sent_to_email'] = strtolower(trim($newEmail));
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

        return [
            'invite_url' => $inviteUrl,
            'sent_to_email' => $emailTo,
        ];
    }

    /**
     * Mark an invite link as opened and return the redirect pin code, or null if invalid.
     */
    public function openInvite(string $token): ?string
    {
        $pin = RegistrationPin::query()
            ->where('link_token', $token)
            ->first();

        if (
            ! $pin
            || $pin->link_opened_at !== null
            || ! $pin->isAvailable()
        ) {
            return null;
        }

        $pin->forceFill(['link_opened_at' => now()])->save();

        return $pin->code;
    }
}
