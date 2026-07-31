<?php

declare(strict_types=1);

use App\Http\Controllers\App\Auth\EmailVerificationController;
use App\Http\Controllers\App\Auth\LocaleController;
use App\Http\Controllers\App\Auth\RegistrationController;
use Illuminate\Support\Facades\Route;

// Creating an account.
Route::middleware(['guest', 'set.locale'])->group(function (): void {
    Route::get('register', [RegistrationController::class, 'new'])->name('auth.register.new');
    Route::post('register', [RegistrationController::class, 'create'])->name('auth.register.create');
});

// Confirming the email address of a brand new account.
Route::middleware(['auth', 'set.locale'])->group(function (): void {
    Route::get('verify-email', [EmailVerificationController::class, 'show'])->name('auth.verification.notice');

    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'update'])
        ->middleware(['signed', 'throttle:6,1'])
        ->where('id', '[0-9]+')
        ->where('hash', '[a-f0-9]{40}')
        ->name('auth.verification.verify');

    Route::post('resend-verification-email', [EmailVerificationController::class, 'create'])
        ->middleware('throttle:6,1')
        ->name('auth.verification.send');
});

// Choosing the language of the interface, signed in or not.
Route::put('locale', [LocaleController::class, 'update'])
    ->middleware('set.locale')
    ->name('auth.locale.update');
