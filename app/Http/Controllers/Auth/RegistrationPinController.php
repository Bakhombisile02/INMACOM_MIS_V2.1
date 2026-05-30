<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\RegistrationPin;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RegistrationPinController extends Controller
{
    /** Human-readable labels for each role. Used by the frontend register Alert. */
    public const ROLE_LABELS = [
        User::ROLE_ADMIN => 'Clerk Admin',
        User::ROLE_MANAGER => 'Data Manager',
        User::ROLE_CLERK => 'Data Entry',
    ];

    /**
     * Verify a registration PIN without consuming it.
     * Consumption happens later during the firebase authenticate step.
     */
    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $code = strtoupper(trim($data['code']));

        $pin = RegistrationPin::query()->available()->where('code', $code)->first();

        if (! $pin) {
            throw ValidationException::withMessages([
                'code' => __('Invalid or expired PIN.'),
            ]);
        }

        return response()->json([
            'ok' => true,
            'role' => $pin->role,
            'role_label' => self::ROLE_LABELS[$pin->role] ?? $pin->role,
        ]);
    }

    /**
     * Reserve a verified PIN against a freshly-created Firebase identity.
     * Called immediately after createUserWithEmailAndPassword so the PIN survives
     * the email-verification gap until the user comes back to log in.
     */
    public function reserve(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
            'email' => ['required', 'email'],
            'firebase_uid' => ['required', 'string'],
        ]);

        $code = strtoupper(trim($data['code']));
        $email = strtolower(trim($data['email']));
        $uid = trim($data['firebase_uid']);

        $pin = RegistrationPin::query()->available()->where('code', $code)->first();

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

        return response()->json(['ok' => true]);
    }
}
