<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Exception\Auth\RevokedIdToken;

/**
 * @description Verifies Firebase ID tokens server-side, establishes Laravel sessions, and manages login/logout/register pages.
 * @author ITPTC / Datamatics
 * @since 2.1.0
 */
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
    public function authenticate(Request $request, RegistrationService $service): RedirectResponse
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
            $user = $service->createUserWithPin($uid, $email, $displayName, $picture, $pinCode);
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
