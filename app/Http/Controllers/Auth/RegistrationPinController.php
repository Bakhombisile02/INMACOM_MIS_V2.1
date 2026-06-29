<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function verify(Request $request, RegistrationService $service): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $result = $service->verifyPin($data['code']);

        return response()->json($result);
    }

    /**
     * Reserve a verified PIN against a freshly-created Firebase identity.
     * Called immediately after createUserWithEmailAndPassword so the PIN survives
     * the email-verification gap until the user comes back to log in.
     */
    public function reserve(Request $request, RegistrationService $service): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
            'email' => ['required', 'email'],
            'firebase_uid' => ['required', 'string'],
        ]);

        $service->reservePin($data['code'], $data['email'], $data['firebase_uid']);

        return response()->json(['ok' => true]);
    }
}
