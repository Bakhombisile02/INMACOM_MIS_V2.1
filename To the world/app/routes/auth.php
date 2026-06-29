<?php

use App\Http\Controllers\Auth\FirebaseAuthController;
use App\Http\Controllers\Auth\RegistrationInviteController;
use App\Http\Controllers\Auth\RegistrationPinController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [FirebaseAuthController::class, 'loginPage'])->name('login');
    Route::get('/login/email', [FirebaseAuthController::class, 'loginEmailPage'])->name('login.email');

    Route::get('/register', [FirebaseAuthController::class, 'registerPage'])->name('register');
    Route::get('/register/email', [FirebaseAuthController::class, 'registerEmailPage'])->name('register.email');

    Route::post('/register/pin/verify', [RegistrationPinController::class, 'verify'])
        ->middleware('throttle:firebase-auth')
        ->name('register.pin.verify');

    Route::post('/register/pin/reserve', [RegistrationPinController::class, 'reserve'])
        ->middleware('throttle:firebase-auth')
        ->name('register.pin.reserve');

    // One-time invite link — opens once then dies; must be guest so admins can't accidentally consume it
    Route::get('/register/invite/{token}', [RegistrationInviteController::class, 'open'])
        ->name('register.invite');

    Route::post('/auth/firebase', [FirebaseAuthController::class, 'authenticate'])
        ->middleware('throttle:firebase-auth')
        ->name('auth.firebase');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [FirebaseAuthController::class, 'logout'])->name('logout');

    // Invite PIN management (admin enforced inside controller)
    Route::post('/invites', [RegistrationInviteController::class, 'store'])
        ->name('invites.store');
    Route::delete('/invites/{pin}', [RegistrationInviteController::class, 'destroy'])
        ->name('invites.destroy');
    Route::patch('/invites/{pin}/resend', [RegistrationInviteController::class, 'resend'])
        ->name('invites.resend');
});
