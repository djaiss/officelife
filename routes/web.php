<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// the logged in application
Route::middleware(['auth', 'set.locale'])->group(function (): void {
    // Placeholder for the dashboard, which every authenticated flow redirects
    // to. It answers with text until the screen itself is built.
    Route::get('dashboard', fn (): string => 'Dashboard')->name('dashboard.index');
});

require __DIR__.'/auth.php';
