<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\RegistrationPin;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Exception\Auth\RevokedIdToken;

class FirebaseAuthController extends Controller
{
    public function loginPage()
    {
        return inertia('Auth/LoginOptions');
    }

    public function loginEmailPage()
    {
        return inertia('Auth/LoginEmail');
    }

    public function registerPage()
    {
        return inertia('Auth/RegisterOptions');
    }

    public function registerEmailPage()
    {
        return inertia('Auth/RegisterEmail');
    }

    /**
     * Verify a Firebase ID token and establish a Laravel session.
     * Called after the client completes Firebase auth (email/password or Google).
     */
    public function authenticate(Request $request): RedirectResponse
    {
        $request->validate([
            'id_token' => ['required', 'string'],
            'registration_pin' => ['nullable', 'string', 'size:6'],
        ]);

        try {
            /** @var \Kreait\Firebase\Contract\Auth $firebaseAuth */
            $firebaseAuth = app(\Kreait\Firebase\Contract\Auth::class);
            $token = $firebaseAuth->verifyIdToken($request->string('id_token'));
        } catch (FailedToVerifyToken|RevokedIdToken) {
            return back()->withErrors(['auth' => 'Authentication failed. Please try again.']);
        }

        $uid = $token->claims()->get('sub');
        $email = $token->claims()->get('email');
        $displayName = $token->claims()->get('name');
        $picture = $token->claims()->get('picture');
        $emailVerified = (bool) $token->claims()->get('email_verified', false);

        if (! $emailVerified) {
            return back()->withErrors(['auth' => 'Please verify your email address before logging in.']);
        }

        if (! $uid || ! $email) {
            return back()->withErrors(['auth' => 'Authentication failed. Please try again.']);
        }

        $pinCode = $request->filled('registration_pin')
            ? strtoupper(trim((string) $request->string('registration_pin')))
            : null;

        try {
            $user = DB::transaction(function () use ($uid, $email, $displayName, $picture, $pinCode) {
                $existing = User::query()
                    ->where('firebase_uid', $uid)
                    ->orWhere('email', strtolower((string) $email))
                    ->first();

                $isNewUser = $existing === null;
                $user = $existing ?? new User;

                $assignedRole = $user->role ?: 'clerk';
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
                        // (set when the user submitted the register form earlier).
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
                    'photo_url' => $picture,
                    'role' => $assignedRole,
                    'email_verified_at' => now(),
                ]);

                $user->save();

                if ($pin) {
                    $pin->markUsedBy($user);
                }

                return $user;
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'invalid_pin') {
                return back()->withErrors(['auth' => 'Invalid or expired registration PIN.']);
            }
            if ($e->getMessage() === 'no_pin') {
                return back()->withErrors(['auth' => 'No account found. Ask an administrator for a registration PIN to sign up, then click "Don\'t have an account? Register" below.']);
            }
            throw $e;
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
