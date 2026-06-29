<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;

class UserPasswordResetController extends Controller
{
    public function store(Request $request, User $user): JsonResponse
    {
        abort_unless(($request->user()?->isAdmin() ?? false), 403);

        app(FirebaseAuth::class)->sendPasswordResetLink($user->email);

        return response()->json(['ok' => true]);
    }
}
