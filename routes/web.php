<?php

declare(strict_types=1);

use App\Http\Controllers\App\Settings\EmailSentController;
use App\Http\Controllers\App\Settings\EmergencyContactController;
use App\Http\Controllers\App\Settings\ProfileController;
use Illuminate\Support\Facades\Route;

// The landing page, until there is a dashboard to send people to.
Route::get('/', function () {
    return view('welcome');
})->name('home.index');

// The screens where somebody looks after their own account.
Route::middleware(['auth', 'set.locale'])->group(function (): void {
    Route::get('settings/account/profile', [ProfileController::class, 'index'])->name('settings.profile.index');
    Route::put('settings/account/profile', [ProfileController::class, 'update'])->name('settings.profile.update');
    Route::get('settings/account/profile/emails', [EmailSentController::class, 'index'])->name('settings.emailsSent.index');

    Route::put('settings/account/emergency-contact', [EmergencyContactController::class, 'update'])->name('settings.emergencyContact.update');
});
