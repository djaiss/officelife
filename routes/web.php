<?php

declare(strict_types=1);

use App\Http\Controllers\App\Settings\Account\EmergencyContactController;
use App\Http\Controllers\App\Settings\Account\Logs\EmailSentController;
use App\Http\Controllers\App\Settings\Account\Logs\LogController;
use App\Http\Controllers\App\Settings\Account\PhotoController;
use App\Http\Controllers\App\Settings\Account\ProfileController;
use Illuminate\Support\Facades\Route;

// The landing page, until there is a dashboard to send people to.
Route::get('/', function () {
    return view('welcome');
})->name('home.index');

// The screens where somebody looks after their own account.
Route::middleware(['auth', 'set.locale'])->group(function (): void {
    Route::get('settings/account/profile', [ProfileController::class, 'index'])->name('settings.profile.index');
    Route::put('settings/account/profile', [ProfileController::class, 'update'])->name('settings.profile.update');
    Route::get('settings/account/logs', [LogController::class, 'index'])->name('settings.logs.index');
    Route::get('settings/account/logs/emails', [EmailSentController::class, 'index'])->name('settings.emailsSent.index');

    Route::put('settings/account/emergency-contact', [EmergencyContactController::class, 'update'])->name('settings.emergencyContact.update');

    Route::post('settings/account/photo', [PhotoController::class, 'update'])->name('settings.photo.update');
    Route::delete('settings/account/photo', [PhotoController::class, 'destroy'])->name('settings.photo.destroy');
    Route::get('settings/account/photo/{employee}/{size}', [PhotoController::class, 'show'])->whereNumber(['employee', 'size'])->name('settings.photo.show');
});
